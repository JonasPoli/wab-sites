<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * `/favicon.ico` por domínio.
 *
 * O navegador pede `/favicon.ico` mesmo quando a página declara `<link rel="icon">`, e sem
 * esta rota a resposta era um 404 que o Lighthouse registra em "erros no console" e derruba
 * a nota de Práticas recomendadas. Aqui ele recebe o favicon do próprio site.
 */
final class FaviconController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/favicon.ico', name: 'pub_favicon', methods: ['GET'])]
    public function favicon(): Response
    {
        $favicon = $this->tenantContext->getTenant()?->getFavicon();

        if ($favicon !== null && $favicon !== '') {
            $arquivo = $this->projectDir . '/public/uploads/tenant/favicon/' . $favicon;
            if (is_file($arquivo)) {
                $resposta = new BinaryFileResponse($arquivo);
                $resposta->setPublic();
                $resposta->setMaxAge(86400);

                return $resposta;
            }
        }

        // Site sem favicon cadastrado: 204 em vez de 404, para não sujar o console
        return new Response('', Response::HTTP_NO_CONTENT, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
