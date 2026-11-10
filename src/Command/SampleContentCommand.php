<?php

namespace App\Command;

use App\Repository\TenantRepository;
use App\SampleContent\SampleContentGeneratorInterface;
use App\SampleContent\SampleReport;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Conteúdo de exemplo para mostrar o layout de um site antes do conteúdo real.
 *
 *   app:conteudo-exemplo ajope.wab.com.br                  # 20 notícias de exemplo
 *   app:conteudo-exemplo ajope.wab.com.br --simular        # mostra o plano, não grava
 *   app:conteudo-exemplo ajope.wab.com.br --remover        # apaga só o que é de exemplo
 *   app:conteudo-exemplo ajope.wab.com.br --substituir --quantidade=10
 *
 * Nunca altera o tenant (nome, cores, logo, tema, configurações).
 */
#[AsCommand(
    name: 'app:conteudo-exemplo',
    description: 'Cria, troca ou remove conteúdo de exemplo (notícias, no tema noticias) no site de um domínio',
)]
final class SampleContentCommand extends Command
{
    private const DEFAULT_QUANTITY = 20;

    /** @param iterable<SampleContentGeneratorInterface> $generators */
    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly EntityManagerInterface $em,
        #[AutowireIterator('app.sample_content_generator')]
        private readonly iterable $generators,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('dominio', InputArgument::REQUIRED, 'Domínio do site, exatamente como cadastrado (ex.: ajope.wab.com.br)')
            ->addOption('quantidade', null, InputOption::VALUE_REQUIRED, 'Quantas notícias criar', (string) self::DEFAULT_QUANTITY)
            ->addOption('remover', null, InputOption::VALUE_NONE, 'Apaga só o conteúdo de exemplo do site e não cria nada')
            ->addOption('substituir', null, InputOption::VALUE_NONE, 'Apaga o conteúdo de exemplo e cria um lote novo')
            ->addOption('simular', null, InputOption::VALUE_NONE, 'Mostra o que seria feito, sem gravar nada');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $domain = trim((string) $input->getArgument('dominio'));
        $remove = (bool) $input->getOption('remover');
        $replace = (bool) $input->getOption('substituir');
        $simulate = (bool) $input->getOption('simular');

        if ($remove && $replace) {
            $io->error('Use --remover ou --substituir, não os dois.');
            return Command::INVALID;
        }

        // Mesmo match exato da resolução de domínio do site (TenantSubscriber)
        $tenant = $this->tenants->findByDomain($domain);
        if (!$tenant) {
            $io->error(sprintf('Nenhum site com o domínio %s', $domain));
            return Command::FAILURE;
        }

        $generator = null;
        foreach ($this->generators as $g) {
            if ($g->theme() === $tenant->getTheme()) {
                $generator = $g;
                break;
            }
        }
        if (!$generator) {
            $io->error(sprintf('O tema %s não tem conteúdo de exemplo', $tenant->getTheme()));
            return Command::FAILURE;
        }

        $quantity = self::DEFAULT_QUANTITY;
        if (!$remove) {
            $raw = (string) $input->getOption('quantidade');
            $max = $generator->maxQuantity();
            if (!ctype_digit($raw) || (int) $raw < 1 || (int) $raw > $max) {
                $io->error(sprintf('A quantidade deve ser um número de 1 a %d.', $max));
                return Command::INVALID;
            }
            $quantity = (int) $raw;
        }

        $existing = $generator->countSamples($tenant);
        if (!$remove && !$replace && $existing > 0) {
            $io->error(sprintf('O site já tem %d %s de exemplo; use --substituir ou --remover', $existing, $generator->label()));
            return Command::FAILURE;
        }

        $io->title(sprintf('Conteúdo de exemplo em %s (%s, tema %s)', $domain, $tenant->getName(), $tenant->getTheme()));

        if ($simulate) {
            $report = new SampleReport();
            if ($remove || $replace) {
                $report->merge($generator->planRemoval($tenant));
            }
            if (!$remove) {
                $report->merge($generator->plan($tenant, $quantity));
            }
            $this->show($io, $report);
            $io->note('Simulação: nada foi gravado.');
            return Command::SUCCESS;
        }

        try {
            $report = $this->em->wrapInTransaction(function () use ($generator, $tenant, $remove, $replace, $quantity): SampleReport {
                $report = new SampleReport();
                if ($remove || $replace) {
                    $report->merge($generator->remove($tenant));
                }
                if (!$remove) {
                    $report->merge($generator->generate($tenant, $quantity));
                }
                return $report;
            });
        } catch (\Throwable $e) {
            $generator->afterRollback();
            $io->error('Nada foi gravado: ' . $e->getMessage());
            return Command::FAILURE;
        }
        $generator->afterCommit();

        $this->show($io, $report);
        if (!$remove) {
            $io->note(sprintf(
                'As %s de exemplo saem com noindex e ficam fora do sitemap. Antes de divulgar o site, rode: bin/console app:conteudo-exemplo %s --remover',
                $generator->label(),
                $domain,
            ));
        } else {
            $io->note('Notícias de exemplo editadas no painel também foram removidas; editorias e jornalistas com conteúdo real ficaram (veja os avisos).');
        }
        $io->success('Pronto.');
        return Command::SUCCESS;
    }

    private function show(SymfonyStyle $io, SampleReport $report): void
    {
        $io->listing($report->lines);
        foreach ($report->warnings as $w) {
            $io->warning($w);
        }
    }
}
