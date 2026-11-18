<?php

namespace App\Controller\admin;

use App\Entity\Redirect;
use App\Repository\RedirectRepository;
use App\Service\CaminhoNormalizado;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Redirecionamentos do site do domínio (site-marcenaria-pichoneri). Só o admin do site
 * (TENANT_MANAGE: workGroup 0); grava sempre no tenant do domínio, e a exclusão só acha
 * redirecionamento desse tenant.
 */
#[IsGranted('TENANT_MANAGE')]
#[Route('/admin/redirecionamentos', name: 'admin_redirect_')]
class AdminRedirectController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly RedirectRepository $redirects,
    ) {}

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $r, EntityManagerInterface $em): Response
    {
        $tenant = $this->tenantContext->requireTenant();
        $de = trim((string) $r->request->get('from', ''));
        $para = trim((string) $r->request->get('to', ''));

        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('redirect_new', (string) $r->request->get('_token'))) {
                $this->addFlash('error', 'Sessão expirada. Atualize a página e tente novamente.');
            } elseif ($erro = $this->validar($de, $para, $tenant)) {
                $this->addFlash('error', $erro);
            } else {
                $em->persist((new Redirect())->setTenant($tenant)->setFromPath(CaminhoNormalizado::de($de))->setToPath($para));
                $em->flush();
                $this->addFlash('success', 'Redirecionamento cadastrado.');
                return $this->redirectToRoute('admin_redirect_index');
            }
        }

        $pagina = max(1, $r->query->getInt('pagina', 1));
        $total = $this->redirects->countForTenant($tenant);
        return $this->render('admin/redirect/index.html.twig', [
            'redirects' => $this->redirects->findForAdmin($tenant, $pagina),
            'total'     => $total,
            'pagina'    => $pagina,
            'paginas'   => max(1, (int) ceil($total / 100)),
            'de'        => $de,
            'para'      => $para,
        ]);
    }

    #[Route('/{id}/excluir', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $r, EntityManagerInterface $em): Response
    {
        $tenant = $this->tenantContext->requireTenant();
        $redirect = $this->redirects->findOneBy(['id' => $id, 'tenant' => $tenant]) ?? throw $this->createNotFoundException();
        if ($this->isCsrfTokenValid('del_redirect_' . $id, (string) $r->request->get('_token'))) {
            $em->remove($redirect);
            $em->flush();
            $this->addFlash('success', 'Redirecionamento excluído.');
        }
        return $this->redirectToRoute('admin_redirect_index');
    }

    private function validar(string $de, string $para, $tenant): ?string
    {
        if (!str_starts_with($de, '/') || !str_starts_with($para, '/')) {
            return 'O endereço antigo e o novo precisam começar com "/" (endereços deste site).';
        }
        if (str_starts_with($para, '//')) {
            return 'O endereço novo precisa ser deste site: não pode começar com "//".';
        }
        $origem = CaminhoNormalizado::de($de);
        if ($origem === '/') {
            return 'A página inicial não pode ser redirecionada.';
        }
        if ($origem === CaminhoNormalizado::de($para)) {
            return 'O endereço novo é igual ao antigo.';
        }
        if ($this->redirects->findOneByPath($tenant, $origem)) {
            return sprintf('Já existe um redirecionamento para %s.', $origem);
        }
        return null;
    }
}
