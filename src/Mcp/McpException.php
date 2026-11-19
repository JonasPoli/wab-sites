<?php

namespace App\Mcp;

/** Erro de protocolo JSON-RPC: o código vira o "code" do erro na resposta. */
final class McpException extends \RuntimeException
{
    public function __construct(int $code, string $message)
    {
        parent::__construct($message, $code);
    }
}
