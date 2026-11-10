<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\AuthorRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Login de jornalista (workGroup 3) só com o autor aprovado no site do domínio.
 *
 * Registrado como checker encadeado do firewall main (security.user_checker.chain.main),
 * para conviver com outros checkers, como o TenantUserChecker de isolamento-entre-sites.
 * O AuthorRepository passa pelo filtro de tenant do domínio: em outro domínio o
 * autor não é encontrado e o login é recusado.
 */
#[AutoconfigureTag('security.user_checker.main')]
class JournalistStatusChecker implements UserCheckerInterface
{
    public const NO_ACCESS_MESSAGE = 'Este usuário não tem acesso a este site.';

    public function __construct(private readonly AuthorRepository $authors) {}

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User || !$user->isJournalist()) {
            return;
        }

        $author = $this->authors->findOneByUser($user);
        if (!$author) {
            throw new CustomUserMessageAccountStatusException(self::NO_ACCESS_MESSAGE);
        }

        $refusal = $author->getStatus()->loginRefusalMessage();
        if ($refusal !== null) {
            throw new CustomUserMessageAccountStatusException($refusal);
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
