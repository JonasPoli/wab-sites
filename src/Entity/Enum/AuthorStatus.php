<?php

namespace App\Entity\Enum;

/** Situação do cadastro de um jornalista (autor) do portal. */
enum AuthorStatus: string
{
    case Pending   = 'pendente';
    case Approved  = 'aprovado';
    case Refused   = 'recusado';
    case Suspended = 'suspenso';

    public function label(): string
    {
        return match ($this) {
            self::Pending   => 'Pendente',
            self::Approved  => 'Aprovado',
            self::Refused   => 'Recusado',
            self::Suspended => 'Suspenso',
        };
    }

    /** Classe do badge no painel (admin/wab_base.html.twig). */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending   => 'bg-warn',
            self::Approved  => 'bg-ok',
            self::Refused   => 'bg-err',
            self::Suspended => 'bg-neu',
        };
    }

    /** Mensagem do login recusado para quem não está aprovado. */
    public function loginRefusalMessage(): ?string
    {
        return match ($this) {
            self::Pending   => 'Seu cadastro aguarda aprovação.',
            self::Refused   => 'Seu cadastro não foi aprovado.',
            self::Suspended => 'Seu acesso está suspenso.',
            self::Approved  => null,
        };
    }
}
