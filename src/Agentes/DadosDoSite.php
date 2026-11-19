<?php

declare(strict_types=1);

namespace App\Agentes;

use App\Entity\Tenant;

/**
 * Dados públicos de contato de um site (tenant), como aparecem no rodapé: usados no
 * Markdown, no llms.txt, no servidor MCP, na API e no agente A2A. Só entra o que o site
 * cadastrou (o horário padrão de exemplo do Tenant fica de fora).
 */
final class DadosDoSite
{
    /** @return array<string, mixed> */
    public static function de(Tenant $tenant, string $base): array
    {
        $horario = [];
        if ($tenant->hasOpeningHours()) {
            foreach ($tenant->getOpeningHours() as $linha) {
                $texto = trim(rtrim(trim((string) ($linha['days'] ?? '')), ':') . ': ' . trim((string) ($linha['hours'] ?? '')), ': ');
                if ('' !== $texto) {
                    $horario[] = $texto;
                }
            }
        }
        $whatsapp = $tenant->getWhatsappLink() ?: null;

        return array_filter([
            'nome' => $tenant->getName(),
            'site' => $base . '/',
            'descricao' => PaginasDoSite::texto($tenant->getSeoDescription() ?: $tenant->getAboutText(), 500) ?: null,
            'endereco' => PaginasDoSite::texto($tenant->getAddress(), 300) ?: null,
            'telefone' => $tenant->getPhone() ?: null,
            'whatsapp' => $whatsapp,
            'email' => $tenant->getContactEmail() ?: null,
            'horario' => $horario ?: null,
            'formulario' => $base . '/#contato',
            'redes' => array_values(array_filter([
                $tenant->getInstagramLink(), $tenant->getFacebookLink(), $tenant->getLinkedinLink(), $tenant->getYoutubeLink(),
            ])) ?: null,
        ], static fn ($v) => null !== $v && '' !== $v);
    }

    /** Os mesmos dados em Markdown (lista), para o fim das páginas e o llms.txt. */
    public static function emMarkdown(array $dados): string
    {
        $linhas = [];
        if (isset($dados['telefone'])) {
            $linhas[] = '- Telefone: ' . $dados['telefone'];
        }
        if (isset($dados['whatsapp'])) {
            $linhas[] = '- WhatsApp: ' . $dados['whatsapp'];
        }
        if (isset($dados['email'])) {
            $linhas[] = '- E-mail: ' . $dados['email'];
        }
        if (isset($dados['endereco'])) {
            $linhas[] = '- Endereço: ' . $dados['endereco'];
        }
        if (isset($dados['horario'])) {
            $linhas[] = '- Atendimento: ' . implode('; ', $dados['horario']);
        }
        $linhas[] = '- Formulário de contato: ' . $dados['formulario'];
        foreach ($dados['redes'] ?? [] as $rede) {
            $linhas[] = '- ' . $rede;
        }

        return implode("\n", $linhas);
    }
}
