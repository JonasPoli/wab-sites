<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\BarraSuperior;
use App\Twig\IconeDoPainelExtension;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Baixa para o repositório os SVG dos ícones que os clientes escolheram no painel.
 *
 * Os ícones fixos dos temas são gravados pelo `ux:icons:lock`, que varre os templates.
 * Os escolhidos no painel (blocos "Texto com 4 Blocos" e ícone de categoria) estão no
 * banco, então o lock não os vê: este comando lê o banco de todos os sites e manda o
 * `ux:icons:import` buscar cada um, para o servidor nunca precisar de rede ao renderizar.
 *
 *   php bin/console app:icones:sincronizar             # baixa o que falta
 *   php bin/console app:icones:sincronizar --listar    # só mostra o que está em uso
 *
 * Rodar depois de cadastrar ícone novo no painel e antes do deploy.
 */
#[AsCommand(
    name: 'app:icones:sincronizar',
    description: 'Baixa os SVG dos ícones escolhidos no painel pelos sites.',
)]
final class SincronizarIconesCommand extends Command
{
    public function __construct(
        private readonly Connection $conexao,
        private readonly IconeDoPainelExtension $icones,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('listar', null, InputOption::VALUE_NONE, 'Só lista os ícones em uso, sem baixar.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $nomes = $this->nomesEmUso();
        if ($nomes === []) {
            $io->success('Nenhum ícone escolhido no painel.');

            return Command::SUCCESS;
        }

        $io->writeln(sprintf('%d ícone(s) em uso nos sites:', count($nomes)));
        $io->listing($nomes);

        if ($input->getOption('listar')) {
            return Command::SUCCESS;
        }

        $aplicacao = $this->getApplication();
        if ($aplicacao === null) {
            $io->error('Sem acesso ao console para chamar o ux:icons:import.');

            return Command::FAILURE;
        }

        $codigo = $aplicacao->find('ux:icons:import')->run(
            new ArrayInput(['names' => $nomes]),
            $output,
        );

        if ($codigo !== Command::SUCCESS) {
            $io->error('O ux:icons:import falhou. Confira a conexão com a internet e os nomes acima.');

            return Command::FAILURE;
        }

        $io->success('Ícones gravados em assets/icons/. Faça commit deles antes do deploy.');

        return Command::SUCCESS;
    }

    /**
     * Nomes no formato do Iconify, sem repetição e em ordem.
     *
     * @return list<string>
     */
    private function nomesEmUso(): array
    {
        $classes = [];

        // Ícone da categoria
        foreach ($this->colunaDeTexto('SELECT icon FROM category WHERE icon IS NOT NULL AND icon <> %s') as $valor) {
            $classes[] = $valor;
        }

        // Ícone do botão flutuante de formulário (tenant.floating_button)
        foreach ($this->colunaDeTexto('SELECT floating_button FROM tenant WHERE floating_button IS NOT NULL') as $json) {
            $fb = json_decode($json, true);
            if (is_array($fb) && is_string($fb['icon'] ?? null) && $fb['icon'] !== '') {
                $classes[] = $fb['icon'];
            }
        }

        // Ícones dos itens personalizados da barra superior (navigationSettings.topBarLeft/Right)
        foreach ($this->colunaDeTexto('SELECT navigation_settings FROM tenant WHERE navigation_settings IS NOT NULL') as $json) {
            $nav = json_decode($json, true);
            foreach (['topBarLeft', 'topBarRight'] as $lado) {
                foreach (BarraSuperior::normalizarLado($nav[$lado] ?? []) as $item) {
                    if ($item['icon'] !== '') {
                        $classes[] = $item['icon'];
                    }
                }
            }
        }

        // Ícones guardados no JSON de configuração dos blocos (blurbs4 e afins)
        foreach ($this->colunaDeTexto('SELECT config FROM page_block WHERE config IS NOT NULL AND config <> %s') as $json) {
            $config = json_decode($json, true);
            if (!is_array($config)) {
                continue;
            }
            foreach ($config as $chave => $valor) {
                if (is_string($valor) && $valor !== '' && str_contains(strtolower((string) $chave), 'icon')) {
                    $classes[] = $valor;
                }
            }
        }

        $nomes = [];
        foreach ($classes as $classe) {
            $nome = $this->icones->nome($classe);
            if ($nome !== null) {
                $nomes[$nome] = true;
            }
        }

        $lista = array_keys($nomes);
        sort($lista);

        return $lista;
    }

    /** @return list<string> */
    private function colunaDeTexto(string $sqlComPlaceholder): array
    {
        $sql = sprintf($sqlComPlaceholder, "''");

        try {
            return array_values(array_filter(
                $this->conexao->fetchFirstColumn($sql),
                static fn ($v) => is_string($v) && $v !== '',
            ));
        } catch (\Throwable) {
            return [];
        }
    }
}
