<?php

namespace App\Tests\Service;

use App\Entity\Tenant;
use App\Service\ContatoAntispam;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;

/** Cenários da spec contato-e-newsletter (change antispam-no-contato). */
class ContatoAntispamTest extends TestCase
{
    private const SEGREDO = 'segredo-de-teste';

    private function antispam(?ArrayAdapter $cache = null): ContatoAntispam
    {
        return new ContatoAntispam($cache ?? new ArrayAdapter(), new NullLogger(), self::SEGREDO);
    }

    private function tenant(string $dominio = 'exemplo.com.br'): Tenant
    {
        $tenant = new Tenant();
        $tenant->setDomain($dominio);

        return $tenant;
    }

    /** Hora assinada como o formulário manda, com a idade pedida em segundos. */
    private function hora(int $segundosAtras): string
    {
        $ts = (string) (time() - $segundosAtras);

        return $ts . '.' . substr(hash_hmac('sha256', $ts, self::SEGREDO), 0, 16);
    }

    private function envio(array $campos, string $ip = '203.0.113.10'): Request
    {
        return Request::create('/contato', 'POST', $campos, server: ['REMOTE_ADDR' => $ip]);
    }

    private function envioNormal(array $troca = [], string $ip = '203.0.113.10'): Request
    {
        return $this->envio(array_merge([
            ContatoAntispam::CAMPO_ISCA => '',
            ContatoAntispam::CAMPO_HORA => $this->hora(30),
        ], $troca), $ip);
    }

    public function testVisitanteDeVerdadePassa(): void
    {
        $antispam = $this->antispam();

        $this->assertNull($antispam->verificar($this->tenant(), $this->envioNormal(), 'Maria Silva', 'Gostaria de um orçamento, por favor.'));
    }

    public function testIscaComEspacoBloqueia(): void
    {
        $antispam = $this->antispam();
        $request = $this->envioNormal([ContatoAntispam::CAMPO_ISCA => ' ']);

        $this->assertSame(ContatoAntispam::MOTIVO_ISCA, $antispam->verificar($this->tenant(), $request, 'Maria', 'Oi'));
    }

    public function testEnvioEmUmSegundoBloqueia(): void
    {
        $antispam = $this->antispam();
        $request = $this->envioNormal([ContatoAntispam::CAMPO_HORA => $this->hora(1)]);

        $this->assertSame(ContatoAntispam::MOTIVO_TEMPO, $antispam->verificar($this->tenant(), $request, 'Maria', 'Oi'));
    }

    public function testPostSemCampoDeHoraBloqueia(): void
    {
        $antispam = $this->antispam();
        $request = $this->envio([ContatoAntispam::CAMPO_ISCA => '']);

        $this->assertSame(ContatoAntispam::MOTIVO_TEMPO, $antispam->verificar($this->tenant(), $request, 'Maria', 'Oi'));
    }

    public function testHoraAdulteradaBloqueia(): void
    {
        $antispam = $this->antispam();
        $assinaturaDeOutroValor = explode('.', $this->hora(30))[1];
        $request = $this->envioNormal([ContatoAntispam::CAMPO_HORA => (time() - 600) . '.' . $assinaturaDeOutroValor]);

        $this->assertSame(ContatoAntispam::MOTIVO_TEMPO, $antispam->verificar($this->tenant(), $request, 'Maria', 'Oi'));
    }

    public function testHoraNoFuturoBloqueia(): void
    {
        $antispam = $this->antispam();
        $request = $this->envioNormal([ContatoAntispam::CAMPO_HORA => $this->hora(-600)]);

        $this->assertSame(ContatoAntispam::MOTIVO_TEMPO, $antispam->verificar($this->tenant(), $request, 'Maria', 'Oi'));
    }

    public function testPaginaAbertaHaDuasHorasPassa(): void
    {
        $antispam = $this->antispam();
        $request = $this->envioNormal([ContatoAntispam::CAMPO_HORA => $this->hora(7200)]);

        $this->assertNull($antispam->verificar($this->tenant(), $request, 'Maria', 'Escrevi com calma.'));
    }

    public function testQuartoEnvioDoMesmoIpBloqueia(): void
    {
        $antispam = $this->antispam(new ArrayAdapter());

        for ($i = 0; $i < 3; $i++) {
            $this->assertNull($antispam->verificar($this->tenant(), $this->envioNormal(), 'Maria', 'Mensagem ' . $i));
            $antispam->registrarAceito('203.0.113.10');
        }

        $this->assertSame(
            ContatoAntispam::MOTIVO_LIMITE_IP,
            $antispam->verificar($this->tenant(), $this->envioNormal(), 'Maria', 'Quarta mensagem')
        );
    }

    public function testOutroIpNaoEntraNoLimite(): void
    {
        $antispam = $this->antispam();
        for ($i = 0; $i < 3; $i++) {
            $antispam->registrarAceito('203.0.113.10');
        }

        $this->assertNull($antispam->verificar($this->tenant(), $this->envioNormal(ip: '198.51.100.7'), 'João', 'Boa tarde'));
    }

    public function testEnvioBloqueadoNaoGastaOLimiteDoIp(): void
    {
        $antispam = $this->antispam();

        for ($i = 0; $i < 10; $i++) {
            $bloqueado = $this->envioNormal([ContatoAntispam::CAMPO_ISCA => 'http://spam.ru']);
            $this->assertSame(ContatoAntispam::MOTIVO_ISCA, $antispam->verificar($this->tenant(), $bloqueado, 'Robô', 'spam'));
        }

        $this->assertNull($antispam->verificar($this->tenant(), $this->envioNormal(), 'Maria', 'Gostaria de um orçamento.'));
    }

    public function testMensagemCheiaDeLinksBloqueia(): void
    {
        $antispam = $this->antispam();
        $mensagem = 'http://a.ru http://b.ru http://c.ru http://d.ru http://e.ru';

        $this->assertSame(
            ContatoAntispam::MOTIVO_CONTEUDO,
            $antispam->verificar($this->tenant(), $this->envioNormal(), 'Compare', $mensagem)
        );
    }

    public function testBbcodeDeLinkBloqueia(): void
    {
        $antispam = $this->antispam();

        $this->assertSame(
            ContatoAntispam::MOTIVO_CONTEUDO,
            $antispam->verificar($this->tenant(), $this->envioNormal(), 'Compare', '[url=http://exemplo.ru]oferta[/url]')
        );
    }

    public function testCirilicoBloqueia(): void
    {
        $antispam = $this->antispam();

        $this->assertSame(
            ContatoAntispam::MOTIVO_CONTEUDO,
            $antispam->verificar($this->tenant(), $this->envioNormal(), 'Привет', 'Olá, tudo bem?')
        );
    }

    public function testUmLinkSoPassa(): void
    {
        $antispam = $this->antispam();

        $this->assertNull(
            $antispam->verificar($this->tenant(), $this->envioNormal(), 'Maria', 'Meu site é https://maria.com.br, pode ver?')
        );
    }

    public function testHoraAssinadaPeloServicoEAceita(): void
    {
        $antispam = $this->antispam();
        $campos = $antispam->camposDoFormulario();

        $this->assertSame(ContatoAntispam::CAMPO_ISCA, $campos['isca']);
        $this->assertSame(ContatoAntispam::CAMPO_HORA, $campos['hora']);
        $this->assertMatchesRegularExpression('/^\d+\.[0-9a-f]{16}$/', $campos['valorHora']);
    }
}
