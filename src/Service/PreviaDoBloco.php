<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageBlockImage;
use App\Entity\PageBlockPartnerLogo;
use App\Entity\PageBlockTeamMember;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Repository\CategoryRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Prévia do bloco no painel (previa-do-bloco-no-painel): monta em memória uma página com
 * uma seção (o mesmo fundo da seção real) e só o bloco em edição, com os dados do
 * formulário, para o `page.html.twig` do tema desenhar. Nada é persistido: só objetos
 * novos são criados, a entidade salva nunca é alterada e não há `flush`.
 *
 * Imagens: arquivo de exemplo com o nome do campo quando não há imagem
 * (`previa-<tipo>.*`) e um segundo arquivo (`previa-<tipo>-arquivo.*`) quando o admin
 * escolheu um arquivo ainda não salvo; o JavaScript do painel troca esse segundo pelo
 * arquivo do computador.
 */
final class PreviaDoBloco
{
    /** tipo => [pasta em public/uploads, nome do campo desenhado, largura, altura, extensão] */
    private const EXEMPLOS = [
        'imagem' => ['page_block', 'IMAGEM', 1600, 1000, 'jpg'],
        'foto'   => ['page_block_gallery', 'FOTO', 1200, 900, 'jpg'],
        'logo'   => ['partner_logo', 'LOGO', 560, 200, 'png'],
        'avatar' => ['testimonial_avatar', 'FOTO', 300, 300, 'jpg'],
        'pessoa' => ['team_member_image', 'FOTO', 600, 800, 'jpg'],
    ];

