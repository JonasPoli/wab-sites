<?php

namespace App\Service;

use App\Entity\Author;
use App\Entity\Enum\AuthorStatus;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * E-mail ao jornalista quando o admin do site muda a situação dele
 * (aprovado, reativado, recusado ou suspenso). O envio é na hora (sem fila);
 * uma falha vai para o log e não desfaz a mudança de situação.
 */
final class JournalistNotifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%emailFrom%')]
        private readonly string $emailFrom,
    ) {
    }

    /** @return bool se o e-mail foi entregue ao servidor de envio */
    public function statusChanged(Author $author, AuthorStatus $from, string $loginUrl): bool
    {
        $email = $author->getUser()?->getEmail();
        $site = $author->getTenant()?->getName() ?: 'Portal';
        if (!$email) {
            return false;
        }

        $case = match ($author->getStatus()) {
            AuthorStatus::Approved  => $from === AuthorStatus::Suspended ? 'reativado' : 'aprovado',
            AuthorStatus::Refused   => 'recusado',
            AuthorStatus::Suspended => 'suspenso',
            AuthorStatus::Pending   => null,
        };
        if ($case === null) {
            return false;
        }
        $subject = match ($case) {
            'aprovado'  => 'Seu cadastro de jornalista foi aprovado',
            'reativado' => 'Seu acesso de jornalista foi reativado',
            'recusado'  => 'Seu cadastro de jornalista não foi aprovado',
            'suspenso'  => 'Seu acesso de jornalista foi suspenso',
        };

        try {
            $this->mailer->send((new TemplatedEmail())
                // EMAIL_FROM pode vir como "Nome <endereço>" ou só o endereço; o nome é o do site
                ->from(new Address(Address::create($this->emailFrom)->getAddress(), $site))
                ->to(new Address($email, $author->getName()))
                ->subject(sprintf('[%s] %s', $site, $subject))
                ->htmlTemplate('email/journalist_status.html.twig')
                ->context([
                    'case'       => $case,
                    'name'       => $author->getName(),
                    'site'       => $site,
                    'loginUrl'   => $loginUrl,
                    'loginEmail' => $email,
                    'color'      => preg_match('/^#[0-9a-f]{6}$/i', (string) $author->getTenant()?->getPrimaryColor()) ? $author->getTenant()->getPrimaryColor() : '#00275c',
                ]));
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao enviar e-mail de situação do jornalista: ' . $e->getMessage(), [
                'author' => $author->getId(),
                'case'   => $case,
            ]);
            return false;
        }
    }
}
