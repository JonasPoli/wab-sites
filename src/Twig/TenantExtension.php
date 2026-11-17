<?php

namespace App\Twig;

use App\Service\HeadingFonts;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Service\TenantContext;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Exposes `tenant()` as a global Twig function and injects footer data
 * (footerPages, footerCategories) as globals so all templates
 * can render the footer without explicit controller injection.
 */
class TenantExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var list<string> famílias que o site aberto usa, preenchida pelo getCssVars() */
    private array $familiasEmUso = [];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PageRepository $pageRepository,
        private readonly CategoryRepository $categoryRepository,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%kernel.project_dir%')]
        private readonly string $projectDir = '',
    ) {}

    #[\Override]
    public function getGlobals(): array
    {
        return [
            'currentTenant'    => $this->tenantContext->getTenant(),
            'headerCategories' => $this->categoryRepository->findForHeader(),
            'footerPages'      => $this->pageRepository->findForFooter(),
            'footerCategories' => $this->categoryRepository->findForFooter(),
            'footerVideos'     => [],
            'footerStudies'    => [],
            'latestNews'       => [],
            'latestStudies'    => [],
        ];
    }


    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('tenant', $this->getTenant(...)),
            new TwigFunction('tenant_css_vars', $this->getCssVars(...), ['is_safe' => ['html']]),
            new TwigFunction('is_dark_color', $this->isDarkColor(...)),
            new TwigFunction('contrast_ratio', ColorContrast::ratio(...)),
            new TwigFunction('latest_pages', $this->getLatestPages(...)),
            new TwigFunction('tenant_fontes', $this->getFamiliasEmUso(...)),
        ];
    }

    /** @return \App\Entity\Page[] */
    public function getLatestPages(?\App\Entity\Category $category, ?int $limit = null): array
    {
        return $category ? $this->pageRepository->findLatestInCategory($category, $limit ?: 3) : [];
    }

    /**
     * Arquivos de fonte que o site aberto precisa pré-carregar: só o subconjunto `latin`
     * das famílias que ele usa nos títulos, e não as seis do catálogo. Caminho relativo
     * ao `assets/`, para o tema passar ao `asset()`.
     *
     * Pré-carregar arquivo que a página não usa é desperdício e o Lighthouse aponta,
     * então aqui só entra arquivo que existe mesmo em `assets/fonts`.
     *
     * @return list<string>
     */
    public function getFamiliasEmUso(): array
    {
        if ($this->familiasEmUso === []) {
            $this->getCssVars();
        }

        $arquivos = [];
        foreach ($this->familiasEmUso as $familia) {
            $prefixo = strtolower(str_replace(' ', '-', $familia));
            $candidatos = glob($this->projectDir . '/assets/fonts/' . $prefixo . '-latin-*-normal.woff2') ?: [];

            // Variável (um arquivo para todos os pesos) vence; senão, o peso 400
            $escolhido = null;
            foreach ($candidatos as $caminho) {
                $nome = basename($caminho);
                if (str_contains($nome, '-latin-ext-')) {
                    continue;
                }
                if (str_contains($nome, '100-900')) {
                    $escolhido = $nome;
                    break;
                }
                if ($escolhido === null || str_contains($nome, '-400-')) {
                    $escolhido = $nome;
                }
            }

            if ($escolhido !== null) {
                $arquivos[] = 'fonts/' . $escolhido;
            }
        }

        return array_values(array_unique($arquivos));
    }

    public function getTenant(): ?\App\Entity\Tenant
    {
        return $this->tenantContext->getTenant();
    }

    /**
     * Returns an inline <style> block with CSS custom properties for the tenant's colors.
     * Call in the <head> of every public base template.
     */
    public function getCssVars(): string
    {
        $tenant = $this->tenantContext->getTenant();
        if ($tenant === null) {
            return '';
        }

        $primary = htmlspecialchars($tenant->getPrimaryColor() ?? '#0044cc');
        $secondary = htmlspecialchars($tenant->getSecondaryColor() ?? '#ffaa00');
        $primaryDark = htmlspecialchars($tenant->getPrimaryColorDark() ?? '#3b82f6');
        $secondaryDark = htmlspecialchars($tenant->getSecondaryColorDark() ?? '#fbbf24');
        $bgColorLight1 = htmlspecialchars($tenant->getBgColorLight1() ?? '#ffffff');
        $bgColorLight2 = htmlspecialchars($tenant->getBgColorLight2() ?? '#f8fafc');
        $bgColorDark1 = htmlspecialchars($tenant->getBgColorDark1() ?? '#0d0f1a');
        $bgColorDark2 = htmlspecialchars($tenant->getBgColorDark2() ?? '#131625');

        $isPrimaryDark = $this->isDarkColor($primary);
        $isPrimaryDarkDark = $this->isDarkColor($primaryDark);

        $topbarText = $isPrimaryDark ? '#ffffff' : '#1f2937';
        $topbarBorder = $isPrimaryDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)';

        $topbarTextDark = $isPrimaryDarkDark ? '#ffffff' : '#1f2937';
        $topbarBorderDark = $isPrimaryDarkDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.1)';

        $fontSettings = $tenant->getFontSettings() ?? [];
        $fontGroups = [];

        for ($i = 1; $i <= 5; $i++) {
            $hKey = 'h' . $i;
            if (isset($fontSettings[$hKey])) {
                // Família fora do catálogo vira Outfit; peso fora da faixa da família vira o mais próximo
                $font = HeadingFonts::normalize($fontSettings[$hKey]['font'] ?? null);
                $weight = HeadingFonts::clampWeight($font, $fontSettings[$hKey]['weight'] ?? '400');

                if (!isset($fontGroups[$font])) {
                    $fontGroups[$font] = [];
                }
                $fontGroups[$font][] = $weight;
            } else {
                $fontGroups['Outfit'][] = 400;
                $fontGroups['Outfit'][] = 700;
            }
        }

        if (empty($fontGroups)) {
            $fontGroups['Outfit'] = [300, 400, 500, 600, 700, 800, 900];
        }

        // As famílias são servidas pelo próprio domínio (assets/styles/fontes.css, gerado
        // por `app:fontes:sincronizar`), carregado no <head> dos temas. Aqui ficou só a
        // lista de quem este site usa, para o tema pré-carregar as certas.
        // Antes havia aqui um `@import url('https://fonts.googleapis.com/css2?…')` dentro
        // do <style> em linha: o navegador só descobria a fonte depois de ler o CSS, não
        // dava para pré-carregar e o `display=optional` trocava a fonte depois da pintura.
        $this->familiasEmUso = array_keys($fontGroups);
        $importString = '';

        $cssRules = "";
        for ($i = 1; $i <= 5; $i++) {
            $hKey = 'h' . $i;
            if (isset($fontSettings[$hKey])) {
                $font = HeadingFonts::normalize($fontSettings[$hKey]['font'] ?? null);
                $weight = HeadingFonts::clampWeight($font, $fontSettings[$hKey]['weight'] ?? '400');
                $size = $fontSettings[$hKey]['size'] ?? '';

                $fallback = HeadingFonts::fallback($font);
                $fontEscaped = htmlspecialchars($font);
                
                $rules = [];
                $rules[] = "font-family: '{$fontEscaped}', {$fallback} !important;";
                $rules[] = "font-weight: {$weight} !important;";
                if ($size !== '') {
                    $rules[] = "font-size: " . htmlspecialchars($size) . " !important;";
                }
                
                $cssRules .= "            h{$i} {\n";
                foreach ($rules as $rRule) {
                    $cssRules .= "                {$rRule}\n";
                }
                $cssRules .= "            }\n";
            }
        }

        $navSettings = $tenant->getNavigationSettings() ?? [];
        $topBarEnabled = $navSettings['topBarEnabled'] ?? false;
        $topBarHeight = $topBarEnabled ? '40px' : '0px';

        // Variante legível da cor da marca, para TEXTO.
        //
        // A cor que o cliente escolhe costuma ser pensada para preenchimento, não para
        // letra: o verde #7abd2f tem 2,3:1 sobre branco, bem abaixo do mínimo de 4,5:1 do
        // WCAG AA, e era o que reprovava `color-contrast` nos sites do tema cetec. Aqui ela
        // é escurecida (ou clareada, no modo escuro) só o quanto baste, mantendo matiz e
        // saturação: a marca continua reconhecível e o texto fica legível.
        //
        // Preenchimento sólido continua usando `--color-primary`, a cor exata do cliente;
        // quem precisa de contraste ali é o texto por cima, que usa `--color-on-primary`.
        // Os fundos em que o tema desenha texto com a cor da marca: a página, a faixa
        // alternada e as "pílulas", que são a cor com 30% de opacidade sobre a página.
        // A pílula aparece tanto sobre a faixa clara 1 quanto sobre a 2, então as duas
        // misturas entram na conta. A margem de 4,6 (acima dos 4,5 do WCAG AA) absorve a
        // diferença de arredondamento entre a mistura calculada aqui e a que o navegador
        // pinta — sem ela, o contraste ficava em 4,31 e o axe reprovava por pouco.
        $misturas = [];
        foreach ([$bgColorLight1, $bgColorLight2] as $fundo) {
            $misturas[] = ColorContrast::mistura($secondary, $fundo, 0.30);
            $misturas[] = ColorContrast::mistura($primary, $fundo, 0.30);
        }
        $fundosClaros = array_filter(array_merge([$bgColorLight1, $bgColorLight2], $misturas));

        $misturasEscuras = [];
        foreach ([$bgColorDark1, $bgColorDark2] as $fundo) {
            $misturasEscuras[] = ColorContrast::mistura($secondaryDark, $fundo, 0.30);
            $misturasEscuras[] = ColorContrast::mistura($primaryDark, $fundo, 0.30);
        }
        $fundosEscuros = array_filter(array_merge([$bgColorDark1, $bgColorDark2], $misturasEscuras));

        $minimo = 4.6;
        $primaryText = ColorContrast::legivelSobreVarios($primary, $fundosClaros, $minimo) ?? $primary;
        $secondaryText = ColorContrast::legivelSobreVarios($secondary, $fundosClaros, $minimo) ?? $secondary;
        $primaryTextDark = ColorContrast::legivelSobreVarios($primaryDark, $fundosEscuros, $minimo) ?? $primaryDark;
        $secondaryTextDark = ColorContrast::legivelSobreVarios($secondaryDark, $fundosEscuros, $minimo) ?? $secondaryDark;

        $onPrimary = ColorContrast::tintaSobre($primary);
        $onSecondary = ColorContrast::tintaSobre($secondary);
        $onPrimaryDark = ColorContrast::tintaSobre($primaryDark);
        $onSecondaryDark = ColorContrast::tintaSobre($secondaryDark);

        return <<<HTML
