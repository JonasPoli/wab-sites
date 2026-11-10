<?php

namespace App\Service;

use App\Entity\Tenant;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Confere no Google o token do reCAPTCHA v3 do formulário de contato, com as chaves do
 * tenant do domínio (nunca de outro site). ACCEPTED: token válido, ação certa e nota mínima.
 * REJECTED: token vazio, recusado, de outra ação ou com nota baixa. UNAVAILABLE: o Google
 * não deu resposta válida (rede, HTTP, JSON, secret key errada): o contato segue como antes.
 * O log nunca leva nome, e-mail, telefone nem mensagem do visitante.
 */
final class RecaptchaVerifier
{
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const UNAVAILABLE = 'unavailable';

    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const REJECT_CODES = ['invalid-input-response', 'timeout-or-duplicate', 'missing-input-response'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.recaptcha.score_min%')]
        private readonly float $scoreMin = 0.5,
    ) {
    }

    public function verify(Tenant $tenant, ?string $token, string $expectedAction, ?string $ip): string
    {
        $domain = (string) $tenant->getDomain();
        $token = trim((string) $token);
        if ($token === '') {
            $this->logger->warning('reCAPTCHA: contato recusado sem token.', ['domain' => $domain, 'action' => $expectedAction, 'ip' => $ip]);
            return self::REJECTED;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => array_filter(['secret' => (string) $tenant->getRecaptchaSecretKey(), 'response' => $token, 'remoteip' => $ip]),
                'timeout' => 5,
                'max_duration' => 5,
            ]);
            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('reCAPTCHA indisponível: HTTP ' . $response->getStatusCode() . '.', ['domain' => $domain]);
                return self::UNAVAILABLE;
            }
            $data = json_decode($response->getContent(false), true);
        } catch (\Throwable $e) {
            $this->logger->warning('reCAPTCHA indisponível: ' . $e->getMessage(), ['domain' => $domain]);
            return self::UNAVAILABLE;
        }

        if (!is_array($data) || !array_key_exists('success', $data)) {
            $this->logger->warning('reCAPTCHA indisponível: resposta que não é JSON.', ['domain' => $domain]);
            return self::UNAVAILABLE;
        }

        $codes = array_values(array_filter((array) ($data['error-codes'] ?? []), 'is_string'));
        $score = isset($data['score']) ? (float) $data['score'] : null;
        $action = isset($data['action']) ? (string) $data['action'] : null;
        $context = ['domain' => $domain, 'action' => $action, 'expected' => $expectedAction, 'score' => $score, 'codes' => $codes, 'ip' => $ip];

        if (!$data['success']) {
            if (array_intersect($codes, self::REJECT_CODES)) {
                $this->logger->warning('reCAPTCHA: contato recusado (token inválido ou repetido).', $context);
                return self::REJECTED;
            }
            // invalid-input-secret, missing-input-secret, bad-request…: problema da chave ou do pedido
            $this->logger->warning('reCAPTCHA indisponível: o Google recusou o pedido (confira a secret key do site).', $context);
            return self::UNAVAILABLE;
        }

        if ($action !== $expectedAction) {
            $this->logger->warning('reCAPTCHA: contato recusado (ação diferente).', $context);
            return self::REJECTED;
        }
        if ($score === null || $score < $this->scoreMin) {
            $this->logger->warning('reCAPTCHA: contato recusado (nota baixa).', $context + ['min' => $this->scoreMin]);
            return self::REJECTED;
        }

        return self::ACCEPTED;
    }
}
