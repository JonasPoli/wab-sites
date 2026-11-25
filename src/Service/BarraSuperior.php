<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Tenant;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Barra superior do site (change barra-superior-configuravel): faixa acima do cabeçalho,
 * com itens à esquerda e à direita, gravada em `navigationSettings`:
 *   topBarEnabled, topBarBg ('#rrggbb' ou ''), topBarLeft e topBarRight
 *   (listas de {type, text, icon, url, newTab}; no formato antigo, só o nome do tipo).
 *
 * `normalizarLado()` limpa o que vem do painel; `doSite()` resolve os itens com os dados
 * do site para o `themes/_topbar.html.twig` (função Twig `barra_superior()`).
 */
final class BarraSuperior implements ResetInterface
{
    public const TIPOS = ['phone', 'whatsapp', 'email', 'address', 'hours', 'social', 'custom'];
    public const MAXIMO_POR_LADO = 10;
    public const TEXTO_MAXIMO = 80;

    /** Tipos com texto próprio opcional. */
    private const COM_TEXTO = ['phone', 'whatsapp', 'email', 'address', 'custom'];

    /** @var array<int, ?array> */
    private array $cache = [];

    /**
     * @return list<array{type: string, text: string, icon: string, url: string, newTab: bool}>
     */
    public static function normalizarLado(mixed $itens): array
    {
        if (!is_array($itens)) {
            return [];
        }

        $saida = [];
        foreach ($itens as $item) {
            if (is_string($item)) {
                $item = ['type' => $item];
            }
            if (!is_array($item)) {
                continue;
            }
            $tipo = is_string($item['type'] ?? null) ? $item['type'] : '';
            if (!in_array($tipo, self::TIPOS, true)) {
                continue;
            }

            $texto = in_array($tipo, self::COM_TEXTO, true)
                ? mb_substr(trim((string) (is_scalar($item['text'] ?? null) ? $item['text'] : '')), 0, self::TEXTO_MAXIMO)
                : '';
            $icone = $tipo === 'custom' ? self::icone((string) (is_scalar($item['icon'] ?? null) ? $item['icon'] : '')) : '';
            $url = $tipo === 'custom' ? self::url((string) (is_scalar($item['url'] ?? null) ? $item['url'] : '')) : '';

            if ($tipo === 'custom' && $icone === '' && $texto === '') {
                continue;
            }

            $saida[] = [
                'type'   => $tipo,
                'text'   => $texto,
                'icon'   => $icone,
                'url'    => $url,
                'newTab' => $tipo === 'custom' && $url !== '' && !empty($item['newTab']),
            ];
            if (count($saida) >= self::MAXIMO_POR_LADO) {
                break;
            }
        }

        return $saida;
    }

    public static function normalizarCor(mixed $cor): string
    {
        $cor = strtolower(trim(is_string($cor) ? $cor : ''));

        return preg_match('~^#[0-9a-f]{6}$~', $cor) ? $cor : '';
    }

    /**
     * Lê o JSON do editor ({left: [...], right: [...]}).
     *
     * @return array{0: list<array>, 1: list<array>}
     */
    public static function lerJsonDoEditor(string $json): array
    {
        $dados = json_decode($json, true);
        if (!is_array($dados)) {
            return [[], []];
        }

        return [self::normalizarLado($dados['left'] ?? []), self::normalizarLado($dados['right'] ?? [])];
    }

    /**
     * Valores da barra a partir do POST do /admin/settings ou do /superadmin. Sem `topBarJson`
     * (outro formulário), cor e itens gravados continuam; a caixa `topBarEnabled` vale sempre.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $navAtual
     *
     * @return array{0: bool, 1: string, 2: list<array>, 3: list<array>}
     */
    public static function doFormulario(array $post, array $navAtual): array
    {
        $ligada = !empty($post['topBarEnabled']);
        if (!isset($post['topBarJson']) || !is_string($post['topBarJson'])) {
            return [
                $ligada,
                self::normalizarCor($navAtual['topBarBg'] ?? ''),
                self::normalizarLado($navAtual['topBarLeft'] ?? []),
                self::normalizarLado($navAtual['topBarRight'] ?? []),
            ];
        }
        [$esquerda, $direita] = self::lerJsonDoEditor($post['topBarJson']);

        return [$ligada, self::normalizarCor($post['topBarBg'] ?? ''), $esquerda, $direita];
    }

    /**
     * null com a barra desligada; senão o fundo escolhido ('' = cor primária do site)
     * e os itens resolvidos de cada lado (os sem dado no site ficam de fora).
     *
     * @return array{fundo: string, esquerda: list<array>, direita: list<array>}|null
     */
    public function doSite(?Tenant $tenant): ?array
    {
        if ($tenant === null) {
            return null;
        }
        $id = $tenant->getId() ?? 0;
        if (!array_key_exists($id, $this->cache)) {
            $this->cache[$id] = self::resolverSite($tenant);
        }

        return $this->cache[$id];
    }