<style>
{$importString}
:root {
  --color-primary: {$primary};
  --color-secondary: {$secondary};
  --color-primary-rgb: {$this->hexToRgb($primary)};
  --color-secondary-rgb: {$this->hexToRgb($secondary)};
  --color-primary-text: {$primaryText};
  --color-secondary-text: {$secondaryText};
  --color-primary-text-rgb: {$this->hexToRgb($primaryText)};
  --color-secondary-text-rgb: {$this->hexToRgb($secondaryText)};
  --color-on-primary: {$onPrimary};
  --color-on-secondary: {$onSecondary};
  --topbar-height: {$topBarHeight};
  --topbar-text: {$topbarText};
  --topbar-border: {$topbarBorder};
}

[data-theme="dark"], .dark, .wab-section-dark, .mp-section-dark {
  --color-primary: {$primaryDark};
  --color-secondary: {$secondaryDark};
  --color-primary-rgb: {$this->hexToRgb($primaryDark)};
  --color-secondary-rgb: {$this->hexToRgb($secondaryDark)};
  --color-primary-text: {$primaryTextDark};
  --color-secondary-text: {$secondaryTextDark};
  --color-primary-text-rgb: {$this->hexToRgb($primaryTextDark)};
  --color-secondary-text-rgb: {$this->hexToRgb($secondaryTextDark)};
  --color-on-primary: {$onPrimaryDark};
  --color-on-secondary: {$onSecondaryDark};
  --topbar-text: {$topbarTextDark};
  --topbar-border: {$topbarBorderDark};
}
{$cssRules}

/* Automatic alternating backgrounds for undefined sections */
.auto-bg-a {
  background-color: {$bgColorLight1} !important;
}
.auto-bg-b {
  background-color: {$bgColorLight2} !important;
}
[data-theme="dark"] .auto-bg-a, html.dark .auto-bg-a, .dark .auto-bg-a {
  background-color: {$bgColorDark1} !important;
}
[data-theme="dark"] .auto-bg-b, html.dark .auto-bg-b, .dark .auto-bg-b {
  background-color: {$bgColorDark2} !important;
}
</style>
HTML;
    }

    public function isDarkColor(?string $hex): bool
    {
        if (!$hex) {
            return true;
        }
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) {
            return true;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = ($r * 0.299 + $g * 0.587 + $b * 0.114);
        return $luminance < 170;
    }

    private function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }
}
