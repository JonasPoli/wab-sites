<?php

namespace App\Controller\pub;

use App\Mcp\McpServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Transporte Streamable HTTP do servidor MCP: JSON-RPC por POST, respostas em JSON,
 * sem sessão e sem fluxo SSE (GET e DELETE respondem 405). Público e só de leitura,
 * por isso o CORS é aberto. Um servidor por site: os dados são os do domínio do pedido.
 */
class McpController extends AbstractController
{
    private const MAX_BYTES = 65536;

    private const CORS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, Mcp-Session-Id, MCP-Protocol-Version, Last-Event-ID',
        'Access-Control-Expose-Headers' => 'Mcp-Session-Id, MCP-Protocol-Version',
        'Access-Control-Max-Age' => '86400',
    ];

    public function __construct(private readonly McpServer $server)
    {
    }

    #[Route('/mcp', name: 'pub_mcp', methods: ['GET', 'POST', 'DELETE', 'OPTIONS'])]
    public function __invoke(Request $request): Response
    {
        if ('OPTIONS' === $request->getMethod()) {
            return new Response('', Response::HTTP_NO_CONTENT, self::CORS);
        }
        if ('POST' !== $request->getMethod()) {
            return $this->json(
                $this->server->error(null, McpServer::INVALID_REQUEST, 'Este servidor MCP aceita só POST (Streamable HTTP, sem fluxo SSE).'),
                Response::HTTP_METHOD_NOT_ALLOWED,
                self::CORS + ['Allow' => 'POST, OPTIONS'],
            );
        }

        $body = $request->getContent();
        if (\strlen($body) > self::MAX_BYTES) {
            return $this->json($this->server->error(null, McpServer::INVALID_REQUEST, 'Pedido grande demais.'), Response::HTTP_REQUEST_ENTITY_TOO_LARGE, self::CORS);
        }

        try {
            $message = json_decode($body, true, 32, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json($this->server->error(null, McpServer::PARSE_ERROR, 'O corpo não é JSON válido.'), Response::HTTP_BAD_REQUEST, self::CORS);
        }

        // Lote (array de mensagens), aceito como no protocolo 2025-03-26.
        if (\is_array($message) && array_is_list($message) && [] !== $message) {
            $responses = array_values(array_filter(array_map($this->server->handle(...), $message)));

            return [] === $responses ? new Response('', Response::HTTP_ACCEPTED, self::CORS) : $this->json($responses, Response::HTTP_OK, self::CORS);
        }

        $response = $this->server->handle($message);

        return null === $response ? new Response('', Response::HTTP_ACCEPTED, self::CORS) : $this->json($response, Response::HTTP_OK, self::CORS);
    }

    protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $response = new JsonResponse($data, $status, $headers);
        $response->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
