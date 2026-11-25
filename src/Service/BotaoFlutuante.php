<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant;
use App\Repository\PageRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Botão flutuante do site (change botao-flutuante-do-site): diz se o site aberto mostra
 * o botão do WhatsApp, o de formulário ou nenhum, a partir de `Tenant::getFloatingButton()`.
 *
 * A página de destino do formulário é buscada com o tenant explícito: um id de página de
 * outro site, ou de página excluída, não acha nada e o botão não aparece.
 */
final class BotaoFlutuante implements ResetInterface
{
    public const TEXTO_MAXIMO = 40;

    /** @var array<int, ?array> resultado por tenant, nesta requisição */
    private array $cache = [];

    public function __construct(
        private readonly PageRepository $paginas,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    /**
     * null quando o site não mostra botão; senão
     *   ['tipo' => 'whatsapp', 'url' => …]
     *   ['tipo' => 'form', 'url' => …, 'texto' => …, 'icone' => …, 'paginaId' => …]
     *
     * @return array{tipo: string, url: string, texto?: string, icone?: string, paginaId?: int}|null
     */
    public function doSite(?Tenant $tenant): ?array
    {
        if ($tenant === null) {
            return null;
        }
        $id = $tenant->getId() ?? 0;
        if (!array_key_exists($id, $this->cache)) {
            $this->cache[$id] = $this->resolver($tenant);
        }

        return $this->cache[$id];
    }

    private function resolver(Tenant $tenant): ?array
    {
        $fb = $tenant->getFloatingButton();

        if ($fb['mode'] === 'whatsapp') {
            $link = trim((string) $tenant->getWhatsappLink());

            return $link !== '' ? ['tipo' => 'whatsapp', 'url' => $link] : null;
        }

        if ($fb['mode'] !== 'form') {
            return null;
        }

        $texto = trim($fb['label']);
        if ($texto === '' || $fb['pageId'] === null || $tenant->getId() === null) {
            return null;
        }

        $pagina = $this->paginas->findOneBy(['id' => $fb['pageId'], 'tenant' => $tenant]);
        if ($pagina === null) {
            return null;
        }

        $url = $tenant->getHomePage()?->getId() === $pagina->getId()
            ? $this->urls->generate('pub_home')
            : $this->urls->generate('pub_page_show', ['slug' => $pagina->getSlug()]);

        return [
            'tipo'     => 'form',
            'url'      => $url,
            'texto'    => $texto,
            'icone'    => trim($fb['icon']),
            'paginaId' => (int) $pagina->getId(),
        ];
    }

    /**
     * Normaliza o que vem do formulário do /superadmin (o pageId já conferido pelo chamador).
     *
     * @return array{mode: string, pageId: ?int, label: string, icon: string}
     */
    public static function normalizar(string $modo, ?int $paginaId, string $texto, string $icone): array
    {
        $icone = trim($icone);
        if (mb_strlen($icone) > 80 || !preg_match('~^[a-z0-9 -]*$~', $icone)) {
            $icone = '';
        }

        return [
            'mode'   => in_array($modo, Tenant::FLOATING_BUTTON_MODES, true) ? $modo : 'whatsapp',
            'pageId' => $paginaId,
            'label'  => mb_substr(trim($texto), 0, self::TEXTO_MAXIMO),
            'icon'   => preg_replace('~\s+~', ' ', $icone),
        ];
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
