<?php

namespace App\Twig;

use App\Service\ContatoAntispam;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Expõe `contato_antispam()` para o parcial de proteção do formulário de contato montar o campo
 * isca e o campo com a hora assinada (change antispam-no-contato), em qualquer página que tenha
 * formulário de contato, sem depender do controller que renderizou a página.
 */
class AntispamExtension extends AbstractExtension
{
    public function __construct(private readonly ContatoAntispam $antispam) {}

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('contato_antispam', $this->antispam->camposDoFormulario(...)),
        ];
    }
}
