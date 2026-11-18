<?php

namespace App\Command;

use App\Repository\TenantRepository;
use App\Service\Wordpress\ImportadorDePosts;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wordpress:importar',
    description: 'Importa os posts e as categorias de um WordPress (API pública) para o blog de um site',
)]
final class ImportarWordpressCommand extends Command
{
    public function __construct(
        private readonly TenantRepository $tenants,
        private readonly ImportadorDePosts $importador,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('dominio', InputArgument::REQUIRED, 'Domínio do site na plataforma (ex.: pichoneri.wab.com.br)')
            ->addArgument('wordpress', InputArgument::REQUIRED, 'Endereço do WordPress (ex.: https://www.exemplo.com.br)')
            ->addOption('simular', null, InputOption::VALUE_NONE, 'Mostra o que seria feito, sem gravar nem baixar arquivos')
            ->addOption('limite', null, InputOption::VALUE_REQUIRED, 'Importa só os N posts mais recentes')
            ->addOption('link', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Troca de link do próprio site, no formato /antigo=/novo (repetível)')
            ->addOption('texto-link', null, InputOption::VALUE_REQUIRED, 'Texto do link quando uma URL solta no texto é trocada', 'clique aqui')
            ->setHelp(<<<'TXT'
                Cria uma notícia por post publicado (título, slug, data, categoria, texto limpo,
                resumo e capa) e os redirecionamentos dos endereços antigos (/AAAA/MM/DD/slug ->
                /blog/slug e /category/slug -> /blog/categoria/slug). Pode ser rodado de novo:
                o que já existe é pulado. Não altera o cadastro do site nem as páginas.

                  php bin/console app:wordpress:importar pichoneri.wab.com.br https://www.marcenariapichoneri.com.br --simular
                  php bin/console app:wordpress:importar pichoneri.wab.com.br https://www.marcenariapichoneri.com.br --link=/orcamento=/#contato --texto-link="peça seu orçamento"
                TXT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dominio = (string) $input->getArgument('dominio');
        $tenant = $this->tenants->findByDomain($dominio);
        if (!$tenant) {
            $io->error(sprintf('Nenhum site com o domínio %s.', $dominio));
            return Command::FAILURE;
        }
        $base = rtrim((string) $input->getArgument('wordpress'), '/');
        if (!preg_match('~^https?://~', $base)) {
            $io->error('Informe o endereço do WordPress com http:// ou https://.');
            return Command::FAILURE;
        }
        $mapa = [];
        foreach ((array) $input->getOption('link') as $par) {
            [$de, $para] = array_pad(explode('=', (string) $par, 2), 2, '');
            if ($de === '' || $para === '') {
                $io->error(sprintf('Troca de link inválida: %s (use /antigo=/novo).', $par));
                return Command::FAILURE;
            }
            $mapa[$de] = $para;
        }
        $simular = (bool) $input->getOption('simular');
        $limite = $input->getOption('limite') !== null ? max(1, (int) $input->getOption('limite')) : null;

        $io->title(sprintf('%s de %s em %s (%s)', $simular ? 'Simulação da importação' : 'Importação', $base, $dominio, $tenant->getName()));
        try {
            $rel = $this->importador->importar($tenant, $base, $simular, $limite, $mapa, (string) $input->getOption('texto-link'));
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        $io->definitionList(
            ['Posts criados' => count($rel['criados'])],
            ['Já existiam (pulados)' => count($rel['pulados'])],
            ['Ficaram em rascunho (sem texto)' => count($rel['rascunhos'])],
            ['Sem capa' => count($rel['semCapa'])],
            ['Categorias criadas' => count($rel['categorias'])],
            ['Redirecionamentos' => $rel['redirecionamentos']],
        );
        if ($rel['rascunhos']) { $io->section('Rascunhos'); $io->listing($rel['rascunhos']); }
        if ($rel['semCapa']) { $io->section('Sem capa'); $io->listing($rel['semCapa']); }
        if ($rel['categorias']) { $io->section('Categorias criadas'); $io->listing($rel['categorias']); }
        foreach ($rel['avisos'] as $aviso) { $io->warning($aviso); }
        $io->success($simular ? 'Simulação concluída: nada foi gravado.' : 'Importação concluída.');

        return Command::SUCCESS;
    }
}
