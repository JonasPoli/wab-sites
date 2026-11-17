<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Category;
use App\Entity\Page;
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
    /** Tipos de bloco que montam carrossel no tema. */
    private const BLOCOS_COM_CARROSSEL = ['banner', 'gallery', 'testimonials', 'partner_logos'];

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('usa_carrossel', $this->usaCarrossel(...)),
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

    private function temBlocoDeCarrossel(Page|Category $alvo): bool
    {
        foreach ($alvo->getSections() as $secao) {
            if (method_exists($secao, 'isActive') && !$secao->isActive()) {
                continue;
            }
            foreach ($secao->getBlocks() as $bloco) {
                $tipo = $bloco->getType();
                $tipo = $tipo instanceof \BackedEnum ? (string) $tipo->value : (string) $tipo;
                if (in_array($tipo, self::BLOCOS_COM_CARROSSEL, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
