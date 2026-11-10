<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\TenantContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Acesso ao /admin pelo domínio do site do usuário, em toda requisição.
 *
 *   TENANT_ACCESS → ROLE_ADMIN e (SuperAdmin ou tenant do usuário = tenant do domínio)
 *   TENANT_MANAGE → TENANT_ACCESS e workGroup 0 (configurações e editores)
 *
 * Cobre sessões abertas antes do TenantUserChecker e a troca de domínio de um tenant.
 */
class TenantAccessVoter extends Voter
{
    public const ACCESS = 'TENANT_ACCESS';
    public const MANAGE = 'TENANT_MANAGE';

    public function __construct(private readonly TenantContext $tenantContext) {}

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS || $attribute === self::MANAGE;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !in_array('ROLE_ADMIN', $token->getRoleNames(), true)) {
            return false;
        }

        if (!$user->isSuperAdmin()) {
            $domainTenantId = $this->tenantContext->getTenant()?->getId();
            if ($domainTenantId === null || $user->getTenant()?->getId() !== $domainTenantId) {
                return false;
            }
        }

        return $attribute === self::ACCESS || $user->getWorkGroup() === 0;
    }
}
