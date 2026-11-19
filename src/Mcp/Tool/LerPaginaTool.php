<?php

declare(strict_types=1);

namespace App\Mcp\Tool;

use App\Agentes\HtmlDaPagina;
use App\Agentes\MarkdownDaPagina;
use App\Agentes\PaginasDoSite;
use App\Agentes\SiteAtual;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 20)]
final class LerPaginaTool implements ToolInterface
{
    public function __construct(
        private readonly SiteAtual $site,
        private readonly PaginasDoSite $paginas,
        private readonly HtmlDaPagina $html,
        private readonly MarkdownDaPagina $conversor,
    ) {
    }

    public function name(): string { return 'ler_pagina'; }

    public function title(): string { return 'Ler uma página'; }

    public function description(): string
    {
        return 'Devolve o conteúdo de uma página pública deste site em Markdown (o mesmo texto que o visitante vê). Use o caminho de listar_paginas, por exemplo "/" para a home.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['caminho' => ['type' => 'string', 'description' => 'Caminho da página (ex.: "/", "/p/sobre"); também aceita o URL completo ou o endereço .md.']],
            'required' => ['caminho'],
            'additionalProperties' => false,
        ];
    }

    public function call(array $arguments): ToolResult
    {
        return $this->ler((string) ($arguments['caminho'] ?? ''));
    }

    public function ler(string $caminho): ToolResult
    {
        $tenant = $this->site->tenant();
        $pagina = $this->paginas->porCaminho($tenant, $caminho);
        if (null === $pagina) {
            return ToolResult::error('Página não encontrada neste site. Use listar_paginas para ver os caminhos válidos.');
        }
        $html = $this->html->de($pagina['caminho']);
        if (null === $html) {
            return ToolResult::error('A página não pôde ser lida agora.');
        }

        return ToolResult::ok($this->conversor->converter($html, $tenant, $this->site->base(), $pagina));
    }
}
