<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\PlaceholderImageFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Site de conferência do tema cetec em https://cetec.localhost:8000 (blocos-novos-do-construtor):
 * a página "todos-os-blocos" com uma seção por tipo de bloco, e a home "inicio" em Modo
 * Landing Page. Admin: cetec.admin / admin123.
 *
 *   bin/console doctrine:fixtures:load --append --group=cetec
 *
 * Entra também no `doctrine:fixtures:load` sem grupo. Não faz nada se o domínio já existe.
 */
class CetecFixtures extends Fixture implements FixtureGroupInterface
{
    use BlocosDeExemplo;

    public const DOMAIN = 'cetec.localhost';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly PlaceholderImageFactory $placeholder,
    ) {}

    public static function getGroups(): array
    {
        return ['cetec'];
    }

    protected function imagens(): PlaceholderImageFactory
    {
        return $this->placeholder;
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $existente = $manager->getRepository(Tenant::class)->findOneBy(['domain' => self::DOMAIN]);
        if ($existente) {
            // Site já carregado: só completa o blog (blog-nos-sites-institucionais), sem
            // recriar nada, para os ids das seções e dos blocos continuarem os mesmos.
            $this->completarBlog($manager, $existente);
            return;
        }

        $tenant = (new Tenant())
            ->setDomain(self::DOMAIN)
            ->setName('Cetec — Local')
            ->setTheme('cetec')
            ->setPrimaryColor('#9b4509')
            ->setSecondaryColor('#c96a18')
            ->setContactEmail('contato@exemplo.com')
            ->setPhone('(16) 0000-0000')
            ->setWhatsappLink('https://wa.me/5500000000000')
            ->setLandingPageMode(true);
        $manager->persist($tenant);

        $admin = (new User())->setUsername('cetec.admin')->setName('Admin Cetec')->setEmail('cetec.admin@exemplo.com')
            ->setWorkGroup(0)->setTenant($tenant);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $categoria = (new Category())->setTenant($tenant)->setName('Institucional')->setSlug('institucional');
        $manager->persist($categoria);

        $tenant->setBlogEnabled(true);
        $this->paginaTodosOsBlocos($manager, $tenant, $categoria, 'cetec', true);
        $inicio = $this->paginaInicio($manager, $tenant, 'cetec');
        $tenant->setHomePage($inicio);
        $this->postsDoBlog($manager, $tenant, 'cetec');

        $manager->flush();
    }

    private function completarBlog(ObjectManager $manager, Tenant $tenant): void
    {
        if ($manager->getRepository(Article::class)->count(['tenant' => $tenant]) > 0) {
            return;
        }
        $tenant->setBlogEnabled(true);
        $this->postsDoBlog($manager, $tenant, 'cetec');

        $pagina = $manager->getRepository(Page::class)->findOneBy(['tenant' => $tenant, 'slug' => 'todos-os-blocos']);
        if ($pagina) {
            $secao = $this->secao($manager, $pagina, 100);
            $this->bloco($manager, $secao, 'blog_posts', ['preTitle' => 'Blog', 'title' => 'Últimas do blog', 'itemCount' => 3]);
        }
        $manager->flush();
    }
}
