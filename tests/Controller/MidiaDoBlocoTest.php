<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\MidiaDoBloco;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/** Envio de imagem na hora pelo painel (change imagem-na-celula-da-sanfona). */
final class MidiaDoBlocoTest extends WebTestCase
{
    private const SITE = 'midia-bloco.localhost';
    private ?int $tenantId = null;
    /** @var string[] */
    private array $gravados = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        $pasta = \dirname(__DIR__, 2) . '/public/' . MidiaDoBloco::PASTA . '/';
        foreach ($this->gravados as $nome) {
            @unlink($pasta . $nome);
        }
        try {
            $this->apagarSite();
        } catch (\Throwable) {
        }
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function apagarSite(): void
    {
        $em = $this->em();
        $em->clear();
        $t = $em->getRepository(Tenant::class)->findOneBy(['domain' => self::SITE]);
        if (!$t) { return; }
        foreach ($em->getRepository(User::class)->findBy(['tenant' => $t]) as $u) { $em->remove($u); }
        foreach ($em->getRepository(Page::class)->findBy(['tenant' => $t]) as $p) { $em->remove($p); }
        $em->flush();
        $em->remove($t);
        $em->flush();
    }

    private function arquivo(string $tipo): UploadedFile
    {
        $caminho = tempnam(sys_get_temp_dir(), 'midia');
        if ($tipo === 'png') {
            $img = imagecreatetruecolor(40, 20);
            imagepng($img, $caminho);
            imagedestroy($img);
            return new UploadedFile($caminho, 'logo.png', 'image/png', null, true);
        }
        file_put_contents($caminho, "%PDF-1.4\n%fake\n");
        return new UploadedFile($caminho, 'logo.pdf', 'application/pdf', null, true);
    }

    public function testEnvio(): void
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => self::SITE]);
        $this->apagarSite();
        $em = $this->em();
        $t = (new Tenant())->setDomain(self::SITE)->setName('Mídia')->setTheme('cetec');
        $em->persist($t);
        $p = (new Page())->setTenant($t)->setTitle('P')->setSlug('p');
        $em->persist($p);
        $s = (new PageSection())->setPage($p)->setPosition(0);
        $em->persist($s);
        $b = (new PageBlock())->setSection($s)->setType('accordion')->setConfig(['panels' => []]);
        $em->persist($b);
        $u = (new User())->setUsername('midia.admin')->setName('Admin')->setEmail('midia.admin@exemplo.com')->setWorkGroup(0)->setTenant($t)->setPassword('x');
        $em->persist($u);
        $em->flush();

        // Sem login: vai para o login e nada é gravado
        $cliente->request('POST', '/admin/block/midia', [], ['arquivo' => $this->arquivo('png')]);
        self::assertResponseRedirects();

        $cliente->loginUser($u);
        $crawler = $cliente->request('GET', '/admin/block/' . $b->getId() . '/edit');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('[data-token-midia]')->attr('data-token-midia');
        self::assertNotEmpty($token);

        // Token errado
        $cliente->request('POST', '/admin/block/midia', ['_token' => 'x'], ['arquivo' => $this->arquivo('png')]);
        self::assertResponseStatusCodeSame(403);

        // PDF recusado
        $cliente->request('POST', '/admin/block/midia', ['_token' => $token], ['arquivo' => $this->arquivo('pdf')]);
        self::assertResponseStatusCodeSame(400);
        self::assertStringContainsString('JPG, PNG, WebP ou GIF', (string) $cliente->getResponse()->getContent());

        // PNG gravado
        $cliente->request('POST', '/admin/block/midia', ['_token' => $token], ['arquivo' => $this->arquivo('png')]);
        self::assertResponseIsSuccessful();
        $json = json_decode((string) $cliente->getResponse()->getContent(), true);
        self::assertMatchesRegularExpression('/^midia_[0-9a-f]+\.png$/', $json['arquivo']);
        $this->gravados[] = $json['arquivo'];
        self::assertFileExists(\dirname(__DIR__, 2) . '/public/' . MidiaDoBloco::PASTA . '/' . $json['arquivo']);
    }
}
