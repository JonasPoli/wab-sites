<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Lê o formulário de notícia (painel e área do jornalista) para a entidade.
 *
 * Isolamento: editoria, outras categorias e autor são carregados pelos
 * repositórios filtrados pelo tenant do domínio; um id de outro site some.
 * O tenant da notícia é definido pelo controller a partir do TenantContext.
 */
class ArticleFormHandler
{
    public const TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly CategoryRepository $categories,
        private readonly AuthorRepository $authors,
        private readonly ArticleBodySanitizer $sanitizer,
        private readonly SluggerInterface $slugger,
        private readonly ImageFormats $imageFormats,
    ) {}

    /**
     * Aplica o POST na notícia e devolve a lista de erros (vazia = pode gravar).
     *
     * @param bool        $staff       true no painel (/admin): escolhe autor, slug e "Leia também"
     * @param Author|null $forceAuthor na área do jornalista, o autor é sempre ele
     *
     * @return string[]
     */
    public function apply(Article $article, Request $r, bool $staff, ?Author $forceAuthor = null): array
    {
        $errors = [];
        $in = $r->request;

        $article->setTitle(trim((string) $in->get('title')));
        if ($article->getTitle() === '') {
            $errors[] = 'O título é obrigatório.';
        }

        $article->setKicker($this->nullable($in->get('kicker')));
        $article->setSummary($this->nullable($in->get('summary')));
        $article->setBody($this->sanitizer->sanitize((string) $in->get('body')));

        // Editoria principal e outras categorias (sempre do site do domínio)
        $principalId = (int) $in->get('categoryId');
        $principal = $principalId ? $this->categories->find($principalId) : null;
        $otherIds = array_filter(array_map('intval', $in->all('categoryIds')));
        $others = $otherIds ? $this->categories->findBy(['id' => $otherIds]) : [];
        $others = array_values(array_filter($others, fn (Category $c) => $c !== $principal));
        if ($others && !$principal) {
            $errors[] = 'Escolha a editoria principal.';
        }
        $article->setCategory($principal);
        $article->setCategories($others);

        // Autor
        if ($forceAuthor) {
            $article->setAuthor($forceAuthor);
        } elseif ($staff) {
            $authorId = (int) $in->get('authorId');
            $current = $article->getAuthor();
            if (!$authorId) {
                $article->setAuthor(null);
            } elseif ($current && $current->getId() === $authorId) {
                // mantém o autor atual mesmo que ele tenha sido suspenso
            } else {
                $article->setAuthor($this->authors->findOneApproved($authorId));
            }
        }

        // Situação e data/hora (formulário em horário de Brasília)
        $article->setStatus(ArticleStatus::tryFrom((string) $in->get('status')) ?? ArticleStatus::Draft);
        $article->setPublishedAt($this->parseLocalDateTime((string) $in->get('publishedAt')) ?? new \DateTimeImmutable());

        // Capa
        $article->setCoverCaption($this->nullable($in->get('coverCaption')));
        $article->setCoverCredit($this->nullable($in->get('coverCredit')));
        $file = $r->files->get('coverImageFile');
        if ($file instanceof UploadedFile && $file->isValid()) {
            // Só JPG e PNG: o servidor não gera miniatura de AVIF (e o Jonas limitou a esses dois)
            if ($this->imageFormats->acceptsUpload($file)) {
                $article->setCoverImageFile($file);
            } else {
                $errors[] = ImageFormats::UPLOAD_MESSAGE;
            }
        }

        $article->setRelatedLinks($this->normalizeLinks($in->all('relatedLinks')));

        // Vídeo do YouTube: gravado num formato só; vazio tira o vídeo
        $video = trim((string) $in->get('videoUrl'));
        if ($video === '') {
            $article->setVideoUrl(null);
        } elseif ($id = Article::youtubeId($video)) {
            $article->setVideoUrl('https://www.youtube.com/watch?v=' . $id);
        } else {
            $errors[] = 'Informe um link de vídeo do YouTube (youtube.com ou youtu.be).';
        }

        if ($staff) {
            $article->setFeatured((bool) $in->get('featured'));
        }

        // Slug: único no site; a equipe pode digitar, o jornalista não
        $wanted = $staff ? trim((string) $in->get('slug')) : '';
        if ($wanted === '' && $article->getId() && $article->getSlug() !== '') {
            $wanted = $article->getSlug();
        }
        $article->setSlug($this->uniqueSlug($wanted !== '' ? $wanted : $article->getTitle(), $article->getId()));

        $article->setUpdatedAt(new \DateTimeImmutable());

        return $errors;
    }

    /**
     * Rótulo e URL obrigatórios; até 3; acrescenta https:// à URL sem esquema.
     *
     * @return list<array{label: string, url: string}>
     */
    public function normalizeLinks(mixed $raw): array
    {
        $links = [];
        foreach (is_array($raw) ? $raw : [] as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));
            if ($label === '' || $url === '') {
                continue;
            }
            if (!preg_match('~^(https?://|//|/)~i', $url)) {
                $url = 'https://' . $url;
            }
            $links[] = ['label' => $label, 'url' => $url];
            if (count($links) === Article::MAX_RELATED_LINKS) {
                break;
            }
        }
        return $links;
    }

    public function uniqueSlug(string $text, ?int $ignoreId): string
    {
        $base = strtolower((string) $this->slugger->slug($text)) ?: 'noticia';
        $slug = $base;
        $n = 2;
        while ($this->articles->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /** "2026-11-09T19:02" em horário de Brasília -> instante no fuso padrão do PHP. */
    public function parseLocalDateTime(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $dt = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new \DateTimeZone(self::TIMEZONE))
            ?: \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s', $value, new \DateTimeZone(self::TIMEZONE));
        return $dt ? $dt->setTimezone(new \DateTimeZone(date_default_timezone_get())) : null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
