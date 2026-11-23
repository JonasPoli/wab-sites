<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Dados do bloco "Mapa de representantes" (`representatives`, change bloco-mapa-de-representantes):
 * normaliza o `config` que vem do painel, igual na gravação e na prévia.
 *
 *   config = { emptyMessage: ?string, reps: [{uf, image: ?string, html: ?string}] }
 *
 * A lista chega como JSON em `config[repsJson]` (um campo só: não esbarra no max_input_vars).
 * O texto de cada registro é HTML do editor do painel, limpo pelo mesmo sanitizador das
 * notícias; o único título permitido é o h3.
 */
final class MapaDeRepresentantes
{
    public const MAX_REGISTROS = 1000;

    /** Sigla => nome, na ordem alfabética do nome. */
    public const ESTADOS = [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará',
        'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
        'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];

    public function __construct(private readonly ArticleBodySanitizer $sanitizador) {}

    /** @param array<mixed> $cfg */
    public function normalizar(array $cfg): array
    {
        $brutos = $cfg['reps'] ?? [];
        if (isset($cfg['repsJson']) && is_string($cfg['repsJson'])) {
            try {
                $brutos = json_decode($cfg['repsJson'], true, 16, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $brutos = [];
            }
        }

        $registros = [];
        foreach (is_array($brutos) ? $brutos : [] as $r) {
            if (count($registros) >= self::MAX_REGISTROS) {
                break;
            }
            if (!is_array($r)) {
                continue;
            }
            $uf = strtoupper(trim((string) ($r['uf'] ?? '')));
            if (!isset(self::ESTADOS[$uf])) {
                continue;
            }
            $imagem = MidiaDoBloco::nomeValido($r['image'] ?? null);
            $html = $this->html(is_string($r['html'] ?? null) ? $r['html'] : null);
            if ($html === null && $imagem === null) {
                continue;
            }
            $registros[] = ['uf' => $uf, 'image' => $imagem, 'html' => $html];
        }

        $saida = ['reps' => $registros];
        $msg = trim(str_replace(["\r\n", "\r"], "\n", (string) ($cfg['emptyMessage'] ?? '')));
        if ($msg !== '') {
            $saida['emptyMessage'] = mb_substr($msg, 0, 2000);
        }

        return $saida;
    }

    /** HTML limpo; todo título vira h3. */
    public function html(?string $html): ?string
    {
        $limpo = $this->sanitizador->sanitize($html);
        if ($limpo === null) {
            return null;
        }

        return preg_replace(['~<h2(\s[^>]*)?>~i', '~</h2\s*>~i'], ['<h3$1>', '</h3>'], $limpo);
    }

    /**
     * Registros agrupados por UF, na ordem alfabética do estado e, dentro dele, na ordem do admin.
     *
     * @return array<string, list<array>>
     */
    public static function porEstado(array $registros): array
    {
        $grupos = [];
        foreach (array_keys(self::ESTADOS) as $uf) {
            foreach ($registros as $r) {
                if (($r['uf'] ?? null) === $uf) {
                    $grupos[$uf][] = $r;
                }
            }
        }

        return $grupos;
    }

    /** Registros de exemplo da prévia, quando o bloco ainda não tem nenhum. */
    public function exemplos(): array
    {
        $texto = static fn (string $nome, string $cidade) => '<h3>' . $nome . '</h3><p>Cidade: ' . $cidade
            . '<br>Fone: <a href="tel:+551100000000">(11) 0000-0000</a><br>E-mail: <a href="mailto:contato@exemplo.com.br">contato@exemplo.com.br</a></p>';

        return [
            ['uf' => 'AC', 'image' => null, 'html' => $texto('Representante de exemplo', 'Rio Branco – AC')],
            ['uf' => 'BA', 'image' => null, 'html' => $texto('Representante de exemplo', 'Salvador – BA')],
            ['uf' => 'BA', 'image' => null, 'html' => $texto('Outro representante', 'Vitória da Conquista – BA')],
            ['uf' => 'RS', 'image' => null, 'html' => $texto('Representante de exemplo', 'Porto Alegre – RS')],
        ];
    }
}
