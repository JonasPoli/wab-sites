<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ImageFormats;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Finder\Finder;

/**
 * Gera as miniaturas de todas as imagens enviadas pelo painel.
 *
 * Por que existe: enquanto a miniatura não está gravada, o LiipImagine devolve no HTML a
 * URL `/media/cache/resolve/…`, que responde 302 e só então serve a imagem — a cadeia de
 * redirecionamento que derruba o LCP. O `build.sh` apaga `public/media/cache/*` a cada
 * deploy, então este comando roda logo depois dele, antes de qualquer medição.
 *
 *   php bin/console app:miniaturas:aquecer            # gera o que falta
 *   php bin/console app:miniaturas:aquecer --refazer  # regera tudo
 *   php bin/console app:miniaturas:aquecer --simular  # só lista o que faria
 */
#[AsCommand(
    name: 'app:miniaturas:aquecer',
    description: 'Gera as miniaturas (LiipImagine) de todas as imagens enviadas pelo painel.',
)]
final class AquecerMiniaturasCommand extends Command
{
    /** Pasta de upload => filtros que os temas usam para as imagens dela. */
    private const FILTROS_POR_PASTA = [
        'uploads/hero' => ['banner_desktop', 'banner_mobile'],
        // banner_mobile tem de estar aqui: sem ele, o celular pedia /media/cache/resolve/…,
        // que responde 302 e manda gerar a miniatura na hora — num servidor de 1 vCPU isso
        // levou o speed-index de 1,3 s para 7 s.
        'uploads/page_block' => ['bloco_imagem', 'banner_desktop', 'banner_mobile', 'destaque_foto', 'destaque_foto_mobile'],
        'uploads/page_cover' => ['capa_pagina', 'capa_pagina_inteira'],
        'uploads/page_block_gallery' => ['galeria', 'portfolio', 'portfolio_grande'],
        'uploads/team_member_image' => ['equipe'],
        'uploads/testimonial_avatar' => ['avatar'],
        'uploads/partner_logo' => ['logo_parceiro'],
        'uploads/page_block_media' => ['logo_bloco'],
        'uploads/section/bg' => ['fundo_secao', 'fundo_secao_mobile'],
        'uploads/tenant/logo' => ['logo_site'],
        'uploads/tenant/dark_logo' => ['logo_site'],
        'uploads/tenant/favicon' => ['favicon_site'],
        'uploads/tenant/about' => ['conteudo_largo'],
        'uploads/tenant/og' => ['conteudo_largo'],
        'uploads/article' => ['capa_noticia', 'capa_noticia_mobile', 'news_thumb', 'news_square', 'hero_banner_mobile', 'capa_pagina', 'admin_thumb'],
        'uploads/author' => ['news_square'],
    ];

    public function __construct(
        private readonly DataManager $dataManager,
        private readonly FilterManager $filterManager,
        private readonly CacheManager $cacheManager,
        private readonly ImageFormats $imageFormats,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('refazer', null, InputOption::VALUE_NONE, 'Regera as miniaturas que já existem.')
            ->addOption('simular', null, InputOption::VALUE_NONE, 'Só mostra o que seria gerado.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $refazer = (bool) $input->getOption('refazer');
        $simular = (bool) $input->getOption('simular');

        $geradas = 0;
        $puladas = 0;
        $semConversao = 0;
        $erros = [];

        foreach (self::FILTROS_POR_PASTA as $pasta => $filtros) {
            $absoluta = $this->projectDir . '/public/' . $pasta;
            if (!is_dir($absoluta)) {
                continue;
            }

            $arquivos = (new Finder())->files()->in($absoluta)->depth('== 0');
            foreach ($arquivos as $arquivo) {
                $relativo = $pasta . '/' . $arquivo->getFilename();

                if (!$this->imageFormats->canThumbnail($arquivo->getPathname())) {
                    ++$semConversao;
                    continue;
                }

                foreach ($filtros as $filtro) {
                    if (!$refazer && $this->cacheManager->isStored($relativo, $filtro)) {
                        ++$puladas;
                        continue;
                    }
                    if ($simular) {
                        $io->writeln(sprintf('  geraria <info>%s</info> em %s', $relativo, $filtro));
                        ++$geradas;
                        continue;
                    }

                    try {
                        $binario = $this->dataManager->find($filtro, $relativo);
                        $this->cacheManager->store(
                            $this->filterManager->applyFilter($binario, $filtro),
                            $relativo,
                            $filtro,
                        );
                        ++$geradas;
                    } catch (\Throwable $e) {
                        $erros[] = sprintf('%s (%s): %s', $relativo, $filtro, $e->getMessage());
                    }
                }
            }
        }

        $io->success(sprintf(
            '%d miniatura(s) %s, %d já existia(m), %d imagem(ns) que o servidor não converte.',
            $geradas,
            $simular ? 'seriam geradas' : 'gerada(s)',
            $puladas,
            $semConversao,
        ));

        if ($erros !== []) {
            $io->warning(count($erros) . ' falha(s):');
            $io->listing(array_slice($erros, 0, 20));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
