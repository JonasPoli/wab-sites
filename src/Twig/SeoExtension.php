<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\Seo\PageSeo;
use App\Service\Seo\SeoData;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `{% set seo = page_seo(_context) %}` em `templates/pub/_seo_head.html.twig`: devolve o
 * {@see SeoData} da página aberta a partir das variáveis que o controller já passou
 * (page, category, article, author, articles), sem precisar mexer em cada controller.
 */
final class SeoExtension extends AbstractExtension
{
    public function __construct(private readonly PageSeo $seo)
    {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('page_seo', $this->paraContexto(...)),
            new TwigFunction('seo_json_ld', $this->jsonLd(...), ['is_safe' => ['html']]),
        ];
    }

    /** @param array<string, mixed> $contexto o `_context` do template */
    public function paraContexto(array $contexto = []): SeoData
    {
        return $this->seo->montar($contexto);
    }

    /** JSON-LD pronto para ir dentro de `<script type="application/ld+json">`. */
    public function jsonLd(SeoData $seo): string
    {
        if ($seo->jsonLd === []) {
            return '';
        }

        $dados = count($seo->jsonLd) === 1 ? $seo->jsonLd[0] : $seo->jsonLd;

        $json = json_encode(
            $dados,
            \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE,
        );

        if ($json === false) {
            return '';
        }

        // `</script>` dentro do JSON fecharia a tag antes da hora.
        return str_replace('<', '<', $json);
    }
}
