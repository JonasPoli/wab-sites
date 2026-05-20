<?php

namespace App\Twig;

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
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PageRepository $pageRepository,
        private readonly CategoryRepository $categoryRepository,
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
        ];
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

        $fontSettings = $tenant->getFontSettings() ?? [];
        $fontGroups = [];

        for ($i = 1; $i <= 5; $i++) {
            $hKey = 'h' . $i;
            if (isset($fontSettings[$hKey])) {
                $font = $fontSettings[$hKey]['font'] ?? 'Outfit';
                $weight = $fontSettings[$hKey]['weight'] ?? '400';
                
                if (!isset($fontGroups[$font])) {
                    $fontGroups[$font] = [];
                }
                $fontGroups[$font][] = (int)$weight;
            } else {
                $fontGroups['Outfit'][] = 400;
                $fontGroups['Outfit'][] = 700;
            }
        }

        if (empty($fontGroups)) {
            $fontGroups['Outfit'] = [300, 400, 500, 600, 700, 800, 900];
        }

        $familiesParts = [];
        foreach ($fontGroups as $family => $weights) {
            $uniqueWeights = array_unique($weights);
            sort($uniqueWeights);
            $formattedFamily = str_replace(' ', '+', $family);
            if (!empty($uniqueWeights)) {
                $familiesParts[] = "family={$formattedFamily}:wght@" . implode(';', $uniqueWeights);
            } else {
                $familiesParts[] = "family={$formattedFamily}";
            }
        }

        $importString = '';
        if (!empty($familiesParts)) {
            $queryString = implode('&', $familiesParts) . '&display=swap';
            $importString = "@import url('https://fonts.googleapis.com/css2?{$queryString}');";
        }

        $cssRules = "";
        for ($i = 1; $i <= 5; $i++) {
            $hKey = 'h' . $i;
            if (isset($fontSettings[$hKey])) {
                $font = $fontSettings[$hKey]['font'] ?? 'Outfit';
                $weight = $fontSettings[$hKey]['weight'] ?? '400';
                $size = $fontSettings[$hKey]['size'] ?? '';
                
                $fallback = ($font === 'Playfair Display') ? 'serif' : 'sans-serif';
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

        return <<<HTML
<style>
{$importString}
:root {
  --color-primary: {$primary};
  --color-secondary: {$secondary};
  --color-primary-rgb: {$this->hexToRgb($primary)};
  --color-secondary-rgb: {$this->hexToRgb($secondary)};
  --topbar-height: {$topBarHeight};
}
{$cssRules}
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
