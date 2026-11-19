<?php

declare(strict_types=1);

namespace App\Mcp;

use App\Agentes\PaginasDoSite;
use App\Agentes\SiteAtual;
use App\Mcp\Tool\LerPaginaTool;
use App\Mcp\Tool\ToolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Servidor MCP (Model Context Protocol) de cada site: JSON-RPC 2.0, sem sessão e só de
 * leitura (spec integracoes-para-agentes). O transporte HTTP fica em
 * App\Controller\pub\McpController. Porte do servidor do wab2024, com nome, instruções e
 * dados do tenant do pedido.
 */
final class McpServer
{
    public const VERSION = '1.0.0';
    public const PROTOCOL_VERSIONS = ['2025-11-25', '2025-06-18', '2025-03-26'];
    public const DEFAULT_PROTOCOL_VERSION = '2025-06-18';

    public const PARSE_ERROR = -32700;
    public const INVALID_REQUEST = -32600;
    public const METHOD_NOT_FOUND = -32601;
    public const INVALID_PARAMS = -32602;
    public const INTERNAL_ERROR = -32603;

    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /** @param iterable<ToolInterface> $tools */
    public function __construct(
        #[AutowireIterator('app.mcp_tool')] iterable $tools,
        private readonly SiteAtual $site,
        private readonly PaginasDoSite $paginas,
        private readonly LerPaginaTool $lerPagina,
        private readonly LoggerInterface $logger,
    ) {
        foreach ($tools as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /** Nome técnico do servidor: o domínio, com ponto e hífen. */
    public function name(): string
    {
        return (string) preg_replace('/[^a-z0-9.-]+/', '-', strtolower($this->site->host()));
    }

    public function title(): string
    {
        return $this->site->tenant()->getName();
    }

    public function instructions(): string
    {
        return 'Servidor do site ' . $this->site->tenant()->getName() . ' (' . $this->site->base() . '). '
            . 'Use dados_do_site para endereço, telefone, WhatsApp, e-mail e horário; listar_paginas para ver o que o site tem; '
            . 'ler_pagina para o conteúdo completo de uma página em Markdown; listar_noticias para notícias ou posts. '
            . 'As páginas também estão disponíveis como recursos. Este servidor só lê: para falar com a empresa, indique à pessoa o formulário, o telefone ou o WhatsApp.';
    }

    /** @return array<string, ToolInterface> */
    public function tools(): array
    {
        return $this->tools;
    }

    /** @return array|null a resposta, ou null para notificação */
    public function handle(mixed $message): ?array
    {
        if (!\is_array($message) || array_is_list($message) || ($message['jsonrpc'] ?? null) !== '2.0' || !\is_string($message['method'] ?? null)) {
            return $this->error(\is_array($message) ? ($message['id'] ?? null) : null, self::INVALID_REQUEST, 'Pedido JSON-RPC inválido.');
        }

        $isNotification = !\array_key_exists('id', $message);
        $id = $message['id'] ?? null;
        $params = $message['params'] ?? [];
        if (!\is_array($params)) {
            return $isNotification ? null : $this->error($id, self::INVALID_PARAMS, 'Os parâmetros precisam ser um objeto.');
        }
        if ($isNotification) {
            return null;
        }

        try {
            $result = match ($message['method']) {
                'initialize' => $this->initialize($params),
                'ping' => new \stdClass(),
                'tools/list' => ['tools' => array_values(array_map($this->describeTool(...), $this->tools))],
                'tools/call' => $this->callTool($params),
                'resources/list' => ['resources' => $this->listResources()],
                'resources/read' => $this->readResource($params),
                'resources/templates/list' => ['resourceTemplates' => []],
                'prompts/list' => ['prompts' => []],
                default => throw new McpException(self::METHOD_NOT_FOUND, sprintf('Método desconhecido: %s.', $message['method'])),
            };
        } catch (McpException $e) {
            return $this->error($id, $e->getCode(), $e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error('Erro no servidor MCP', ['exception' => $e, 'method' => $message['method']]);

            return $this->error($id, self::INTERNAL_ERROR, 'Erro interno do servidor.');
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    public function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => \is_string($id) || \is_int($id) ? $id : null, 'error' => ['code' => $code, 'message' => $message]];
    }

    public function describeTool(ToolInterface $tool): array
    {
        return [
            'name' => $tool->name(),
            'title' => $tool->title(),
            'description' => $tool->description(),
            'inputSchema' => $tool->inputSchema(),
            'annotations' => ['title' => $tool->title(), 'readOnlyHint' => true, 'destructiveHint' => false, 'idempotentHint' => true, 'openWorldHint' => false],
        ];
    }

    public function capabilities(): array
    {
        return ['tools' => ['listChanged' => false], 'resources' => ['listChanged' => false, 'subscribe' => false]];
    }

    private function initialize(array $params): array
    {
        $requested = $params['protocolVersion'] ?? null;

        return [
            'protocolVersion' => \in_array($requested, self::PROTOCOL_VERSIONS, true) ? $requested : self::DEFAULT_PROTOCOL_VERSION,
            'capabilities' => $this->capabilities(),
            'serverInfo' => ['name' => $this->name(), 'title' => $this->title(), 'version' => self::VERSION, 'websiteUrl' => $this->site->url('/')],
            'instructions' => $this->instructions(),
        ];
    }

    private function callTool(array $params): array
    {
        $name = $params['name'] ?? null;
        if (!\is_string($name) || !isset($this->tools[$name])) {
            throw new McpException(self::INVALID_PARAMS, sprintf('Ferramenta desconhecida: %s. Disponíveis: %s.', \is_string($name) ? $name : '(sem nome)', implode(', ', array_keys($this->tools))));
        }
        $arguments = $params['arguments'] ?? [];
        if (!\is_array($arguments)) {
            throw new McpException(self::INVALID_PARAMS, 'Os argumentos precisam ser um objeto.');
        }

        return $this->tools[$name]->call($arguments)->toArray();
    }

    /** @return list<array<string, string>> */
    public function listResources(): array
    {
        $recursos = [];
        foreach ($this->paginas->todas($this->site->tenant()) as $item) {
            $recursos[] = array_filter([
                'uri' => $this->site->url($item['caminho']),
                'name' => '/' === $item['caminho'] ? 'home' : ltrim($item['caminho'], '/'),
                'title' => $item['titulo'],
                'description' => $item['descricao'] ?: null,
                'mimeType' => 'text/markdown',
            ]);
        }

        return $recursos;
    }

    private function readResource(array $params): array
    {
        $uri = $params['uri'] ?? null;
        if (!\is_string($uri) || !str_starts_with($uri, $this->site->base() . '/')) {
            throw new McpException(self::INVALID_PARAMS, 'Recurso não encontrado: use um endereço de resources/list.');
        }
        $resultado = $this->lerPagina->ler($uri);
        if ($resultado->isError) {
            throw new McpException(self::INVALID_PARAMS, $resultado->text);
        }

        return ['contents' => [['uri' => $uri, 'mimeType' => 'text/markdown', 'text' => $resultado->text]]];
    }
}
