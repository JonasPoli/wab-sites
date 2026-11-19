<?php

declare(strict_types=1);

namespace App\Mcp\Tool;

use App\Agentes\DadosDoSite;
use App\Agentes\SiteAtual;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 40)]
final class DadosDoSiteTool implements ToolInterface
{
    public function __construct(private readonly SiteAtual $site)
    {
    }

    public function name(): string { return 'dados_do_site'; }

    public function title(): string { return 'Dados e contato'; }

    public function description(): string
    {
        return 'Nome, descrição, endereço, telefone, WhatsApp, e-mail, horário de atendimento, redes sociais e formulário de contato deste site. Este servidor não envia mensagens: indique à pessoa o canal de contato.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false];
    }

    public function call(array $arguments): ToolResult
    {
        $dados = $this->site->dados();
        $texto = $dados['nome'] . (isset($dados['descricao']) ? ': ' . $dados['descricao'] : '') . "\n\n" . DadosDoSite::emMarkdown($dados);

        return ToolResult::ok($texto, $dados);
    }
}
