<?php

namespace App\Service;

use App\Entity\Tenant;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Antispam do formulário público de contato, sem chave de terceiro e valendo em todos os tenants
 * (antispam-no-contato). Quatro conferências: campo isca preenchido, envio antes do tempo mínimo
 * (ou campo de hora ausente/adulterado), envios demais do mesmo IP e mensagem com cara de spam.
 * verificar() devolve null quando o envio passa, ou o motivo do bloqueio. Quem grava, responde ao
 * visitante e decide o resto é o controller. O log nunca leva nome, e-mail, telefone nem mensagem.
 */
final class ContatoAntispam
{
    /** Campo isca: invisível na tela, só robô preenche. */
    public const CAMPO_ISCA = 'wab_website';

    /** Campo com a hora em que a página foi montada, assinada pelo servidor. */
    public const CAMPO_HORA = 'wab_t';

    public const MOTIVO_ISCA = 'isca';
    public const MOTIVO_TEMPO = 'tempo';
    public const MOTIVO_LIMITE_IP = 'limite_ip';
    public const MOTIVO_CONTEUDO = 'conteudo';

    /** Segundos que o visitante precisa levar, no mínimo, entre abrir a página e enviar. */
    private const SEGUNDOS_MINIMOS = 3;

    /** Quantos contatos aceitos um mesmo IP pode mandar na janela abaixo. */
    private const MAX_POR_IP = 3;

    /** Tamanho da janela do limite por IP, em segundos. */
    private const JANELA_IP = 3600;

    /** A partir de quantos links a mensagem é tratada como spam. */
    private const MAX_LINKS = 4;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.secret%')]
        private readonly string $segredo,
    ) {
    }

    /**
     * Confere o envio do contato. Devolve null quando passa, ou um dos MOTIVO_*.
     */
    public function verificar(Tenant $tenant, Request $request, string $nome, string $mensagem): ?string
    {
        $ip = $request->getClientIp();

        if ((string) $request->request->get(self::CAMPO_ISCA) !== '') {
            return $this->bloquear(self::MOTIVO_ISCA, $tenant, $ip);
        }

        if (!$this->horaAceita((string) $request->request->get(self::CAMPO_HORA))) {
            return $this->bloquear(self::MOTIVO_TEMPO, $tenant, $ip);
        }

        if ($this->passouDoLimite($ip)) {
            return $this->bloquear(self::MOTIVO_LIMITE_IP, $tenant, $ip);
        }

        if ($this->conteudoDeSpam($nome, $mensagem)) {
            return $this->bloquear(self::MOTIVO_CONTEUDO, $tenant, $ip);
        }

        return null;
    }

    /**
     * Valor do campo de hora para o formulário: <timestamp>.<assinatura>. O instante só é passado
     * em teste, para simular uma página aberta há algum tempo; em produção é sempre agora.
     */
    public function assinarHora(?int $quando = null): string
    {
        $instante = $quando ?? time();

        return $instante . '.' . $this->assinatura((string) $instante);
    }

    /**
     * Conta um contato aceito no limite por IP. Só os aceitos contam: assim um robô insistente
     * não gasta a cota de um IP compartilhado (operadora móvel, escola, empresa).
     */
    public function registrarAceito(?string $ip): void
    {
        if (!$ip) {
            return;
        }

        $item = $this->cache->getItem($this->chaveIp($ip));
        $guardado = $item->isHit() ? $item->get() : null;
        $agora = time();

        $quantidade = is_array($guardado) ? (int) ($guardado['c'] ?? 0) : 0;
        $fim = is_array($guardado) ? (int) ($guardado['fim'] ?? 0) : 0;
        if ($fim <= $agora) {
            // Janela nova: começa a contar deste envio.
            $quantidade = 0;
            $fim = $agora + self::JANELA_IP;
        }

        $item->set(['c' => $quantidade + 1, 'fim' => $fim]);
        $item->expiresAfter(max(1, $fim - $agora));
        $this->cache->save($item);
    }

    /**
     * Nome do campo isca e valor do campo de hora, para o template montar os dois campos.
     *
     * @return array{isca: string, hora: string, valorHora: string}
     */
    public function camposDoFormulario(): array
    {
        return [
            'isca' => self::CAMPO_ISCA,
            'hora' => self::CAMPO_HORA,
            'valorHora' => $this->assinarHora(),
        ];
    }

    private function horaAceita(string $valor): bool
    {
        $partes = explode('.', $valor, 2);
        if (count($partes) !== 2 || $partes[0] === '' || !ctype_digit($partes[0])) {
            return false;
        }

        if (!hash_equals($this->assinatura($partes[0]), $partes[1])) {
            return false;
        }

        $agora = time();
        $hora = (int) $partes[0];
        if ($hora > $agora + 1) {
            // Hora no futuro: só acontece com valor forjado.
            return false;
        }

        // Hora antiga passa de propósito: aba aberta de véspera, página em cache e visitante
        // lento não podem virar bloqueio. A validade do envio é do token CSRF.
        return ($agora - $hora) >= self::SEGUNDOS_MINIMOS;
    }

    private function passouDoLimite(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }

        $item = $this->cache->getItem($this->chaveIp($ip));
        if (!$item->isHit()) {
            return false;
        }

        $guardado = $item->get();
        if (!is_array($guardado) || (int) ($guardado['fim'] ?? 0) <= time()) {
            return false;
        }

        return (int) ($guardado['c'] ?? 0) >= self::MAX_POR_IP;
    }

    private function conteudoDeSpam(string $nome, string $mensagem): bool
    {
        if (preg_match('~\[url[=\]]|\[link[=\]]|<a\s+href~i', $mensagem)) {
            return true;
        }

        if (preg_match_all('~https?://|www\.~i', $mensagem) >= self::MAX_LINKS) {
            return true;
        }

        return (bool) preg_match('~\p{Cyrillic}~u', $nome . ' ' . $mensagem);
    }

    private function bloquear(string $motivo, Tenant $tenant, ?string $ip): string
    {
        $this->logger->warning('Antispam: contato bloqueado.', [
            'domain' => (string) $tenant->getDomain(),
            'motivo' => $motivo,
            'ip' => $ip,
        ]);

        return $motivo;
    }

    private function assinatura(string $timestamp): string
    {
        return substr(hash_hmac('sha256', $timestamp, $this->segredo), 0, 16);
    }

    private function chaveIp(string $ip): string
    {
        return 'antispam_ip_' . sha1($ip);
    }
}
