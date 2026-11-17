<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\HeadingFonts;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Baixa as fontes do catálogo para dentro do projeto e gera o CSS com os `@font-face`.
 *
 * Por que existe: o `tenant_css_vars()` emitia
 * `@import url('https://fonts.googleapis.com/css2?…')` dentro de um `<style>` em linha —
 * o pior caso possível, porque o navegador só descobre a fonte depois de ler o CSS, não
 * dá para pré-carregar e o texto troca de fonte depois da pintura. Servindo do próprio
 * domínio somem duas conexões para o Google e o texto nunca fica invisível.
 *
 *   php bin/console app:fontes:sincronizar
 *
 * Rodar de novo quando uma família nova entrar no catálogo (`HeadingFonts`).
 */
#[AsCommand(
    name: 'app:fontes:sincronizar',
    description: 'Baixa as fontes do catálogo e gera assets/styles/fontes.css.',
)]
final class SincronizarFontesCommand extends Command
{
    /** Navegador moderno: sem isto o Google devolve formatos antigos, bem maiores. */
    private const AGENTE = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /** Só o alfabeto que os sites usam; cortar os outros reduz muito o arquivo. */
    private const SUBCONJUNTOS = ['latin', 'latin-ext'];

    public function __construct(
        private readonly HttpClientInterface $http,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('refazer', null, InputOption::VALUE_NONE, 'Baixa de novo os arquivos que já existem.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $refazer = (bool) $input->getOption('refazer');

        $pastaFontes = $this->projectDir . '/assets/fonts';
        if (!is_dir($pastaFontes) && !mkdir($pastaFontes, 0775, true) && !is_dir($pastaFontes)) {
            $io->error('Não consegui criar assets/fonts.');

            return Command::FAILURE;
        }

        $familias = array_keys(HeadingFonts::choices());
        $regras = [];
        $baixados = 0;
        $reaproveitados = 0;

        foreach ($familias as $familia) {
            $io->writeln(sprintf('<info>%s</info>', $familia));

            $css = $this->cssDoGoogle($familia);
            if ($css === null) {
                $io->warning(sprintf('Não consegui baixar o CSS de %s.', $familia));
                continue;
            }

            foreach ($this->blocos($css) as $bloco) {
                $subconjunto = $bloco['subconjunto'];
                if ($subconjunto !== null && !in_array($subconjunto, self::SUBCONJUNTOS, true)) {
                    continue;
                }

                $nomeArquivo = $this->nomeDoArquivo($familia, $bloco);
                $destino = $pastaFontes . '/' . $nomeArquivo;

                if ($refazer || !is_file($destino)) {
                    $binario = $this->baixar($bloco['url']);
                    if ($binario === null) {
                        $io->warning('Falhou: ' . $bloco['url']);
                        continue;
                    }
                    file_put_contents($destino, $binario);
                    ++$baixados;
                } else {
                    ++$reaproveitados;
                }

                $regras[] = $this->regraFontFace($familia, $bloco, $nomeArquivo);
            }
        }

        if ($regras === []) {
            $io->error('Nenhuma fonte baixada: o CSS não ficou utilizável.');

            return Command::FAILURE;
        }

        $cabecalho = <<<CSS
            /*
             * Fontes do catálogo (App\\Service\\HeadingFonts) servidas pelo próprio domínio.
             *
             * GERADO por `php bin/console app:fontes:sincronizar` — não edite à mão.
             * `font-display: swap` garante que o texto apareça numa fonte de reserva
             * enquanto a definitiva carrega, em vez de ficar invisível.
             */

            CSS;

        file_put_contents(
            $this->projectDir . '/assets/styles/fontes.css',
            $cabecalho . "\n" . implode("\n", $regras) . "\n",
        );

        $io->success(sprintf(
            '%d arquivo(s) baixado(s), %d reaproveitado(s), %d regra(s) em assets/styles/fontes.css.',
            $baixados,
            $reaproveitados,
            count($regras),
        ));

        return Command::SUCCESS;
    }

    private function cssDoGoogle(string $familia): ?string
    {
        // Peso variável de 100 a 900: um arquivo por subconjunto atende todos os pesos
        $url = sprintf(
            'https://fonts.googleapis.com/css2?family=%s:wght@100..900&display=swap',
            rawurlencode($familia),
        );

        $css = $this->baixar($url);
        if ($css !== null) {
            return $css;
        }

        // Família sem eixo variável: tenta a lista de pesos fixos
        return $this->baixar(sprintf(
            'https://fonts.googleapis.com/css2?family=%s:wght@300;400;500;600;700;800&display=swap',
            rawurlencode($familia),
        ));
    }

    private function baixar(string $url): ?string
    {
        try {
            $resposta = $this->http->request('GET', $url, [
                'headers' => ['User-Agent' => self::AGENTE],
                'timeout' => 30,
            ]);

            return $resposta->getStatusCode() === 200 ? $resposta->getContent() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Cada `@font-face` do CSS do Google, com o subconjunto que o comentário anterior indica.
     *
     * @return list<array{subconjunto: ?string, estilo: string, peso: string, url: string, unicode: ?string}>
     */
    private function blocos(string $css): array
    {
        $blocos = [];
        $subconjunto = null;

        // O CSS vem como: /* latin */ @font-face { … }
        $pedacos = preg_split('~/\*\s*([a-z0-9-]+)\s*\*/~i', $css, -1, \PREG_SPLIT_DELIM_CAPTURE) ?: [];

        for ($i = 1; $i < count($pedacos); $i += 2) {
            $subconjunto = $pedacos[$i];
            $corpo = $pedacos[$i + 1] ?? '';

            if (!preg_match('~src:\s*url\((https://[^)]+\.woff2)\)~', $corpo, $m)) {
                continue;
            }

            preg_match('~font-style:\s*([a-z]+)~', $corpo, $estilo);
            preg_match('~font-weight:\s*([0-9 ]+)~', $corpo, $peso);
            preg_match('~unicode-range:\s*([^;]+);~', $corpo, $unicode);

            $blocos[] = [
                'subconjunto' => $subconjunto,
                'estilo' => $estilo[1] ?? 'normal',
                'peso' => trim($peso[1] ?? '400'),
                'url' => $m[1],
                'unicode' => isset($unicode[1]) ? trim($unicode[1]) : null,
            ];
        }

        return $blocos;
    }

    /** @param array{subconjunto: ?string, estilo: string, peso: string, url: string, unicode: ?string} $bloco */
    private function nomeDoArquivo(string $familia, array $bloco): string
    {
        return sprintf(
            '%s-%s-%s-%s.woff2',
            strtolower(str_replace(' ', '-', $familia)),
            $bloco['subconjunto'] ?? 'latin',
            str_replace(' ', '-', $bloco['peso']),
            $bloco['estilo'],
        );
    }

    /** @param array{subconjunto: ?string, estilo: string, peso: string, url: string, unicode: ?string} $bloco */
    private function regraFontFace(string $familia, array $bloco, string $arquivo): string
    {
        $unicode = $bloco['unicode'] !== null ? "\n    unicode-range: {$bloco['unicode']};" : '';

        return <<<CSS
            @font-face {
                font-family: '{$familia}';
                font-style: {$bloco['estilo']};
                font-weight: {$bloco['peso']};
                font-display: swap;
                src: url('../fonts/{$arquivo}') format('woff2');{$unicode}
            }
            CSS;
    }
}
