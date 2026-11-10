<?php

namespace App\Security;

use App\Entity\User;
use App\Service\TenantContext;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Login e troca de usuário (switch_user) só no domínio do site do usuário.
 *
 * O SuperAdmin (sem tenant e workGroup 0) entra em qualquer domínio de tenant; os demais
 * só no domínio do próprio tenant. Usuário sem tenant com outro workGroup é recusado em todos.
 * Encadeado no firewall main (security.user_checker.chain.main), junto com o
 * JournalistStatusChecker do portal de notícias.
 */
#[AutoconfigureTag('security.user_checker.main')]
class TenantUserChecker implements UserCheckerInterface
{
    public const NO_ACCESS_MESSAGE = 'Este usuário não tem acesso a este site.';

    public function __construct(private readonly TenantContext $tenantContext) {}

    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User || $user->isSuperAdmin()) {
            return;
        }

        $domainTenantId = $this->tenantContext->getTenant()?->getId();
        if ($domainTenantId === null || $user->getTenant()?->getId() !== $domainTenantId) {
            throw new CustomUserMessageAccountStatusException(self::NO_ACCESS_MESSAGE);
        }
    }
}
