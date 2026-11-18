<?php

namespace App\Tests\Twig;

use App\Twig\TenantExtension;
use PHPUnit\Framework\TestCase;

/** Filtro `youtube_id` do bloco de vídeo (blocos-novos-do-construtor). */
final class YoutubeIdTest extends TestCase
{
    public static function links(): array
    {
        return [
            'watch'           => ['https://www.youtube.com/watch?v=7-y0py3nPrQ', '7-y0py3nPrQ'],
            'watch com tempo' => ['https://youtube.com/watch?feature=share&v=7-y0py3nPrQ&t=10', '7-y0py3nPrQ'],
            'curto'           => ['https://youtu.be/JCvNa5kAlEo?si=abc', 'JCvNa5kAlEo'],
            'shorts'          => ['https://www.youtube.com/shorts/7-y0py3nPrQ', '7-y0py3nPrQ'],
            'embed'           => ['https://www.youtube-nocookie.com/embed/50Z3dBqkmTA', '50Z3dBqkmTA'],
            'outro site'      => ['https://vimeo.com/123456789', null],
            'id curto'        => ['https://www.youtube.com/watch?v=abc', null],
            'vazio'           => ['', null],
            'nulo'            => [null, null],
        ];
    }

    /** @dataProvider links */
    public function testIdDoVideo(?string $url, ?string $esperado): void
    {
        self::assertSame($esperado, TenantExtension::youtubeId($url));
    }
}
