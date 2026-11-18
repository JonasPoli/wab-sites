<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\PlaceholderImageFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Site de demonstração do tema atelie em https://atelie.localhost:8000 (tema-atelie): a home
 * reproduz o layout de referência seção por seção, a página "todos-os-blocos" tem um bloco
 * de cada tipo e o blog vem ligado. Admin: atelie.admin / admin123.
 *
 *   bin/console doctrine:fixtures:load --append --group=atelie
 *
 * Não faz nada se o domínio já existe.
 */
class AtelieFixtures extends Fixture implements FixtureGroupInterface
{
    use BlocosDeExemplo;

    public const DOMAIN = 'atelie.localhost';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly PlaceholderImageFactory $placeholder,
    ) {}

    public static function getGroups(): array
    {
        return ['atelie'];
    }

    protected function imagens(): PlaceholderImageFactory
    {
        return $this->placeholder;
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if ($manager->getRepository(Tenant::class)->findOneBy(['domain' => self::DOMAIN])) {
            return;
        }

        $titulos = [];
        foreach ([1 => [600, '3.4rem'], 2 => [600, ''], 3 => [600, ''], 4 => [700, ''], 5 => [700, '']] as $h => [$peso, $tamanho]) {
            $titulos['h' . $h] = ['font' => 'Manrope', 'weight' => (string) $peso, 'size' => ''];
        }

        $tenant = (new Tenant())
            ->setDomain(self::DOMAIN)
            ->setName('Ateliê — Local')
            ->setTheme('atelie')
            ->setPrimaryColor('#9b4509')
            ->setSecondaryColor('#c96a18')
            ->setPrimaryColorDark('#e7a15b')
            ->setSecondaryColorDark('#e7a15b')
            ->setBgColorLight1('#f7f5f0')
            ->setBgColorLight2('#ebe6dc')
            ->setContactEmail('contato@exemplo.com')
            ->setPhone('(16) 0000-0000')
            ->setWhatsappLink('https://wa.me/5500000000000')
            ->setInstagramLink('https://instagram.com/exemplo')
            ->setFacebookLink('https://facebook.com/exemplo')
            ->setAddress("Rua de Exemplo, 100\nAraraquara — SP")
            ->setAboutText('Tradição e design sob medida para ambientes com personalidade.')
            ->setSeoDescription('Site de demonstração do tema Ateliê: marcenaria sob medida, portfólio, processo e blog.')
            ->setFontSettings($titulos)
            ->setOpeningHours([['days' => 'Seg à Qui:', 'hours' => '7h30 às 17h30'], ['days' => 'Sex:', 'hours' => '7h30 às 17h']])
            ->setNavigationSettings([
                'showMenuIcons'   => false,
                'topBarEnabled'   => true,
                'topBarLeft'      => ['hours'],
                'topBarRight'     => ['social', 'phone'],
                'customMenuItems' => [],
                'headerCtaLabel'  => 'Solicite um orçamento',
                'headerCtaUrl'    => '#contato',
                'headerTagline'   => 'Design & marcenaria sob medida',
            ])
            ->setLandingPageMode(true)
            ->setBlogEnabled(true);
        $manager->persist($tenant);

        $admin = (new User())->setUsername('atelie.admin')->setName('Admin Ateliê')->setEmail('atelie.admin@exemplo.com')
            ->setWorkGroup(0)->setTenant($tenant);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        $categoria = (new Category())->setTenant($tenant)->setName('Institucional')->setSlug('institucional');
        $manager->persist($categoria);

        $this->paginaTodosOsBlocos($manager, $tenant, $categoria, 'atelie', true);
        $tenant->setHomePage($this->paginaInicio($manager, $tenant, 'atelie', true));
        $this->postsDoBlog($manager, $tenant, 'atelie');

        $manager->flush();
    }
}
