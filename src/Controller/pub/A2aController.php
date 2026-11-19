<?php

namespace App\Controller\pub;

use App\Agentes\AgenteA2a;
use App\Agentes\SiteAtual;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Agente A2A (Agent2Agent) de cada site, por JSON-RPC 2.0 em /a2a, protocolo 1.0 (SendMessage) e
 * 0.3 (message/send). Responde na hora com uma Message; não cria tarefas, não faz
 * streaming nem notificações, e os métodos correspondentes devolvem os erros padrão.
 */
class A2aController extends AbstractController
{
    private const MAX_BYTES = 65536;

    private const PARSE_ERROR = -32700;
    private const INVALID_REQUEST = -32600;
    private const METHOD_NOT_FOUND = -32601;
    private const INVALID_PARAMS = -32602;
    private const TASK_NOT_FOUND = -32001;
    private const PUSH_NOT_SUPPORTED = -32003;
    private const UNSUPPORTED_OPERATION = -32004;
    private const VERSION_NOT_SUPPORTED = -32009;

    private const CORS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization, A2A-Version, A2A-Extensions',
        'Access-Control-Max-Age' => '86400',
    ];

    public function __construct(
        private readonly AgenteA2a $agent,
        private readonly SiteAtual $site,
    ) {
    }

    #[Route('/a2a', name: 'pub_a2a', methods: ['GET', 'POST', 'OPTIONS'])]
    public function __invoke(Request $request): Response
    {
        if ('OPTIONS' === $request->getMethod()) {
            return new Response('', Response::HTTP_NO_CONTENT, self::CORS);
        }
        if ('POST' !== $request->getMethod()) {
            return $this->send($this->error(null, self::INVALID_REQUEST, 'Este agente A2A aceita JSON-RPC por POST. Cartão: '.$this->site->url('/.well-known/agent-card.json')), Response::HTTP_METHOD_NOT_ALLOWED, ['Allow' => 'POST, OPTIONS']);
        }
        if (\strlen($request->getContent()) > self::MAX_BYTES) {
            return $this->send($this->error(null, self::INVALID_REQUEST, 'Pedido grande demais.'), Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        try {
            $message = json_decode($request->getContent(), true, 32, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->send($this->error(null, self::PARSE_ERROR, 'O corpo não é JSON válido.'));
        }

        return $this->send($this->handle($message, (string) $request->headers->get('A2A-Version', $request->query->getString('A2A-Version'))));
    }

    private function handle(mixed $rpc, string $versionHeader): array
    {
        if (!\is_array($rpc) || array_is_list($rpc) || ($rpc['jsonrpc'] ?? null) !== '2.0' || !\is_string($rpc['method'] ?? null)) {
            return $this->error($rpc['id'] ?? null, self::INVALID_REQUEST, 'Pedido JSON-RPC inválido.');
        }
        $id = $rpc['id'] ?? null;
        $method = $rpc['method'];
        $params = \is_array($rpc['params'] ?? null) ? $rpc['params'] : [];

        // 0.3 usa "message/send"; 1.0 usa "SendMessage". Sem cabeçalho, vale o estilo do método.
        $version = '' !== $versionHeader ? $versionHeader : (str_contains($method, '/') ? '0.3' : '1.0');
        if (!preg_match('/^(0\.3|1\.0)(\.\d+)?$/', $version)) {
            return $this->error($id, self::VERSION_NOT_SUPPORTED, 'Versões aceitas do A2A: 1.0 e 0.3.');
        }
        $v1 = str_starts_with($version, '1');

        return match ($method) {
            'SendMessage', 'message/send' => $this->sendMessage($id, $params, $v1),
            'SendStreamingMessage', 'message/stream', 'SubscribeToTask', 'tasks/resubscribe' => $this->error($id, self::UNSUPPORTED_OPERATION, 'Este agente não faz streaming: use SendMessage.'),
            'GetTask', 'tasks/get', 'CancelTask', 'tasks/cancel' => $this->error($id, self::TASK_NOT_FOUND, 'Este agente responde na hora e não guarda tarefas.'),
            'ListTasks', 'tasks/list' => ['jsonrpc' => '2.0', 'id' => $id, 'result' => ['tasks' => [], 'nextPageToken' => '', 'pageSize' => 0, 'totalSize' => 0]],
            'CreateTaskPushNotificationConfig', 'GetTaskPushNotificationConfig', 'ListTaskPushNotificationConfigs', 'DeleteTaskPushNotificationConfig',
            'tasks/pushNotificationConfig/set', 'tasks/pushNotificationConfig/get', 'tasks/pushNotificationConfig/list', 'tasks/pushNotificationConfig/delete' => $this->error($id, self::PUSH_NOT_SUPPORTED, 'Este agente não envia notificações.'),
            'GetExtendedAgentCard', 'agent/getAuthenticatedExtendedCard' => $this->error($id, self::UNSUPPORTED_OPERATION, 'Não há cartão estendido; o cartão público está em /.well-known/agent-card.json.'),
            default => $this->error($id, self::METHOD_NOT_FOUND, sprintf('Método desconhecido: %s.', $method)),
        };
    }

    private function sendMessage(mixed $id, array $params, bool $v1): array
    {
        $message = $params['message'] ?? null;
        if (!\is_array($message) || !\is_array($message['parts'] ?? null)) {
            return $this->error($id, self::INVALID_PARAMS, 'Envie params.message com parts.');
        }
        if (!empty($message['taskId'])) {
            return $this->error($id, self::TASK_NOT_FOUND, 'Este agente não guarda tarefas: envie a pergunta sem taskId.');
        }

        $text = '';
        foreach ($message['parts'] as $part) {
            if (\is_array($part) && \is_string($part['text'] ?? null)) {
                $text .= ' '.$part['text'];
            }
        }
        if ('' === trim($text)) {
            return $this->error($id, self::INVALID_PARAMS, 'Envie a pergunta numa parte de texto.');
        }

        $reply = $this->agent->responder(mb_substr(trim($text), 0, 2000));
        $contextId = \is_string($message['contextId'] ?? null) && '' !== $message['contextId'] ? $message['contextId'] : self::uuid();

        if ($v1) {
            $parts = [['text' => $reply['text'], 'mediaType' => 'text/plain']];
            if ([] !== $reply['data']) {
                $parts[] = ['data' => $reply['data'], 'mediaType' => 'application/json'];
            }
            $result = ['message' => ['messageId' => self::uuid(), 'contextId' => $contextId, 'role' => 'ROLE_AGENT', 'parts' => $parts]];
        } else {
            $parts = [['kind' => 'text', 'text' => $reply['text']]];
            if ([] !== $reply['data']) {
                $parts[] = ['kind' => 'data', 'data' => $reply['data']];
            }
            $result = ['kind' => 'message', 'messageId' => self::uuid(), 'contextId' => $contextId, 'role' => 'agent', 'parts' => $parts];
        }

        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => \is_string($id) || \is_int($id) ? $id : null, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function send(array $data, int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        $response = new JsonResponse($data, $status, self::CORS + $headers + ['Cache-Control' => 'no-store']);
        $response->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return $response;
    }

    private static function uuid(): string
    {
        $b = random_bytes(16);
        $b[6] = \chr(\ord($b[6]) & 0x0F | 0x40);
        $b[8] = \chr(\ord($b[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }
}
