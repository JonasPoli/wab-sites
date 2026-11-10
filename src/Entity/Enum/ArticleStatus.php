<?php

namespace App\Entity\Enum;

/** Situação de uma notícia do portal (tema noticias). */
enum ArticleStatus: string
{
    case Draft     = 'rascunho';
    case Published = 'publicada';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Rascunho',
            self::Published => 'Publicada',
        };
    }

    /** Classe do badge no painel (admin/wab_base.html.twig). */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft     => 'bg-neu',
            self::Published => 'bg-ok',
        };
    }
}
