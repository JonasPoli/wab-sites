<?php

namespace App\Tests\Entity;

use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\Tenant;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PageSectionTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    public function testDefaultValues(): void
    {
        $section = new PageSection();
        $this->assertTrue($section->isActive());
        $this->assertTrue($section->isShowInMenu());
        $this->assertTrue($section->getShowInMenu());

        $section->setShowInMenu(false);
        $this->assertFalse($section->isShowInMenu());
        $this->assertFalse($section->getShowInMenu());
    }

    public function testMenuRenderingInThemes(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $catRepo = $this->createMock(\App\Repository\CategoryRepository::class);
        $catRepo->method('findForHeader')->willReturn([]);
        $catRepo->method('findForFooter')->willReturn([]);
        $container->set(\App\Repository\CategoryRepository::class, $catRepo);

        $pageRepo = $this->createMock(\App\Repository\PageRepository::class);
        $pageRepo->method('findForFooter')->willReturn([]);
        $container->set(\App\Repository\PageRepository::class, $pageRepo);

        $twig = $container->get('twig');

        $s1 = new PageSection();
        $s1->setTitlePart1('SecVisible');
        $s1->setActive(true);
        $s1->setShowInMenu(true);

        $s2 = new PageSection();
        $s2->setTitlePart1('SecHiddenFromMenu');
        $s2->setActive(true);
        $s2->setShowInMenu(false);

        $page = new Page();
        $page->setTitle('Home Page');
        $page->getSections()->add($s1);
        $page->getSections()->add($s2);

        $tenant = new Tenant();
        $tenant->setName('Test Tenant');
        $tenant->setLandingPageMode(true);
        $tenant->setHomePage($page);

        // Push fake request so app.request exists in Twig
        $requestStack = $container->get('request_stack');
        $requestStack->push(new Request());

        $themes = [
            'themes/wab/_header.html.twig',
            'themes/cetec/_header.html.twig',
            'themes/moderno/_header.html.twig',
        ];

        foreach ($themes as $template) {
            $rendered = $twig->render($template, [
                'currentTenant' => $tenant,
            ]);

            $this->assertStringContainsString('SecVisible', $rendered, "Failed for {$template}");
            $this->assertStringNotContainsString('SecHiddenFromMenu', $rendered, "Hidden section found in menu for {$template}");
        }
    }

    public function testPopulateSectionMethod(): void
    {
        $controller = new \App\Controller\admin\AdminContentController();
        $refMethod = new \ReflectionMethod($controller, 'populateSection');
        $refMethod->setAccessible(true);

        $section = new PageSection();
        $this->assertTrue($section->isShowInMenu());

        // Simulate submitting form with showInMenu unchecked
        $requestUnchecked = new Request([], ['active' => '1']); // no showInMenu
        $refMethod->invoke($controller, $section, $requestUnchecked);
        $this->assertFalse($section->isShowInMenu());
        $this->assertTrue($section->isActive());

        // Simulate submitting form with showInMenu checked
        $requestChecked = new Request([], ['active' => '1', 'showInMenu' => '1']);
        $refMethod->invoke($controller, $section, $requestChecked);
        $this->assertTrue($section->isShowInMenu());
    }
}

