<?php

namespace App\Twig;

use App\Service\ImageFormats;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * {{ 'uploads/article/x.jpg'|news_image('news_thumb') }}: a URL da miniatura do LiipImagine
 * quando o servidor consegue gerá-la; senão (ex.: AVIF, que o GD do servidor não lê),
 * a URL do arquivo original, que o CSS do tema recorta no mesmo tamanho.
 */
final class NewsImageExtension extends AbstractExtension
{
    /** @var array<string, bool> */
    private array $canThumb = [];

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly Packages $packages,
        private readonly ImageFormats $imageFormats,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[\Override]
    public function getFilters(): array
    {
        return [new TwigFilter('news_image', $this->newsImage(...))];
    }

    public function newsImage(string $path, string $filter): string
    {
        $path = ltrim($path, '/');
        $this->canThumb[$path] ??= $this->imageFormats->canThumbnail($this->projectDir . '/public/' . $path);
        return $this->canThumb[$path]
            ? $this->cacheManager->getBrowserPath($path, $filter)
            : $this->packages->getUrl($path);
    }
}
