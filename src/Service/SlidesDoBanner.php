<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Regra do "Só a imagem" do bloco Banner (changes banner-so-imagem e banner-so-imagem-por-slide),
 * num lugar só para os temas (BannerExtension), a pré-carga do LCP (AtivosExtension) e o resumo
 * de SEO (TextoDeResumo).
 *
 * A escolha é por slide (`banners[].imageOnly`). Bloco gravado antes disso guardava a opção no
 * bloco inteiro (`config.imageOnly`): ela vale para os slides que ainda não têm a chave própria.
 */
final class SlidesDoBanner
{
    /** O slide mostra só a imagem, sem camada, texto nem botões. */
    public static function soImagem(array $slide, array $config): bool
    {
        return self::ligado($slide['imageOnly'] ?? $config['imageOnly'] ?? '0');
    }

    /**
     * Todos os slides ativos são "só a imagem": o banner sai com a imagem inteira, na proporção
     * dela (themes/_comum/_banner_so_imagem.html.twig). Banner antigo, sem lista de slides,
     * segue a opção do bloco.
     */
    public static function todosSoImagem(array $config): bool
    {
        $slides = $config['banners'] ?? [];
        if (!is_array($slides) || $slides === []) {
            return self::ligado($config['imageOnly'] ?? '0');
        }

        $ativos = 0;
        foreach ($slides as $slide) {
            if (!is_array($slide) || ($slide['active'] ?? '1') !== '1') {
                continue;
            }
            if (!self::soImagem($slide, $config)) {
                return false;
            }
            ++$ativos;
        }

        return $ativos > 0;
    }

    /** O primeiro slide ativo com imagem: o que aparece primeiro e entra no resumo e no LCP. */
    public static function primeiroComImagem(array $config): ?array
    {
        foreach ($config['banners'] ?? [] as $slide) {
            if (is_array($slide) && ($slide['active'] ?? '1') === '1' && !empty($slide['image'])) {
                return $slide;
            }
        }

        return null;
    }

    private static function ligado(mixed $valor): bool
    {
        return $valor === '1' || $valor === 1 || $valor === true;
    }
}
