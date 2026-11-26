<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Category;
use App\Entity\Page;
use App\Service\SlidesDoBanner;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Decide, no servidor, quais arquivos a página precisa carregar.
 *
 * Hoje só o carrossel: ele é a única biblioteca pesada que sobrou, e a maioria das páginas
 * não tem carrossel nenhum. Carregá-lo só onde é usado tira CSS e JavaScript do caminho de
 * renderização das demais páginas.
 *
 * Ver a requirement "Biblioteca carregada só onde é usada" da spec `desempenho-das-paginas`.
 */
final class AtivosExtension extends AbstractExtension
{
    /**
     * Só o bloco de banner usa o Swiper, e apenas com mais de um banner — galeria,
     * depoimentos e logos de parceiros têm JavaScript próprio no tema.
     */
    private const BLOCOS_COM_CARROSSEL = [];

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('usa_carrossel', $this->usaCarrossel(...)),
            new TwigFunction('imagem_do_lcp', $this->imagemDoLcp(...)),
            new TwigFunction('filtros_do_lcp', $this->filtrosDoLcp(...)),
        ];
    }

    /** @param array<string, mixed> $contexto o `_context` do template */
    public function usaCarrossel(array $contexto = []): bool
    {
        // Slider do topo da home (tema cetec): mais de um banner vira carrossel
        $banners = $contexto['banners'] ?? null;
        if (is_countable($banners) && count($banners) > 1) {
            return true;
        }

        foreach (['page', 'category'] as $chave) {
            $alvo = $contexto[$chave] ?? null;
            if (($alvo instanceof Page || $alvo instanceof Category) && $this->temBlocoDeCarrossel($alvo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * O arquivo da imagem que provavelmente forma o maior elemento visível da página,
     * para o `<head>` pré-carregá-la.
     *
     * Sem isto, o navegador só descobre a imagem depois de ler o HTML até o bloco, e o LCP
     * atrasa — era o que segurava o desempenho entre 96 e 99 nos sites com banner no topo.
     * A ordem segue a do próprio template: banner do topo, fundo da primeira seção e, por
     * último, a primeira imagem de conteúdo.
     *
     * @param array<string, mixed> $contexto o `_context` do template
     */
    public function imagemDoLcp(array $contexto = []): ?string
    {
        $banners = $contexto['banners'] ?? null;
        if (is_iterable($banners)) {
            foreach ($banners as $banner) {
                if (method_exists($banner, 'getBackgroundImage') && $banner->getBackgroundImage()) {
                    return 'uploads/hero/' . $banner->getBackgroundImage();
                }
            }
        }

        foreach (['page', 'category'] as $chave) {
            $alvo = $contexto[$chave] ?? null;
            if (!$alvo instanceof Page && !$alvo instanceof Category) {
                continue;
            }

            foreach ($alvo->getSections() as $secao) {
                if (method_exists($secao, 'isActive') && !$secao->isActive()) {
                    continue;
                }

                foreach ($secao->getBlocks() as $bloco) {
                    $tipo = $bloco->getType();
                    $tipo = $tipo instanceof \BackedEnum ? (string) $tipo->value : (string) $tipo;
                    if ($tipo !== 'banner') {
                        continue;
                    }

                    // O bloco de banner guarda os banners no JSON de configuração; só quando
                    // essa lista está vazia é que o template cai na imagem do próprio bloco.
                    $config = $bloco->getConfig() ?? [];
                    foreach ($config['banners'] ?? [] as $banner) {
                        if (($banner['active'] ?? '1') === '1' && !empty($banner['image'])) {
                            return 'uploads/page_block/' . $banner['image'];
                        }
                    }

                    if ($bloco->getImage()) {
                        return 'uploads/page_block/' . $bloco->getImage();
                    }
                }

                if ($secao->getBgType() === 'image' && $secao->getBgImage()) {
                    return 'uploads/section/bg/' . $secao->getBgImage();
                }

                foreach ($secao->getBlocks() as $bloco) {
                    if ($bloco->getImage()) {
                        return 'uploads/page_block/' . $bloco->getImage();
                    }
                }

                // Só a primeira seção conta: o que vem depois não é o maior elemento visível.
                break;
            }
        }

        return null;
    }

    /** Blocos cuja foto é desenhada com os filtros `destaque_foto*`, e não os de banner. */
    private const BLOCOS_DE_DESTAQUE = ['hero_split', 'statement'];

    /** Filtros do banner "Só a imagem"; o parcial themes/_comum/_banner_so_imagem usa os mesmos no `srcset`. */
    public const FILTROS_DO_BANNER_INTEIRO = ['banner_inteiro_mobile' => 768, 'banner_inteiro' => 1600];

    /**
     * Filtros e `sizes` da pré-carga do LCP, para a pré-carga pedir o mesmo arquivo que a
     * `<img>` vai pedir. O padrão é o do banner (`banner_mobile`/`banner_desktop`, 100vw),
     * que vale para todas as páginas que existiam antes dos blocos novos; quando a imagem
     * do LCP vem de um bloco `hero_split` ou `statement`, valem `destaque_foto*`, que não
     * recortam a foto em 16:9, com o `sizes` que o tema informa.
     *
     * @param array<string, mixed> $contexto o `_context` do template
     * @return array{filtros: array<string, int>, sizes: string}
     */
    public function filtrosDoLcp(array $contexto = [], string $sizesDoDestaque = '(min-width: 1024px) 50vw, 100vw'): array
    {
        $padrao = ['filtros' => ['banner_mobile' => 768, 'banner_desktop' => 1600], 'sizes' => '100vw'];

        $banners = $contexto['banners'] ?? null;
        if (is_iterable($banners)) {
            foreach ($banners as $banner) {
                if (method_exists($banner, 'getBackgroundImage') && $banner->getBackgroundImage()) {
                    return $padrao;
                }
            }
        }

        foreach (['page', 'category'] as $chave) {
            $alvo = $contexto[$chave] ?? null;
            if (!$alvo instanceof Page && !$alvo instanceof Category) {
                continue;
            }
            foreach ($alvo->getSections() as $secao) {
                if (method_exists($secao, 'isActive') && !$secao->isActive()) {
                    continue;
                }
                // Mesma ordem de imagemDoLcp(): banner, fundo da seção, primeira imagem de bloco
                foreach ($secao->getBlocks() as $bloco) {
                    if ($this->tipo($bloco) === 'banner') {
                        // Todos os slides "Só a imagem" (changes banner-so-imagem e -por-slide): a arte sai
                        // inteira, pelos filtros que não recortam; num banner misto, valem os do banner com texto
                        return SlidesDoBanner::todosSoImagem($bloco->getConfig() ?? [])
                            ? ['filtros' => self::FILTROS_DO_BANNER_INTEIRO, 'sizes' => '100vw']
                            : $padrao;
                    }
                }
                if ($secao->getBgType() === 'image' && $secao->getBgImage()) {
                    return $padrao;
                }
                foreach ($secao->getBlocks() as $bloco) {
                    if ($bloco->getImage()) {
                        return in_array($this->tipo($bloco), self::BLOCOS_DE_DESTAQUE, true)
                            ? ['filtros' => ['destaque_foto_mobile' => 800, 'destaque_foto' => 1400], 'sizes' => $sizesDoDestaque]
                            : $padrao;
                    }
                }
                break;
            }
        }

        return $padrao;
    }

    private function tipo(object $bloco): string
    {
        $tipo = $bloco->getType();
        return $tipo instanceof \BackedEnum ? (string) $tipo->value : (string) $tipo;
    }

    private function temBlocoDeCarrossel(Page|Category $alvo): bool
    {
        foreach ($alvo->getSections() as $secao) {
            if (method_exists($secao, 'isActive') && !$secao->isActive()) {
                continue;
            }
            foreach ($secao->getBlocks() as $bloco) {
                $tipo = $bloco->getType();
                $tipo = $tipo instanceof \BackedEnum ? (string) $tipo->value : (string) $tipo;

                // Um banner só não vira carrossel: o template desenha a imagem direto.
                // Carregar o Swiper nesse caso custava 4,5 KB de CSS bloqueante e 40 KB
                // de JavaScript à toa — e banner único é o caso da maioria das páginas.
                if ($tipo === 'banner') {
                    if ($this->quantosBanners($bloco) > 1) {
                        return true;
                    }
                    continue;
                }

                if (in_array($tipo, self::BLOCOS_COM_CARROSSEL, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Quantos banners ativos o bloco tem; sem lista na configuração, vale a imagem do bloco. */
    private function quantosBanners(object $bloco): int
    {
        $config = method_exists($bloco, 'getConfig') ? ($bloco->getConfig() ?? []) : [];
        $lista = $config['banners'] ?? null;

        if (is_array($lista) && $lista !== []) {
            $ativos = 0;
            foreach ($lista as $banner) {
                if (($banner['active'] ?? '1') === '1') {
                    ++$ativos;
                }
            }

            return $ativos;
        }

        return $bloco->getTitle() ? 1 : 0;
    }
}