    public function __construct(
        private readonly CategoryRepository $categorias,
        private readonly SanfonaDoBloco $sanfona,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    public function montar(Request $r, Tenant $tenant, PageSection $secaoReal, ?PageBlock $salvo): Page
    {
        $page = (new Page())->setTenant($tenant)->setTitle('Prévia')->setSlug('previa')->setShowTitle(false);

        $secao = (new PageSection())->setPage($page)->setPosition(0)->setShowTitle(false)->setShowInMenu(false)
            ->setBgType($secaoReal->getBgType())->setBgColor($secaoReal->getBgColor())->setBgGradient($secaoReal->getBgGradient())
            ->setBgImage($secaoReal->getBgImage())->setBgImageOpacity($secaoReal->getBgImageOpacity())
            ->setBgImagePosition($secaoReal->getBgImagePosition())->setBgVideo($secaoReal->getBgVideo());
        $page->getSections()->add($secao);

        $tipo = (string) $r->request->get('type', $salvo?->getType() ?? 'text_image');
        $bloco = (new PageBlock())->setSection($secao)->setType($tipo);
        $secao->getBlocks()->add($bloco);

        $arquivos = $this->arquivosEscolhidos($r);

        // Texto e configuração do formulário
        $bloco->setPreTitle($this->texto($r, 'preTitle'))
            ->setTitle($this->texto($r, 'title'))
            ->setText($this->texto($r, 'text'))
            ->setEmbedUrl($this->texto($r, 'embedUrl') ?? $salvo?->getEmbedUrl())
            ->setItemCount($r->request->get('itemCount') !== null ? ((int) $r->request->get('itemCount') ?: null) : $salvo?->getItemCount());
        $catId = (int) $r->request->get('relatedCategoryId');
        $bloco->setRelatedCategory($catId ? $this->categorias->find($catId) : $salvo?->getRelatedCategory());

        $cfg = $r->request->all('config');
        foreach ($cfg['items'] ?? [] as $i => $it) {
            if (is_array($it) && ($it['link'] ?? '') !== 'external') {
                $cfg['items'][$i]['externalUrl'] = '';
            }
        }
        $cfgSalvo = $salvo?->getConfig() ?? [];
        if (!empty($cfgSalvo['videoFile']) && $r->request->get('removeVideo') !== '1') {
            $cfg['videoFile'] = $cfgSalvo['videoFile'];
        }
        if ($tipo === 'banner') {
            $cfg['banners'] = $this->slides($r, $cfgSalvo['banners'] ?? [], $arquivos);
        }
        if ($tipo === 'accordion') {
            $cfg = $this->sanfona->normalizar(is_array($cfg) ? $cfg : []);
            if ($cfg['panels'] === []) {
                $cfg['panels'] = $this->sanfona->exemplos();
            }
            // Item de tabela ainda sem linha: a tabela de exemplo no lugar
            foreach ($cfg['panels'] as $i => $item) {
                if ($item['kind'] === 'table' && $item['table']['rows'] === []) {
                    $cfg['panels'][$i]['table'] = $this->sanfona->exemplos()[1]['table'];
                }
            }
        }
        $bloco->setConfig($cfg ?: null);

        // Imagem principal: arquivo novo, a salva ou a de exemplo
        $bloco->setImage(match (true) {
            isset($arquivos['imageFile']) => $this->exemplo('imagem', true),
            (bool) $salvo?->getImage() => $salvo->getImage(),
            default => $this->exemplo('imagem', false),
        });

        match ($tipo) {
            'gallery', 'portfolio' => $this->fotos($r, $bloco, $salvo, $arquivos),
            'testimonials' => $this->depoimentos($r, $bloco, $salvo, $arquivos),
            'partner_logos' => $this->logos($r, $bloco, $salvo, $arquivos),
            'team' => $this->equipe($r, $bloco, $salvo, $arquivos),
            default => null,
        };

        return $page;
    }

    private function texto(Request $r, string $campo): ?string
    {
        $v = $r->request->get($campo);
        return $v === null || trim((string) $v) === '' ? null : (string) $v;
    }

    /** @return array<string, int> nome do campo de arquivo => quantos arquivos foram escolhidos */
    private function arquivosEscolhidos(Request $r): array
    {
        $lista = [];
        foreach ((array) $r->request->all('_previa_arquivos') as $nome => $qtd) {
            if ((int) $qtd > 0) {
                $lista[(string) $nome] = (int) $qtd;
            }
        }
        return $lista;
    }

    private function slides(Request $r, array $salvos, array $arquivos): array
    {
        $slides = [];
        foreach ((array) $r->request->all('banners') as $i => $s) {
            $imagem = isset($arquivos["banners[$i][imageFile]"]) ? $this->exemplo('imagem', true)
                : (($s['image'] ?? '') ?: (($salvos[$i]['image'] ?? '') ?: $this->exemplo('imagem', false)));
            $slides[] = ['title' => $s['title'] ?? '', 'text' => $s['text'] ?? '', 'ctaText' => $s['ctaText'] ?? '', 'ctaLink' => $s['ctaLink'] ?? '',
                         'active' => ($s['active'] ?? '1') === '0' ? '0' : '1', 'image' => $imagem];
        }
        return $slides ?: [['title' => 'Título do slide', 'text' => 'Texto do slide', 'ctaText' => 'Texto do botão', 'ctaLink' => '#', 'active' => '1', 'image' => $this->exemplo('imagem', false)]];
    }

    private function fotos(Request $r, PageBlock $bloco, ?PageBlock $salvo, array $arquivos): void
    {
        $apagar = array_map('intval', (array) $r->request->all('delete_gallery'));
        $meta = $r->request->all('gallery_meta');
        $pos = 0;
        foreach ($salvo?->getGalleryImages() ?? [] as $img) {
            if (in_array($img->getId(), $apagar, true)) {
                continue;
            }
            $m = $meta[(string) $img->getId()] ?? [];
            $bloco->getGalleryImages()->add((new PageBlockImage())->setBlock($bloco)->setFilename($img->getFilename())
                ->setTitle(array_key_exists('title', $m) ? ($m['title'] ?: null) : $img->getTitle())
                ->setCategory(array_key_exists('category', $m) ? ($m['category'] ?: null) : $img->getCategory())
                ->setCaption(array_key_exists('caption', $m) ? ($m['caption'] ?: null) : $img->getCaption())
                ->setPosition($pos++));
        }
        for ($i = 0; $i < ($arquivos['galleryFiles[]'] ?? 0); $i++) {
            $bloco->getGalleryImages()->add((new PageBlockImage())->setBlock($bloco)->setFilename($this->exemplo('foto', true))->setPosition($pos++));
        }
        if ($bloco->getGalleryImages()->isEmpty()) {
            foreach (['Primeira', 'Segunda', 'Terceira', 'Quarta', 'Quinta'] as $i => $n) {
                $bloco->getGalleryImages()->add((new PageBlockImage())->setBlock($bloco)->setFilename($this->exemplo('foto', false))
                    ->setTitle('Título da foto')->setCategory($i % 2 ? 'Categoria 2' : 'Categoria 1')->setCaption($n . ' foto')->setPosition($i));
            }
        }
    }

    private function depoimentos(Request $r, PageBlock $bloco, ?PageBlock $salvo, array $arquivos): void
    {
        $salvos = [];
        foreach ($salvo?->getTestimonials() ?? [] as $t) {
            $salvos[$t->getId()] = $t;
        }
        $apagar = array_map('intval', (array) $r->request->all('delete_testimonial'));
        foreach ((array) $r->request->all('testimonials') as $i => $d) {
            $id = (int) ($d['id'] ?? 0);
            if ($id && in_array($id, $apagar, true)) {
                continue;
            }
            $avatar = isset($arquivos["testimonials[$i][avatarFile]"]) ? $this->exemplo('avatar', true)
                : ($id && isset($salvos[$id]) ? $salvos[$id]->getAvatar() : null);
            $bloco->getTestimonials()->add((new PageBlockTestimonial())->setBlock($bloco)->setName((string) ($d['name'] ?? ''))
                ->setRole($d['role'] ?? null)->setText((string) ($d['text'] ?? ''))->setRating(max(1, min(5, (int) ($d['rating'] ?? 5))))
                ->setAvatar($avatar)->setPosition((int) $i));
        }
        if ($bloco->getTestimonials()->isEmpty()) {
            $bloco->getTestimonials()->add((new PageBlockTestimonial())->setBlock($bloco)->setName('Nome')->setRole('Cargo / Função')
                ->setText('Depoimento')->setRating(5)->setAvatar($this->exemplo('avatar', false)));
        }
    }

    private function logos(Request $r, PageBlock $bloco, ?PageBlock $salvo, array $arquivos): void
    {
        $salvos = [];
        foreach ($salvo?->getPartnerLogos() ?? [] as $l) {
            $salvos[$l->getId()] = $l;
        }
        $apagar = array_map('intval', (array) $r->request->all('delete_logo'));
        $pos = 0;
        foreach ((array) $r->request->all('logos') as $i => $d) {
            $id = (int) ($d['id'] ?? 0);
            if (!$id || !isset($salvos[$id]) || in_array($id, $apagar, true)) {
                continue;
            }
            $arquivo = isset($arquivos["logos[$i][logoFile]"]) ? $this->exemplo('logo', true) : $salvos[$id]->getLogoFilename();
            $bloco->getPartnerLogos()->add((new PageBlockPartnerLogo())->setBlock($bloco)->setName($d['name'] ?? $salvos[$id]->getName())
                ->setUrl($d['url'] ?? null)->setLogoFilename($arquivo)->setPosition($pos++));
        }
        for ($i = 0; $i < ($arquivos['logoFiles[]'] ?? 0); $i++) {
            $bloco->getPartnerLogos()->add((new PageBlockPartnerLogo())->setBlock($bloco)->setName('Logo')->setLogoFilename($this->exemplo('logo', true))->setPosition($pos++));
        }
        if ($bloco->getPartnerLogos()->isEmpty()) {
            for ($i = 1; $i <= 4; $i++) {
                $bloco->getPartnerLogos()->add((new PageBlockPartnerLogo())->setBlock($bloco)->setName('Logo ' . $i)->setLogoFilename($this->exemplo('logo', false))->setPosition($i));
            }
        }
    }

    private function equipe(Request $r, PageBlock $bloco, ?PageBlock $salvo, array $arquivos): void
    {
        $salvos = [];
        foreach ($salvo?->getTeamMembers() ?? [] as $m) {
            $salvos[$m->getId()] = $m;
        }
        $apagar = array_map('intval', (array) $r->request->all('delete_member'));
        foreach ((array) $r->request->all('team') as $i => $d) {
            $id = (int) ($d['id'] ?? 0);
            if ($id && in_array($id, $apagar, true)) {
                continue;
            }
            $foto = isset($arquivos["team[$i][imageFile]"]) ? $this->exemplo('pessoa', true)
                : ($id && isset($salvos[$id]) ? $salvos[$id]->getImage() : $this->exemplo('pessoa', false));
            $bloco->getTeamMembers()->add((new PageBlockTeamMember())->setBlock($bloco)->setName((string) ($d['name'] ?? ''))
                ->setRole($d['role'] ?? null)->setSummary($d['summary'] ?? null)->setBio($d['bio'] ?? null)->setImage($foto)
                ->setEmail($d['email'] ?? null)->setPhone($d['phone'] ?? null)->setLinkedinUrl($d['linkedinUrl'] ?? null)
                ->setInstagramUrl($d['instagramUrl'] ?? null)->setFacebookUrl($d['facebookUrl'] ?? null)->setWhatsappUrl($d['whatsappUrl'] ?? null)
                ->setPosition((int) $i));
        }
        if ($bloco->getTeamMembers()->isEmpty()) {
            for ($i = 1; $i <= 2; $i++) {
                $bloco->getTeamMembers()->add((new PageBlockTeamMember())->setBlock($bloco)->setName('Nome')->setRole('Cargo')
                    ->setImage($this->exemplo('pessoa', false))->setPosition($i));
            }
        }
    }

    /**
     * Nome do arquivo de exemplo do tipo (gerado na primeira vez). `$escolhido`: a versão que
     * o painel troca pelo arquivo escolhido no computador.
     */
    public function exemplo(string $tipo, bool $escolhido): string
    {
        [$pasta, $rotulo, $w, $h, $ext] = self::EXEMPLOS[$tipo];
        $nome = 'previa-' . $tipo . ($escolhido ? '-arquivo' : '') . '.' . $ext;
        $caminho = $this->projectDir . '/public/uploads/' . $pasta . '/' . $nome;
        if (!is_file($caminho) && function_exists('imagecreatetruecolor')) {
            (new Filesystem())->mkdir(dirname($caminho));
            // Desenha o rótulo pequeno e amplia: legível sem depender de fonte TrueType
            $p = imagecreatetruecolor(200, (int) round(200 * $h / $w));
            imagefill($p, 0, 0, imagecolorallocate($p, 221, 216, 208));
            $cor = imagecolorallocate($p, 120, 112, 103);
            $x = (int) ((imagesx($p) - imagefontwidth(5) * strlen($rotulo)) / 2);
            $y = (int) ((imagesy($p) - imagefontheight(5)) / 2);
            imagestring($p, 5, $x, $y, $rotulo, $cor);
            imagerectangle($p, 4, 4, imagesx($p) - 5, imagesy($p) - 5, $cor);
            $img = imagecreatetruecolor($w, $h);
            imagecopyresampled($img, $p, 0, 0, 0, 0, $w, $h, imagesx($p), imagesy($p));
            $ext === 'png' ? imagepng($img, $caminho) : imagejpeg($img, $caminho, 80);
            imagedestroy($p);
            imagedestroy($img);
        }
        return $nome;
    }
}
