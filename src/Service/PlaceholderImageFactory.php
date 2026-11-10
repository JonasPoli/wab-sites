<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Imagens de conteúdo fictício: foto do picsum.photos (com semente, sempre a
 * mesma para a mesma semente) ou degradê gerado com GD. Grava em public/<dir>/<name>
 * e devolve o nome do arquivo, como o VichUploader guarda no banco.
 * Usado pelas fixtures do portal e pelo comando app:conteudo-exemplo.
 */
final class PlaceholderImageFactory
{
    private const PICSUM_URL = 'https://picsum.photos/seed/%s/%d/%d';

    private readonly Filesystem $fs;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly HttpClientInterface $httpClient,
    ) {
        $this->fs = new Filesystem();
    }

    /**
     * Baixa uma foto do picsum.photos. Devolve null (sem gravar nada) se a rede
     * falhar, passar do tempo-limite ou a resposta não for JPEG.
     */
    public function photo(string $dir, string $name, string $seed, int $w, int $h, float $timeout = 10.0, ?string $urlPattern = null): ?string
    {
        $url = sprintf($urlPattern ?? self::PICSUM_URL, rawurlencode($seed), $w, $h);
        try {
            $response = $this->httpClient->request('GET', $url, ['timeout' => $timeout, 'max_duration' => $timeout, 'max_redirects' => 3]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $content = $response->getContent();
        } catch (\Throwable) {
            return null;
        }
        // JPEG começa com FF D8 FF
        if (strlen($content) < 1024 || !str_starts_with($content, "\xFF\xD8\xFF")) {
            return null;
        }
        $path = $this->path($dir, $name);
        $this->fs->dumpFile($path, $content);
        return $name;
    }

    /**
     * Foto do picsum.photos e, se ela falhar, o degradê. $usedPhoto diz qual veio.
     * Devolve null só quando os dois falham (sem rede e sem GD).
     *
     * @param array{int, int, int} $from
     * @param array{int, int, int} $to
     */
    public function photoOrGradient(string $dir, string $name, string $seed, int $w, int $h, array $from, array $to, ?bool &$usedPhoto = null, ?string $urlPattern = null): ?string
    {
        $file = $this->photo($dir, $name, $seed, $w, $h, 10.0, $urlPattern);
        $usedPhoto = $file !== null;
        return $file ?? $this->gradient($dir, $name, $w, $h, $from, $to);
    }

    /**
     * Degradê horizontal de $from para $to (RGB 0-255) com um círculo claro.
     * Devolve null quando o PHP não tem GD.
     *
     * @param array{int, int, int} $from
     * @param array{int, int, int} $to
     */
    public function gradient(string $dir, string $name, int $w, int $h, array $from, array $to): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }
        $path = $this->path($dir, $name);
        $this->fs->mkdir(dirname($path));
        $img = imagecreatetruecolor($w, $h);
        for ($x = 0; $x < $w; $x++) {
            $t = $x / max(1, $w - 1);
            $color = imagecolorallocate(
                $img,
                (int) ($from[0] + ($to[0] - $from[0]) * $t),
                (int) ($from[1] + ($to[1] - $from[1]) * $t),
                (int) ($from[2] + ($to[2] - $from[2]) * $t),
            );
            imageline($img, $x, 0, $x, $h, $color);
        }
        // Um círculo claro para dar forma à imagem
        $white = imagecolorallocatealpha($img, 255, 255, 255, 90);
        imagefilledellipse($img, (int) ($w * 0.7), (int) ($h * 0.45), (int) ($h * 0.6), (int) ($h * 0.6), $white);
        imagejpeg($img, $path, 82);
        return $name;
    }

    /** Caminho absoluto de public/<dir>/<name>. */
    public function path(string $dir, string $name): string
    {
        return $this->projectDir . '/public/' . trim($dir, '/') . '/' . $name;
    }
}
