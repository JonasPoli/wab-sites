<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Dados do bloco Sanfona (`accordion`, change bloco-sanfona): normaliza o `config` que vem
 * do formulário do painel, igual na gravação e na prévia.
 *
 *   config = {
 *     firstOpen: '1'|ausente, multiple: '1'|ausente,
 *     panels: [{title, kind: 'text'|'table', html: ?string, table: {header: bool, rows: (string|{text, image})[][]}}]
 *   }
 *
 *   Célula só de texto é string; célula com imagem (uploads/page_block_media, change
 *   imagem-na-celula-da-sanfona) é {text, image}.
 *
 * A tabela chega como JSON num campo só por item (as células não têm `name` no formulário),
 * para uma tabela grande não passar do `max_input_vars` do PHP. `html` e `table` ficam os
 * dois gravados, qualquer que seja o `kind`: trocar o tipo do item e voltar não perde nada.
 */
final class SanfonaDoBloco
{
    public const MAX_COLUNAS = 12;
    public const MAX_LINHAS = 500;
    public const MAX_CELULA = 2000;
    public const MAX_TITULO = 200;

    public function __construct(private readonly ArticleBodySanitizer $sanitizador) {}

    /** @param array<mixed> $cfg */
    public function normalizar(array $cfg): array
    {
        $itens = [];
        foreach ((array) ($cfg['panels'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $titulo = mb_substr(trim((string) ($item['title'] ?? '')), 0, self::MAX_TITULO);
            $html = $this->sanitizador->sanitize(is_string($item['html'] ?? null) ? $item['html'] : null);
            $tabela = $this->tabela($item['table'] ?? null);
            if ($titulo === '' && $html === null && $tabela['rows'] === []) {
                continue;
            }
            $itens[] = [
                'title' => $titulo,
                'kind'  => ($item['kind'] ?? '') === 'table' ? 'table' : 'text',
                'html'  => $html,
                'table' => $tabela,
            ];
        }

        $saida = ['panels' => $itens];
        foreach (['firstOpen', 'multiple'] as $opcao) {
            if (($cfg[$opcao] ?? '') === '1') {
                $saida[$opcao] = '1';
            }
        }

        return $saida;
    }

    /**
     * Tabela gravada: JSON `{header, rows}` (do formulário) ou o array já decodificado.
     *
     * @return array{header: bool, rows: list<list<string>>}
     */
    public function tabela(mixed $bruta): array
    {
        if (is_string($bruta)) {
            try {
                $bruta = json_decode($bruta, true, 8, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $bruta = null;
            }
        }
        if (!is_array($bruta)) {
            return ['header' => true, 'rows' => []];
        }

        $linhas = [];
        $largura = 0;
        foreach (array_values((array) ($bruta['rows'] ?? [])) as $linha) {
            if (count($linhas) >= self::MAX_LINHAS) {
                break;
            }
            if (!is_array($linha)) {
                continue;
            }
            $celulas = [];
            foreach (array_slice(array_values($linha), 0, self::MAX_COLUNAS) as $c) {
                // Célula com imagem chega como {text, image}; só de texto, como string
                $imagem = null;
                if (is_array($c)) {
                    $imagem = MidiaDoBloco::nomeValido($c['image'] ?? null);
                    $c = $c['text'] ?? '';
                }
                $texto = is_scalar($c) ? (string) $c : '';
                $texto = mb_substr(trim(str_replace(["\r\n", "\r"], "\n", $texto)), 0, self::MAX_CELULA);
                $celulas[] = $imagem !== null ? ['text' => $texto, 'image' => $imagem] : $texto;
            }
            if (array_filter($celulas, static fn ($c) => $c !== '') === []) {
                continue;
            }
            $linhas[] = $celulas;
            $largura = max($largura, count($celulas));
        }
        // Tira colunas vazias do fim (sobra comum de colar planilha) e iguala a largura
        while ($largura > 0 && array_reduce($linhas, static fn (bool $vazia, array $l) => $vazia && ($l[$largura - 1] ?? '') === '', true)) {
            $largura--;
        }
        foreach ($linhas as $i => $l) {
            $linhas[$i] = array_pad(array_slice($l, 0, $largura), $largura, '');
        }

        return ['header' => ($bruta['header'] ?? true) !== false && ($bruta['header'] ?? true) !== '0', 'rows' => $linhas];
    }

    /** Itens de exemplo da prévia, quando a sanfona ainda não tem item. */
    public function exemplos(): array
    {
        return [
            [
                'title' => 'Item de texto',
                'kind'  => 'text',
                'html'  => '<p>Texto do item, com <strong>negrito</strong>, <em>itálico</em> e <a href="#">link</a>.</p><ul><li>Primeiro tópico</li><li>Segundo tópico</li></ul>',
                'table' => ['header' => true, 'rows' => []],
            ],
            [
                'title' => 'Item de tabela',
                'kind'  => 'table',
                'html'  => null,
                'table' => ['header' => true, 'rows' => [
                    ['Coluna 1', 'Coluna 2', 'Coluna 3'],
                    ['Valor', 'Valor', 'Valor'],
                    ['Valor', 'Valor', 'Valor'],
                ]],
            ],
        ];
    }
}