    /** @return array{fundo: string, esquerda: list<array>, direita: list<array>}|null */
    public static function resolverSite(Tenant $tenant): ?array
    {
        $nav = $tenant->getNavigationSettings() ?? [];
        if (empty($nav['topBarEnabled'])) {
            return null;
        }

        $lado = static fn (mixed $itens): array => array_values(array_filter(array_map(
            static fn (array $item): ?array => self::resolverItem($item, $tenant),
            self::normalizarLado($itens),
        )));

        return [
            'fundo'    => self::normalizarCor($nav['topBarBg'] ?? ''),
            'esquerda' => $lado($nav['topBarLeft'] ?? []),
            'direita'  => $lado($nav['topBarRight'] ?? []),
        ];
    }

    /**
     * @param array{type: string, text: string, icon: string, url: string, newTab: bool} $item
     *
     * @return array{tipo: string, texto: string, url: string, novaAba: bool, titulo: string, icone: string, redes: list<array{rede: string, url: string}>}|null
     */
    public static function resolverItem(array $item, Tenant $tenant): ?array
    {
        $base = ['tipo' => $item['type'], 'texto' => '', 'url' => '', 'novaAba' => false, 'titulo' => '', 'icone' => '', 'redes' => []];
        $proprio = $item['text'];

        switch ($item['type']) {
            case 'phone':
                $fone = trim((string) $tenant->getPhone());
                if ($fone === '') {
                    return null;
                }

                return ['texto' => $proprio ?: $fone, 'url' => 'tel:' . str_replace([' ', '-', '(', ')'], '', $fone), 'titulo' => $fone] + $base;

            case 'whatsapp':
                $link = trim((string) $tenant->getWhatsappLink());
                if ($link === '') {
                    return null;
                }
                $numero = self::numeroDoWhatsapp($link);

                return ['texto' => $proprio ?: ($numero ?? 'WhatsApp'), 'url' => $link, 'novaAba' => true, 'titulo' => $numero ?? 'WhatsApp'] + $base;

            case 'email':
                $email = trim((string) $tenant->getContactEmail());
                if ($email === '') {
                    return null;
                }

                return ['texto' => $proprio ?: $email, 'url' => 'mailto:' . $email, 'titulo' => $email] + $base;

            case 'address':
                $endereco = trim((string) $tenant->getAddress());
                if ($endereco === '') {
                    return null;
                }

                return [
                    'texto'   => $proprio ?: $endereco,
                    'url'     => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($endereco),
                    'novaAba' => true,
                    'titulo'  => $endereco,
                ] + $base;

            case 'hours':
                $partes = [];
                foreach ($tenant->getOpeningHours() ?? [] as $h) {
                    $partes[] = trim(($h['days'] ?? '') . ' ' . ($h['hours'] ?? ''));
                }
                $horario = implode(' – ', array_filter($partes, static fn (string $p) => $p !== ''));

                return $horario === '' ? null : ['texto' => $horario, 'titulo' => $horario] + $base;

            case 'social':
                $redes = [];
                foreach ([
                    'whatsapp'  => $tenant->getWhatsappLink(),
                    'instagram' => $tenant->getInstagramLink(),
                    'facebook'  => $tenant->getFacebookLink(),
                    'youtube'   => $tenant->getYoutubeLink(),
                    'linkedin'  => $tenant->getLinkedinLink(),
                ] as $rede => $url) {
                    if (trim((string) $url) !== '') {
                        $redes[] = ['rede' => $rede, 'url' => trim((string) $url)];
                    }
                }

                return $redes === [] ? null : ['redes' => $redes] + $base;

            case 'custom':
                return [
                    'texto'   => $proprio,
                    'url'     => $item['url'],
                    'novaAba' => $item['newTab'],
                    'titulo'  => $proprio,
                    'icone'   => $item['icon'],
                ] + $base;
        }

        return null;
    }

    /** "(16) 99649-4545" a partir de https://wa.me/5516996494545; null quando não reconhece. */
    public static function numeroDoWhatsapp(string $link): ?string
    {
        if (preg_match('~(?:wa\.me/|phone=)\+?(\d{10,13})~', $link, $m) !== 1) {
            return null;
        }
        $d = $m[1];
        if (strlen($d) >= 12 && str_starts_with($d, '55')) {
            $d = substr($d, 2);
        }
        if (strlen($d) === 11) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
        }
        if (strlen($d) === 10) {
            return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
        }

        return null;
    }

    private static function icone(string $icone): string
    {
        $icone = preg_replace('~\s+~', ' ', trim($icone)) ?? '';

        return (mb_strlen($icone) <= 80 && preg_match('~^[a-z0-9 -]*$~', $icone)) ? $icone : '';
    }

    private static function url(string $url): string
    {
        $url = mb_substr(trim($url), 0, 500);
        if ($url !== '' && !preg_match('~^(https?://|//|mailto:|tel:|/|#)~i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
