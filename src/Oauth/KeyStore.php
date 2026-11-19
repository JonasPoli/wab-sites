<?php

namespace App\Oauth;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Par de chaves RSA que assina os tokens (RS256).
 *
 * Gerado no primeiro uso em var/oauth/<ambiente>/, fora do git e preservado entre
 * deploys. Se a chave for apagada, uma nova é gerada e os tokens em circulação deixam
 * de valer: os agentes simplesmente se registram de novo (design D1).
 */
final class KeyStore
{
    private ?\OpenSSLAsymmetricKey $privateKey = null;
    private ?array $publicJwk = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%/var/oauth/%kernel.environment%')] private readonly string $directory,
    ) {
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function privateKey(): \OpenSSLAsymmetricKey
    {
        return $this->privateKey ??= $this->load();
    }

    /** Chave pública no formato JWK (RFC 7517), com kid = impressão digital RFC 7638. */
    public function publicJwk(): array
    {
        if (null === $this->publicJwk) {
            $rsa = openssl_pkey_get_details($this->privateKey())['rsa'];
            $n = Base64Url::encode($rsa['n']);
            $e = Base64Url::encode($rsa['e']);
            $thumbprint = Base64Url::encode(hash('sha256', '{"e":"'.$e.'","kty":"RSA","n":"'.$n.'"}', true));
            $this->publicJwk = ['kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => $thumbprint, 'n' => $n, 'e' => $e];
        }

        return $this->publicJwk;
    }

    public function kid(): string
    {
        return $this->publicJwk()['kid'];
    }

    public function jwks(): array
    {
        return ['keys' => [$this->publicJwk()]];
    }

    public function publicKey(): \OpenSSLAsymmetricKey
    {
        return openssl_pkey_get_public(openssl_pkey_get_details($this->privateKey())['key']);
    }

    private function load(): \OpenSSLAsymmetricKey
    {
        $file = $this->directory.'/private.pem';
        if (!is_file($file)) {
            $this->generate($file);
        }

        $key = openssl_pkey_get_private((string) file_get_contents($file));
        if (false === $key) {
            throw new \RuntimeException('Chave privada OAuth ilegível: '.$file);
        }

        return $key;
    }

    /** Gera a chave sob trava, para dois pedidos simultâneos não criarem chaves diferentes. */
    private function generate(string $file): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Não foi possível criar '.$this->directory);
        }

        $lock = fopen($this->directory.'/.lock', 'c');
        flock($lock, \LOCK_EX);
        try {
            if (is_file($file)) {
                return;
            }
            $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => \OPENSSL_KEYTYPE_RSA]);
            openssl_pkey_export($key, $pem);
            $tmp = $file.'.'.bin2hex(random_bytes(4));
            file_put_contents($tmp, $pem);
            chmod($tmp, 0600);
            rename($tmp, $file);
        } finally {
            flock($lock, \LOCK_UN);
            fclose($lock);
        }
    }
}
