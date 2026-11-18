<?php

namespace App\Service\Wordpress;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Leitura da API REST pública de um WordPress (/wp-json/wp/v2), página a página. */
final class ClienteWordpress
{
    private const UA = 'Mozilla/5.0 (compatible; importador-de-blog/1.0)';

    public function __construct(private readonly HttpClientInterface $http) {}

    /** @return list<array<string, mixed>> */
    public function categorias(string $base): array
    {
        return $this->todos($base, '/wp-json/wp/v2/categories', ['per_page' => 100]);
    }

    /** @return list<array<string, mixed>> posts publicados, com a imagem destacada embutida */
    public function posts(string $base): array
    {
        return $this->todos($base, '/wp-json/wp/v2/posts', ['per_page' => 50, '_embed' => 'wp:featuredmedia', 'status' => 'publish']);
    }

    public function baixar(string $url): ?string
    {
        try {
            $r = $this->http->request('GET', $url, ['headers' => ['User-Agent' => self::UA], 'timeout' => 30, 'max_redirects' => 3]);
            return $r->getStatusCode() === 200 ? $r->getContent() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<array<string, mixed>> */
    private function todos(string $base, string $caminho, array $consulta): array
    {
        $itens = [];
        $pagina = 1;
        do {
            $r = $this->http->request('GET', rtrim($base, '/') . $caminho, [
                'query' => $consulta + ['page' => $pagina],
                'headers' => ['User-Agent' => self::UA, 'Accept' => 'application/json'],
                'timeout' => 30,
            ]);
            if ($r->getStatusCode() !== 200) {
                throw new \RuntimeException(sprintf('A API do WordPress respondeu %d em %s (página %d).', $r->getStatusCode(), $caminho, $pagina));
            }
            $lote = $r->toArray();
            $itens = array_merge($itens, $lote);
            $total = (int) ($r->getHeaders()['x-wp-totalpages'][0] ?? 1);
            ++$pagina;
            usleep(300_000);
        } while ($pagina <= $total && $lote !== []);

        return $itens;
    }
}
