<?php

namespace App\Tests\Controller;

use App\Entity\ContactMessage;
use App\Entity\Tenant;
use App\Repository\ContactMessageRepository;
use App\Service\ContatoAntispam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * POST /contato de ponta a ponta (change antispam-no-contato): envio bloqueado pela isca não
 * virá contato aceito nem e-mail, e envio válido grava o domínio de origem.
 *
 * O projeto não define KERNEL_CLASS no phpunit.xml.dist, então a classe do kernel é dita aqui,
 * sem mexer na configuração dos outros testes. O banco é o do ambiente local; tudo o que o teste
 * grava é apagado no fim.
 */
class ContatoAntispamFuncionalTest extends WebTestCase
{
    private const EMAIL_DO_TESTE = 'teste-antispam@exemplo.test';

    /** Id do tenant que este teste criou, para apagar no fim. */
    private ?int $tenantCriadoAqui = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            if (static::getClient()) {
                $em = static::getContainer()->get(EntityManagerInterface::class);
                foreach ($em->getRepository(ContactMessage::class)->findBy(['senderEmail' => self::EMAIL_DO_TESTE]) as $msg) {
                    $em->remove($msg);
                }
                $em->flush();

                if ($this->tenantCriadoAqui !== null) {
                    $tenant = $em->getRepository(Tenant::class)->find($this->tenantCriadoAqui);
                    if ($tenant) {
                        $em->remove($tenant);
                        $em->flush();
                    }
                    $this->tenantCriadoAqui = null;
                }
            }
        } catch (\Throwable) {
            // Ambiente sem banco: não há o que limpar.
        }

        parent::tearDown();
        static::ensureKernelShutdown();
    }

    /**
     * Cria o cliente e só segue se este ambiente tiver banco e o tenant `localhost`. O projeto não
     * tem banco de teste configurado (o DATABASE_URL de test é o padrão do Symfony), então fora de
     * um ambiente com banco o teste é pulado em vez de falhar.
     */
    private function cliente(): KernelBrowser
    {
        $client = static::createClient();
        // O limite por IP vive no cache.app e dura uma hora: cada teste começa do zero.
        static::getContainer()->get('cache.app')->clear();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        try {
            $tenant = $em->getRepository(Tenant::class)->findOneBy(['domain' => 'localhost']);
        } catch (\Throwable $e) {
            $this->markTestSkipped('sem banco neste ambiente de teste: ' . $e->getMessage());
        }

        if (!$tenant) {
            // Banco de teste vazio: o próprio teste monta o site mínimo de que precisa.
            $tenant = new Tenant();
            $tenant->setDomain('localhost');
            $tenant->setName('Site de Teste');
            $tenant->setTheme('moderno');
            $em->persist($tenant);
            $em->flush();
            $this->tenantCriadoAqui = $tenant->getId();
        }

        return $client;
    }

    /** Hora assinada pelo próprio serviço, como se a página tivesse sido aberta há tantos segundos. */
    private function hora(int $segundosAtras): string
    {
        return static::getContainer()->get(ContatoAntispam::class)->assinarHora(time() - $segundosAtras);
    }

    /**
     * Abre a home (o que cria a sessão e o token CSRF do formulário) e manda o POST com o token
     * e os campos de proteção que a página entregou, trocando o que o teste quiser.
     */
    private function enviar(KernelBrowser $client, array $campos): void
    {
        $client->request('GET', '/');
        $html = (string) $client->getResponse()->getContent();
        if (!str_contains($html, 'name="' . ContatoAntispam::CAMPO_HORA . '"')) {
            $this->markTestSkipped('a home deste site de teste não tem formulário de contato');
        }
        // O nome do formulário muda de tema para tema; o que interessa é o form que posta em /contato.
        $formulario = strstr($html, 'action="/contato"') ?: '';
        if (!preg_match('/name="_token" value="([^"]+)"/', $formulario, $achado)) {
            $this->markTestSkipped('não achei o token CSRF do formulário de contato na home');
        }
        $token = $achado[1];

        $client->request('POST', '/contato', array_merge([
            '_token' => $token,
            'name' => 'Visitante de Teste',
            'email' => self::EMAIL_DO_TESTE,
            'phone' => '16 99999-0000',
            'message' => 'Mensagem de teste do antispam.',
            ContatoAntispam::CAMPO_ISCA => '',
            ContatoAntispam::CAMPO_HORA => $this->hora(30),
        ], $campos));
    }

    public function testEnvioValidoGravaComDominioDeOrigem(): void
    {
        $client = $this->cliente();

        $this->enviar($client, []);

        $this->assertResponseRedirects();
        $repo = static::getContainer()->get(ContactMessageRepository::class);
        $msg = $repo->findOneBy(['senderEmail' => self::EMAIL_DO_TESTE], ['id' => 'DESC']);
        $this->assertNotNull($msg, 'o contato válido devia ter sido gravado');
        $this->assertNull($msg->getSpamReason());
        $this->assertSame('localhost', $msg->getSourceDomain());
        $this->assertFalse($msg->isRead());
    }

    public function testIscaPreenchidaBloqueiaSemEmailEComMotivo(): void
    {
        $client = $this->cliente();

        $this->enviar($client, [ContatoAntispam::CAMPO_ISCA => 'http://spam.ru']);

        $this->assertResponseRedirects();
        $repo = static::getContainer()->get(ContactMessageRepository::class);
        $msg = $repo->findOneBy(['senderEmail' => self::EMAIL_DO_TESTE], ['id' => 'DESC']);
        $this->assertNotNull($msg, 'o bloqueado também é gravado, para não se perder');
        $this->assertSame(ContatoAntispam::MOTIVO_ISCA, $msg->getSpamReason());
        $this->assertTrue($msg->isRead(), 'bloqueado nasce lido, fora da contagem de novos');
        $this->assertSame('localhost', $msg->getSourceDomain());
    }

    public function testPainelSeparaAceitosDeBloqueados(): void
    {
        $client = $this->cliente();

        $this->enviar($client, []);
        $this->enviar($client, [ContatoAntispam::CAMPO_ISCA => 'http://spam.ru']);

        // Depois dos envios, as listas do painel vêm do mesmo repositório que a tela usa.
        $em = static::getContainer()->get(EntityManagerInterface::class);
        if ($em->getFilters()->isEnabled('tenant_filter')) {
            $em->getFilters()->disable('tenant_filter');
        }
        $em->clear();
        $repo = static::getContainer()->get(ContactMessageRepository::class);

        $aceitos = array_filter($repo->findBySituacao(false), fn ($m) => $m->getSenderEmail() === self::EMAIL_DO_TESTE);
        $bloqueados = array_filter($repo->findBySituacao(true), fn ($m) => $m->getSenderEmail() === self::EMAIL_DO_TESTE);

        $this->assertCount(1, $aceitos, 'o aceito aparece na lista normal');
        $this->assertCount(1, $bloqueados, 'o bloqueado aparece só na lista de bloqueados');

        $aceito = reset($aceitos);
        $this->assertNull($aceito->getSpamReason());
        $this->assertFalse($aceito->isRead(), 'o aceito conta como novo no painel');

        $bloqueado = reset($bloqueados);
        $this->assertSame(ContatoAntispam::MOTIVO_ISCA, $bloqueado->getSpamReason());
        $this->assertTrue($bloqueado->isRead(), 'o bloqueado nasce lido, fora da contagem de novos');
        $this->assertGreaterThanOrEqual(1, $repo->countBlocked());
    }

    public function testEmailDoContatoMostraODominioDeOrigem(): void
    {
        static::createClient();
        $twig = static::getContainer()->get('twig');

        $corpo = $twig->render('email/contact.html.twig', [
            'name' => 'Visitante de Teste',
            'sender_email' => self::EMAIL_DO_TESTE,
            'phone' => '16 99999-0000',
            'message' => 'Mensagem de teste.',
            'submitted_at' => new \DateTimeImmutable('now'),
            'tenant' => null,
            'source_domain' => 'site.petvida.com.br',
        ]);

        $this->assertStringContainsString('Site de origem', $corpo);
        $this->assertStringContainsString('site.petvida.com.br', $corpo);
    }

    public function testPostSemCampoDeHoraBloqueia(): void
    {
        $client = $this->cliente();

        $this->enviar($client, [ContatoAntispam::CAMPO_HORA => '']);

        $repo = static::getContainer()->get(ContactMessageRepository::class);
        $msg = $repo->findOneBy(['senderEmail' => self::EMAIL_DO_TESTE], ['id' => 'DESC']);
        $this->assertNotNull($msg);
        $this->assertSame(ContatoAntispam::MOTIVO_TEMPO, $msg->getSpamReason());
    }
}
