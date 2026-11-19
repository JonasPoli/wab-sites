<?php

declare(strict_types=1);

namespace App\Mcp\Tool;

use App\Agentes\PaginasDoSite;
use App\Agentes\SiteAtual;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 30)]
final class ListarPaginasTool implements ToolInterface
{
    public function __construct(private readonly SiteAtual $site, private readonly PaginasDoSite $paginas)
    {
    }

    public function name(): string { return 'listar_paginas'; }

    public function title(): string { return 'Listar as páginas'; }

    public function description(): string
    {
        return 'Lista as páginas públicas deste site (home, páginas, categorias, notícias e posts), com título, descrição, endereço e versão em Markdown.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false];
    }

    public function call(array $arguments): ToolResult
    {
        $lista = [];
        foreach ($this->paginas->todas($this->site->tenant()) as $item) {
            $lista[] = array_filter([
                'caminho' => $item['caminho'],
                'titulo' => $item['titulo'],
                'descricao' => $item['descricao'] ?: null,
                'tipo' => $item['tipo'],
                'url' => $this->site->url($item['caminho']),
                'markdown' => $this->site->url(PaginasDoSite::enderecoMarkdown($item['caminho'])),
            ]);
        }
        $texto = \count($lista) . ' página(s) de ' . $this->site->tenant()->getName() . ":\n";
        foreach ($lista as $p) {
            $texto .= "\n- " . $p['titulo'] . ' (' . $p['caminho'] . ')' . (isset($p['descricao']) ? ': ' . $p['descricao'] : '');
        }

        return ToolResult::ok($texto, ['paginas' => $lista]);
    }
}
