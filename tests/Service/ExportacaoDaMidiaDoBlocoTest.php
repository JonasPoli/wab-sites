<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\MidiaDoBloco;
use App\Service\TenantExporter;
use App\Service\TenantImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Imagens enviadas pelo painel (célula da sanfona, logo do mapa) vão no ZIP do site e voltam
 * na importação (changes imagem-na-celula-da-sanfona e bloco-mapa-de-representantes).
 */
final class ExportacaoDaMidiaDoBlocoTest extends KernelTestCase
{
    private const ORIGEM = 'export-midia.localhost';
    private const DESTINO = 'export-midia-2.localhost';

    /** @var string[] */
    private array $arquivos = [];
    private ?string $zip = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        foreach ($this->arquivos as $a) { @unlink($a); }
        if ($this->zip) { @unlink($this->zip); }
        try {
            foreach ([self::ORIGEM, self::DESTINO] as $d) { $this->apagar($d); }
        } catch (\Throwable) {
        }
        parent::tearDown();
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function apagar(string $dominio): void
    {
        $em = $this->em();
        $em->clear();
        $t = $em->getRepository(Tenant::class)->findOneBy(['domain' => $dominio]);
        if (!$t) { return; }
        foreach ($em->getRepository(User::class)->findBy(['tenant' => $t]) as $u) { $em->remove($u); }
        foreach ($em->getRepository(Page::class)->findBy(['tenant' => $t]) as $p) { $em->remove($p); }
        $em->flush();
        $em->remove($t);
        $em->flush();
        $em->clear();
    }

    public function testIdaEVolta(): void
    {
        self::bootKernel(['environment' => 'test']);
        foreach ([self::ORIGEM, self::DESTINO] as $d) { $this->apagar($d); }

        $pasta = static::getContainer()->getParameter('kernel.project_dir') . '/public/' . MidiaDoBloco::PASTA;
        @mkdir($pasta, 0775, true);
        $celula = 'midia_exp_' . bin2hex(random_bytes(4)) . '.png';
        $logo = 'midia_exp_' . bin2hex(random_bytes(4)) . '.png';
        foreach ([$celula, $logo] as $nome) {
            file_put_contents($pasta . '/' . $nome, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
            $this->arquivos[] = $pasta . '/' . $nome;
        }

        $em = $this->em();
        $t = (new Tenant())->setDomain(self::ORIGEM)->setName('Exportação')->setTheme('cetec');
        $em->persist($t);
        $p = (new Page())->setTenant($t)->setTitle('Revendas')->setSlug('revendas');
        $em->persist($p);
        $s = (new PageSection())->setPage($p)->setPosition(0);
        $em->persist($s);
        $em->persist((new PageBlock())->setSection($s)->setType('accordion')->setConfig(['panels' => [[
            'title' => 'Acre', 'kind' => 'table', 'html' => null,
            'table' => ['header' => true, 'rows' => [['Cidade', 'Logo'], ['Rio Branco', ['text' => '', 'image' => $celula]]]],
        ]]]));
        $em->persist((new PageBlock())->setSection($s)->setType('representatives')->setPosition(1)
            ->setConfig(['reps' => [['uf' => 'AC', 'image' => $logo, 'html' => '<h3>J. G. Pacheco</h3>']]]));
        $em->flush();

        $this->zip = static::getContainer()->get(TenantExporter::class)->export($t);
        $z = new \ZipArchive();
        self::assertTrue($z->open($this->zip));
        self::assertNotFalse($z->locateName('media/page_block_media/' . $celula), 'imagem da célula no ZIP');
        self::assertNotFalse($z->locateName('media/page_block_media/' . $logo), 'logo do mapa no ZIP');
        $z->close();

        // Some com os arquivos e importa em outro domínio: eles voltam do ZIP
        foreach ($this->arquivos as $a) { unlink($a); }
        $importador = static::getContainer()->get(TenantImporter::class);
        $analise = $importador->analyze($this->zip);
        $novo = $importador->import($analise['metadata'], ['domain' => self::DESTINO, 'tenant_name' => 'Exportação 2', 'users' => []], $this->zip);
        self::assertSame(self::DESTINO, $novo->getDomain());
        self::assertFileExists($pasta . '/' . $celula);
        self::assertFileExists($pasta . '/' . $logo);
    }
}
