<?php

namespace App\Mcp\Tool;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Ferramenta do servidor MCP. Toda ferramenta é só de leitura (design D2 da mudança
 * descoberta-por-agentes): nenhuma grava dados nem envia mensagens.
 */
#[AutoconfigureTag('app.mcp_tool')]
interface ToolInterface
{
    /** Identificador usado em tools/call (minúsculas e sublinhado). */
    public function name(): string;

    public function title(): string;

    public function description(): string;

    /** JSON Schema dos argumentos. */
    public function inputSchema(): array;

    /** @param array<string, mixed> $arguments */
    public function call(array $arguments): ToolResult;
}
