<?php

namespace App\Service\Wordpress;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use App\Entity\Redirect;
use App\Entity\Tenant;
use App\Service\ArticleBodySanitizer;
use App\Service\CaminhoNormalizado;
use App\Service\Seo\TextoDeResumo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Importa os posts e as categorias de um WordPress para as notícias de um site
 * (importacao-de-wordpress). Repetível: post cujo slug já existe no site é pulado, e
 * categoria e redirecionamento que já existem não são duplicados. Nunca altera o tenant,
 * as páginas nem notícias que não criou.
 *
 * Roda pelo console, sem o filtro de tenant do domínio: toda consulta diz o tenant.
 */
final class ImportadorDePosts
{
    private const FUSO = 'America/Sao_Paulo';

    public function __construct(
        private readonly ClienteWordpress $cliente,
        private readonly LimpadorDeConteudo $limpador,
        private readonly ArticleBodySanitizer $sanitizador,
        private readonly EntityManagerInterface $em,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * @param array<string, string> $mapa caminho antigo => caminho novo (links do próprio site)
     * @return array{criados: list<string>, pulados: list<string>, rascunhos: list<string>, semCapa: list<string>, categorias: list<string>, redirecionamentos: int, avisos: list<string>}
     */
    public function importar(Tenant $tenant, string $base, bool $simular, ?int $limite = null, array $mapa = [], string $textoDoLink = 'clique aqui'): array
    {
        $rel = ['criados' => [], 'pulados' => [], 'rascunhos' => [], 'semCapa' => [], 'categorias' => [], 'redirecionamentos' => 0, 'avisos' => []];
        $host = (string) parse_url($base, PHP_URL_HOST);
        $mapaNormalizado = [];
        foreach ($mapa as $de => $para) {
            $mapaNormalizado[CaminhoNormalizado::de($de)] = $para;
        }

        // Categorias (sem a "sem-categoria" padrão do WordPress)
        $categoriasWp = [];
        foreach ($this->cliente->categorias($base) as $c) {
            if (($c['slug'] ?? '') === '' || $c['slug'] === 'sem-categoria' || $c['slug'] === 'uncategorized') {
                continue;
            }
            $categoriasWp[(int) $c['id']] = $c;
        }

        $posts = $this->cliente->posts($base);
        if ($limite !== null) {
            $posts = array_slice($posts, 0, $limite);
        }
        $slugsImportados = array_map(fn ($p) => (string) $p['slug'], $posts);

        // Categorias usadas pelos posts
        $categorias = [];
        foreach ($posts as $p) {
            foreach ($p['categories'] ?? [] as $cid) {
                $wp = $categoriasWp[(int) $cid] ?? null;
                if (!$wp || isset($categorias[$wp['slug']])) {
                    continue;
                }
                $existente = $this->em->getRepository(Category::class)->findOneBy(['tenant' => $tenant, 'slug' => $wp['slug']]);
                if (!$existente) {
                    $existente = (new Category())->setTenant($tenant)->setSlug($wp['slug'])
                        ->setName(html_entity_decode((string) $wp['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if (!$simular) {
                        $this->em->persist($existente);
                    }
                    $rel['categorias'][] = $existente->getName();
                }
                $categorias[$wp['slug']] = $existente;
                $this->redirecionar($tenant, '/category/' . $wp['slug'], '/blog/categoria/' . $wp['slug'], $simular, $rel);
            }
        }

        foreach ($posts as $p) {
            $slug = (string) $p['slug'];
            $titulo = html_entity_decode(strip_tags((string) ($p['title']['rendered'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $antigo = (string) parse_url((string) ($p['link'] ?? ''), PHP_URL_PATH);

            $artigo = $this->em->getRepository(Article::class)->findOneBy(['tenant' => $tenant, 'slug' => $slug]);
            if ($artigo) {
                $rel['pulados'][] = $titulo;
                $this->redirecionar($tenant, $antigo, $artigo->getStatus() === ArticleStatus::Published ? '/blog/' . $slug : '/blog', $simular, $rel);
                continue;
            }

            $corpo = $this->limpador->limpar((string) ($p['content']['rendered'] ?? ''), $host, $slugsImportados, $mapaNormalizado, $textoDoLink);
            $corpo = $this->sanitizador->sanitize($corpo);
            $resumo = $this->limpador->resumo($p['excerpt']['rendered'] ?? null);
            $resumo = $resumo !== null ? TextoDeResumo::cortar($resumo, 160) : ($corpo ? TextoDeResumo::resumir($corpo, 160) : null);
            $publicado = $corpo !== null;

            $artigo = (new Article())
                ->setTenant($tenant)
                ->setTitle($titulo)
                ->setSlug($slug)
                ->setBody($corpo)
                ->setSummary($resumo !== '' ? $resumo : null)
                ->setStatus($publicado ? ArticleStatus::Published : ArticleStatus::Draft)
                ->setPublishedAt(new \DateTimeImmutable((string) $p['date'], new \DateTimeZone(self::FUSO)));
            foreach ($p['categories'] ?? [] as $cid) {
                $wp = $categoriasWp[(int) $cid] ?? null;
                if ($wp && isset($categorias[$wp['slug']])) {
                    $artigo->setCategory($categorias[$wp['slug']]);
                    break;
                }
            }

            $midia = $p['_embedded']['wp:featuredmedia'][0] ?? null;
            if (is_array($midia) && !empty($midia['source_url'])) {
                $capa = $simular ? 'simulado.jpg' : $this->baixarCapa((string) $midia['source_url'], (int) ($p['id'] ?? 0));
                if ($capa) {
                    $artigo->setCoverImage($capa);
                    $alt = trim((string) ($midia['alt_text'] ?? ''));
                    if ($alt !== '') {
                        $artigo->setCoverCaption(mb_substr($alt, 0, 255));
                    }
                } else {
                    $rel['semCapa'][] = $titulo;
                    $rel['avisos'][] = sprintf('Capa não baixada ou formato não aceito: %s', $midia['source_url']);
                }
            } else {
                $rel['semCapa'][] = $titulo;
            }

            if (!$simular) {
                $this->em->persist($artigo);
            }
            $rel['criados'][] = $titulo;
            if (!$publicado) {
                $rel['rascunhos'][] = $titulo;
            }
            $this->redirecionar($tenant, $antigo, $publicado ? '/blog/' . $slug : '/blog', $simular, $rel);
        }

        if (!$simular) {
            $this->em->flush();
        }

        return $rel;
    }

    private function redirecionar(Tenant $tenant, string $de, string $para, bool $simular, array &$rel): void
    {
        $de = CaminhoNormalizado::de($de);
        if ($de === '/' || $de === CaminhoNormalizado::de($para)) {
            return;
        }
        $repo = $this->em->getRepository(Redirect::class);
        if ($repo->findOneBy(['tenant' => $tenant, 'fromPath' => $de])) {
            return;
        }
        // Evita duplicar dentro da mesma execução (ainda não gravado)
        foreach ($this->em->getUnitOfWork()->getScheduledEntityInsertions() as $agendado) {
            if ($agendado instanceof Redirect && $agendado->getTenant() === $tenant && $agendado->getFromPath() === $de) {
                return;
            }
        }
        if (!$simular) {
            $this->em->persist((new Redirect())->setTenant($tenant)->setFromPath($de)->setToPath($para));
        }
        ++$rel['redirecionamentos'];
    }

    /** Baixa a imagem destacada para public/uploads/article; WebP vira JPG. Devolve o nome do arquivo. */
    private function baixarCapa(string $url, int $id): ?string
    {
        $binario = $this->cliente->baixar($url);
        if ($binario === null || strlen($binario) < 100) {
            return null;
        }
        $info = @getimagesizefromstring($binario);
        $tipo = $info['mime'] ?? '';
        $base = sprintf('wp-%d-%s', $id, preg_replace('~[^a-z0-9-]+~', '-', strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME))));
        $pasta = $this->projectDir . '/public/uploads/article';
        (new Filesystem())->mkdir($pasta);

        if ($tipo === 'image/jpeg' || $tipo === 'image/png') {
            $nome = $base . ($tipo === 'image/png' ? '.png' : '.jpg');
            file_put_contents($pasta . '/' . $nome, $binario);
            return $nome;
        }
        if ($tipo === 'image/webp' && function_exists('imagecreatefromstring')) {
            $img = @imagecreatefromstring($binario);
            if ($img) {
                $nome = $base . '.jpg';
                imagejpeg($img, $pasta . '/' . $nome, 86);
                imagedestroy($img);
                return $nome;
            }
        }
        return null;
    }
}
