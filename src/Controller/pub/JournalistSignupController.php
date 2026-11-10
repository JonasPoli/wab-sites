<?php

namespace App\Controller\pub;

use App\Entity\Author;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\UserRepository;
use App\Service\ImageFormats;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Cadastro público do jornalista candidato (só no tema noticias).
 * Cria, no site do domínio, o usuário (workGroup 3, login = e-mail) e o autor
 * como pendente; o admin do site aprova em /admin/jornalistas.
 */
class JournalistSignupController extends AbstractController
{
    public const SUCCESS_MESSAGE = 'Cadastro recebido! Ele está em análise pela administração do portal. Quando for aprovado, você receberá um e-mail com o link para entrar e publicar.';
    public const CONSENT_TEXT = 'Autorizo o uso destes dados para a análise do meu cadastro e a exibição do meu perfil de autor no portal.';
    private const MAX_PHOTO_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    #[Route('/jornalista/cadastro', name: 'journalist_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $r,
        EntityManagerInterface $em,
        UserRepository $users,
        AuthorRepository $authors,
        UserPasswordHasherInterface $hasher,
        SluggerInterface $slugger,
        ImageFormats $imageFormats,
    ): Response {
        $tenant = $this->tenantContext->getTenant();
        if ($tenant?->getTheme() !== 'noticias') {
            throw $this->createNotFoundException('Página não encontrada.');
        }

        $data = [];
        $errors = [];

        if ($r->isMethod('POST')) {
            $in = $r->request;
            $data = [
                'name'                     => trim((string) $in->get('name')),
                'email'                    => mb_strtolower(trim((string) $in->get('email'))),
                'phone'                    => trim((string) $in->get('phone')), // WhatsApp
                'address'                  => trim((string) $in->get('address')),
                'state'                    => strtoupper(trim((string) $in->get('state'))),
                'spiritistCenter'          => trim((string) $in->get('spiritistCenter')),
                'professionalRegistration' => trim((string) $in->get('professionalRegistration')),
                'outlet'                   => trim((string) $in->get('outlet')),
                'city'                     => trim((string) $in->get('city')),
                'bio'                      => trim((string) $in->get('bio')),
                'consent'                  => (bool) $in->get('consent'),
            ];

            // Campo isca: robô preenche, gente não vê. Responde como sucesso e não grava.
            if (trim((string) $in->get('website')) !== '') {
                $this->addFlash('success', self::SUCCESS_MESSAGE);
                return $this->redirectToRoute('journalist_signup');
            }

            if (!$this->isCsrfTokenValid('journalist_signup', (string) $in->get('_token'))) {
                $errors[] = 'Sessão expirada. Atualize a página e tente novamente.';
            }
            if ($data['name'] === '') {
                $errors[] = 'Informe o seu nome.';
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Informe um e-mail válido.';
            }
            $password = (string) $in->get('password');
            if (mb_strlen($password) < 8) {
                $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
            } elseif ($password !== (string) $in->get('passwordConfirm')) {
                $errors[] = 'A confirmação da senha não confere.';
            }
            if (!Author::isValidWhatsapp($data['phone'])) {
                $errors[] = 'Informe o WhatsApp com DDD.';
            }
            if ($data['address'] === '' || mb_strlen($data['address']) > 255) {
                $errors[] = 'Informe o endereço.';
            }
            if (!isset(Author::STATES[$data['state']])) {
                $errors[] = 'Escolha o estado.';
            }
            if (mb_strlen($data['spiritistCenter']) > 150) {
                $errors[] = 'O nome da casa espírita pode ter até 150 caracteres.';
            }
            if (mb_strlen($data['bio']) > Author::BIO_MAX_LENGTH) {
                $errors[] = sprintf('A minibio pode ter até %d caracteres.', Author::BIO_MAX_LENGTH);
            }
            if (!$data['consent']) {
                $errors[] = 'É preciso aceitar o uso dos dados para enviar o cadastro.';
            }
            $photo = $r->files->get('photo');
            if ($photo instanceof UploadedFile) {
                if (!$photo->isValid() || $photo->getSize() > self::MAX_PHOTO_BYTES) {
                    $errors[] = 'A foto precisa ser uma imagem de até 5 MB.';
                    $photo = null;
                } elseif (!$imageFormats->acceptsUpload($photo)) {
                    $errors[] = ImageFormats::UPLOAD_MESSAGE;
                    $photo = null;
                }
            } else {
                $photo = null;
            }
            // E-mail único em toda a base (User não passa pelo filtro de tenant)
            if (!$errors && ($users->findOneBy(['username' => $data['email']]) || $users->findOneBy(['email' => $data['email']]))) {
                $errors[] = 'Este e-mail já está cadastrado.';
            }

            if (!$errors) {
                $em->wrapInTransaction(function () use ($em, $tenant, $data, $password, $photo, $hasher, $slugger, $authors) {
                    $user = (new User())
                        ->setUsername($data['email'])
                        ->setEmail($data['email'])
                        ->setName($data['name'])
                        ->setWorkGroup(User::WORKGROUP_JOURNALIST)
                        ->setTenant($tenant);
                    $user->setPassword($hasher->hashPassword($user, $password));
                    $em->persist($user);

                    $base = strtolower((string) $slugger->slug($data['name'])) ?: 'autor';
                    $slug = $base;
                    $n = 2;
                    while ($authors->slugExists($slug)) {
                        $slug = $base . '-' . $n++;
                    }

                    $author = (new Author())
                        ->setTenant($tenant)
                        ->setUser($user)
                        ->setName($data['name'])
                        ->setSlug($slug)
                        ->setPhone($data['phone'] ?: null)
                        ->setAddress($data['address'] ?: null)
                        ->setState($data['state'] ?: null)
                        ->setSpiritistCenter($data['spiritistCenter'] ?: null)
                        ->setProfessionalRegistration($data['professionalRegistration'] ?: null)
                        ->setOutlet($data['outlet'] ?: null)
                        ->setCity($data['city'] ?: null)
                        ->setBio($data['bio'] ?: null)
                        ->setConsentAt(new \DateTimeImmutable());
                    if ($photo) {
                        $author->setPhotoFile($photo);
                    }
                    $em->persist($author);
                    $em->flush();
                });

                $this->addFlash('success', self::SUCCESS_MESSAGE);
                return $this->redirectToRoute('journalist_signup');
            }
        }

        return $this->render('themes/noticias/journalist_signup.html.twig', [
            'data'         => $data,
            'errors'       => $errors,
            'consent_text' => self::CONSENT_TEXT,
            'bio_max'      => Author::BIO_MAX_LENGTH,
            'states'       => Author::STATES,
        ], new Response(status: $errors ? 422 : 200));
    }
}
