<?php

namespace App\Oauth;

/**
 * Erro OAuth no formato da RFC 6749 §5.2: código (invalid_request, invalid_grant…),
 * descrição em português e status HTTP.
 */
final class OauthException extends \RuntimeException
{
    public function __construct(
        public readonly string $error,
        string $description,
        public readonly int $status = 400,
    ) {
        parent::__construct($description);
    }

    public function toArray(): array
    {
        return ['error' => $this->error, 'error_description' => $this->getMessage()];
    }
}
