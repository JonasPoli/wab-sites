<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Depois do login, o jornalista vai para a área dele (/jornalista), e não para
 * o destino padrão do firewall (admin_dash), que ele não pode abrir.
 */
class JournalistLoginRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly UrlGeneratorInterface $urls) {}

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if (!in_array('ROLE_JOURNALIST', $event->getUser()->getRoles(), true)) {
            return;
        }

        // Mantém um destino salvo dentro da própria área (ex.: /jornalista/perfil)
        $response = $event->getResponse();
        if ($response instanceof RedirectResponse) {
            $path = (string) parse_url($response->getTargetUrl(), PHP_URL_PATH);
            if (str_starts_with($path, '/jornalista')) {
                return;
            }
        }

        $event->setResponse(new RedirectResponse($this->urls->generate('journalist_area')));
    }
}
