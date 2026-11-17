<?php

declare(strict_types=1);

/*
 * Compara as medições do sh/medir-pagespeed.sh e imprime a tabela de notas e as
 * auditorias reprovadas por domínio.
 *
 *   php sh/comparar-pagespeed.php              # só o "antes"
 *   php sh/comparar-pagespeed.php antes depois # as duas, lado a lado
 */

$pasta = __DIR__;
$prefixos = array_slice($argv, 1);
if ($prefixos === []) {
    $prefixos = ['antes'];
}

const CATEGORIAS = ['performance', 'accessibility', 'best-practices', 'seo'];

/** @return array<string, array<string, array<string, int>>> domínio => formato => categoria => nota */
function ler(string $pasta, string $prefixo): array
{
    $notas = [];
    foreach (glob($pasta . '/' . $prefixo . '-*.json') ?: [] as $arquivo) {
        $base = basename($arquivo, '.json');
        $resto = substr($base, strlen($prefixo) + 1);
        $corte = strrpos($resto, '-');
        if ($corte === false) {
            continue;
        }
        $dominio = substr($resto, 0, $corte);
        $formato = substr($resto, $corte + 1);

        $dados = json_decode((string) file_get_contents($arquivo), true);
        if (!is_array($dados) || !isset($dados['categories'])) {
            continue;
        }
        foreach (CATEGORIAS as $categoria) {
            $nota = $dados['categories'][$categoria]['score'] ?? null;
            $notas[$dominio][$formato][$categoria] = (int) round(((float) $nota) * 100);
        }
    }
    ksort($notas);

    return $notas;
}

/** @return array<string, list<string>> auditoria => execuções que reprovaram */
function reprovadas(string $pasta, string $prefixo): array
{
    $falhas = [];
    foreach (glob($pasta . '/' . $prefixo . '-*.json') ?: [] as $arquivo) {
        $dados = json_decode((string) file_get_contents($arquivo), true);
        if (!is_array($dados) || !isset($dados['audits'])) {
            continue;
        }
        $rotulo = str_replace($prefixo . '-', '', basename($arquivo, '.json'));
        foreach ($dados['categories'] as $categoria) {
            foreach ($categoria['auditRefs'] ?? [] as $ref) {
                $auditoria = $dados['audits'][$ref['id']] ?? null;
                if ($auditoria === null) {
                    continue;
                }
                if (in_array($auditoria['scoreDisplayMode'] ?? '', ['notApplicable', 'manual', 'informative'], true)) {
                    continue;
                }
                if (($auditoria['score'] ?? null) !== null && $auditoria['score'] < 1) {
                    $falhas[$ref['id']][] = $rotulo;
                }
            }
        }
    }
    uasort($falhas, static fn (array $a, array $b) => count($b) <=> count($a));

    return $falhas;
}

foreach ($prefixos as $prefixo) {
    $notas = ler($pasta, $prefixo);
    if ($notas === []) {
        echo "Nenhuma medição com o prefixo \"$prefixo\".\n";
        continue;
    }

    echo "\n=== $prefixo — desempenho / acessibilidade / práticas / SEO ===\n\n";
    printf("%-36s %-26s %s\n", 'domínio', 'celular', 'computador');

    $tudo100 = true;
    foreach ($notas as $dominio => $formatos) {
        $coluna = static function (?array $n) use (&$tudo100): string {
            if ($n === null) {
                return '  (sem dado)';
            }
            foreach ($n as $nota) {
                if ($nota < 100) {
                    $tudo100 = false;
                }
            }

            return sprintf('  %3d /%3d /%3d /%3d', $n['performance'], $n['accessibility'], $n['best-practices'], $n['seo']);
        };
        printf("%-36s %-26s %s\n", $dominio, $coluna($formatos['mobile'] ?? null), $coluna($formatos['desktop'] ?? null));
    }

    echo "\n", $tudo100 ? "TODOS OS DOMÍNIOS EM 100 NAS QUATRO CATEGORIAS.\n" : "Ainda há nota abaixo de 100.\n";

    $falhas = reprovadas($pasta, $prefixo);
    if ($falhas !== []) {
        echo "\nAuditorias reprovadas:\n";
        foreach ($falhas as $id => $execucoes) {
            printf("  %-42s %d execução(ões)\n", $id, count($execucoes));
        }
    }
}

echo "\n";
