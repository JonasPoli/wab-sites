<?php

namespace App\Controller\admin;

use App\Entity\HeroBanner;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\PageBlock;
use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\HeroBannerRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageRepository;
use App\Repository\PageSectionRepository;
use App\Repository\PageBlockRepository;
use App\Repository\UserRepository;

use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'admin_')]
class AdminContentController extends AbstractController
{
    // ── Dashboard ────────────────────────────────────────────────────────────

    #[Route('', name: 'dash')]
    public function dashboard(
        ContactMessageRepository $contacts,
        PageRepository $pages,
        CategoryRepository $cats,
        NewsletterSubscriberRepository $newsletters
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'unreadContacts'  => $contacts->countUnread(),
            'pageCount'       => count($pages->findAll()),
            'categoryCount'   => count($cats->findAll()),
            'newsletterCount' => count($newsletters->findAll()),
        ]);
    }

    // ── HeroBanner ───────────────────────────────────────────────────────────

    #[Route('/banner', name: 'banner_index')]
    public function bannerIndex(HeroBannerRepository $repo): Response
    {
        return $this->render('admin/banner/index.html.twig', ['banners' => $repo->findBy([], ['position' => 'ASC', 'id' => 'ASC'])]);
    }

    #[Route('/banner/new', name: 'banner_new', methods: ['GET', 'POST'])]
    public function bannerNew(Request $r, EntityManagerInterface $em, TenantContext $tc): Response
    {
        $banner = new HeroBanner();
        if ($r->isMethod('POST')) {
            $banner->setTenant($tc->requireTenant());
            $banner->setTitle((string) $r->request->get('title'));
            $banner->setSubtitle($r->request->get('subtitle') ?: null);
            $banner->setCtaText($r->request->get('ctaText') ?: null);
            $banner->setCtaLink($r->request->get('ctaLink') ?: null);
            $banner->setActive((bool) $r->request->get('active'));
            $file = $r->files->get('backgroundImageFile');
            if ($file instanceof UploadedFile && $file->isValid()) { $banner->setBackgroundImageFile($file); }
            $em->persist($banner);
            $em->flush();
            $this->addFlash('success', 'Banner criado.');
            return $this->redirectToRoute('admin_banner_index');
        }
        return $this->render('admin/banner/new.html.twig', ['banner' => $banner]);
    }

    #[Route('/banner/{id}/edit', name: 'banner_edit', methods: ['GET', 'POST'])]
    public function bannerEdit(HeroBanner $banner, Request $r, EntityManagerInterface $em): Response
    {
        if ($r->isMethod('POST')) {
            $banner->setTitle((string) $r->request->get('title'));
            $banner->setSubtitle($r->request->get('subtitle') ?: null);
            $banner->setCtaText($r->request->get('ctaText') ?: null);
            $banner->setCtaLink($r->request->get('ctaLink') ?: null);
            $banner->setActive((bool) $r->request->get('active'));
            $file = $r->files->get('backgroundImageFile');
            if ($file instanceof UploadedFile && $file->isValid()) { $banner->setBackgroundImageFile($file); }
            $em->flush();
            $this->addFlash('success', 'Banner atualizado.');
            return $this->redirectToRoute('admin_banner_index');
        }
        return $this->render('admin/banner/edit.html.twig', ['banner' => $banner]);
    }

    #[Route('/banner/reorder', name: 'banner_reorder', methods: ['POST'])]
    public function bannerReorder(Request $r, HeroBannerRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $ids = json_decode($r->getContent(), true)['ids'] ?? [];
        foreach ($ids as $pos => $id) {
            $banner = $repo->find((int) $id);
            if ($banner) { $banner->setPosition($pos + 1); }
        }
        $em->flush();
        return new JsonResponse(['ok' => true]);
    }

    #[Route('/banner/{id}/delete', name: 'banner_delete', methods: ['POST'])]
    public function bannerDelete(HeroBanner $banner, Request $r, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_banner_' . $banner->getId(), (string) $r->request->get('_token'))) {
            $em->remove($banner);
            $em->flush();
        }
        return $this->redirectToRoute('admin_banner_index');
    }

    // ── Category ─────────────────────────────────────────────────────────────

    #[Route('/category', name: 'category_index')]
    public function categoryIndex(CategoryRepository $repo): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $repo->findRootCategories(),
        ]);
    }

    #[Route('/category/new', name: 'category_new', methods: ['GET', 'POST'])]
    public function categoryNew(Request $r, EntityManagerInterface $em, TenantContext $tc, SluggerInterface $slugger): Response
    {
        $cat = new Category();
        if ($r->isMethod('POST')) {
            $cat->setTenant($tc->requireTenant());
            $this->populateCategory($cat, $r, $slugger);
            $em->persist($cat);
            $em->flush();
            $this->addFlash('success', 'Categoria criada.');
            return $this->redirectToRoute('admin_category_index');
        }
        return $this->render('admin/category/new.html.twig', ['category' => $cat]);
    }

    #[Route('/category/{id}/edit', name: 'category_edit', methods: ['GET', 'POST'])]
    public function categoryEdit(Category $cat, Request $r, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if ($r->isMethod('POST')) {
            $this->populateCategory($cat, $r, $slugger);
            $em->flush();
            $this->addFlash('success', 'Categoria atualizada.');
            return $this->redirectToRoute('admin_category_index');
        }
        return $this->render('admin/category/edit.html.twig', ['category' => $cat]);
    }

    #[Route('/category/{id}/delete', name: 'category_delete', methods: ['POST'])]
    public function categoryDelete(Category $cat, Request $r, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_cat_' . $cat->getId(), (string) $r->request->get('_token'))) {
            $em->remove($cat);
            $em->flush();
        }
        return $this->redirectToRoute('admin_category_index');
    }

    // ── SubCategory ──────────────────────────────────────────────────────────

    #[Route('/category/{id}/sub', name: 'subcategory_index')]
    public function subcategoryIndex(Category $cat): Response
    {
        return $this->render('admin/category/sub_index.html.twig', [
            'category' => $cat,
            'subcategories' => $cat->getChildren(),
        ]);
    }

    #[Route('/category/{id}/sub/new', name: 'subcategory_new', methods: ['GET', 'POST'])]
    public function subcategoryNew(Category $cat, Request $r, EntityManagerInterface $em, TenantContext $tc, SluggerInterface $slugger): Response
    {
        // Sub-categories cannot themselves have children (max 1 level)
        if ($cat->isSubCategory()) {
            $this->addFlash('danger', 'Sub-categorias não podem ter sub-categorias.');
            return $this->redirectToRoute('admin_category_index');
        }
        $sub = new Category();
        if ($r->isMethod('POST')) {
            $sub->setTenant($tc->requireTenant());
            $sub->setParent($cat);
            $this->populateCategory($sub, $r, $slugger);
            $em->persist($sub);
            $em->flush();
            $this->addFlash('success', 'Sub-categoria criada.');
            return $this->redirectToRoute('admin_subcategory_index', ['id' => $cat->getId()]);
        }
        return $this->render('admin/category/sub_new.html.twig', [
            'category' => $cat,
            'subcategory' => $sub,
        ]);
    }

    #[Route('/subcategory/{id}/edit', name: 'subcategory_edit', methods: ['GET', 'POST'])]
    public function subcategoryEdit(Category $sub, Request $r, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $parent = $sub->getParent();
        if ($r->isMethod('POST')) {
            $this->populateCategory($sub, $r, $slugger);
            $em->flush();
            $this->addFlash('success', 'Sub-categoria atualizada.');
            return $this->redirectToRoute('admin_subcategory_index', ['id' => $parent?->getId()]);
        }
        return $this->render('admin/category/sub_edit.html.twig', [
            'category' => $parent,
            'subcategory' => $sub,
        ]);
    }

    #[Route('/subcategory/{id}/delete', name: 'subcategory_delete', methods: ['POST'])]
    public function subcategoryDelete(Category $sub, Request $r, EntityManagerInterface $em): Response
    {
        $parentId = $sub->getParent()?->getId();
        if ($this->isCsrfTokenValid('del_sub_' . $sub->getId(), (string) $r->request->get('_token'))) {
            $em->remove($sub);
            $em->flush();
        }
        return $this->redirectToRoute('admin_subcategory_index', ['id' => $parentId]);
    }

    // ── Category Sections ─────────────────────────────────────────────────────

    #[Route('/category/{catId}/section', name: 'cat_section_index')]
    public function catSectionIndex(int $catId, CategoryRepository $cats): Response
    {
        $cat = $cats->find($catId) ?? throw $this->createNotFoundException();
        return $this->render('admin/section/index.html.twig', [
            'page'     => null,
            'category' => $cat,
            'sections' => $cat->getSections(),
        ]);
    }

    #[Route('/category/{catId}/section/new', name: 'cat_section_new', methods: ['GET', 'POST'])]
    public function catSectionNew(int $catId, Request $r, EntityManagerInterface $em, CategoryRepository $cats): Response
    {
        $cat = $cats->find($catId) ?? throw $this->createNotFoundException();
        $section = new PageSection();
        if ($r->isMethod('POST')) {
            $section->setCategory($cat);
            $section->setTitlePart1($r->request->get('titlePart1') ?: null);
            $section->setTitlePart2($r->request->get('titlePart2') ?: null);
            $section->setActive((bool) $r->request->get('active'));
            $em->persist($section);
            $em->flush();
            $this->addFlash('success', 'Seção criada.');
            return $this->redirectToRoute('admin_cat_section_index', ['catId' => $catId]);
        }
        return $this->render('admin/section/new.html.twig', [
            'page'     => null,
            'category' => $cat,
            'section'  => $section,
        ]);
    }

    // ── ContactMessage ────────────────────────────────────────────────────────

    #[Route('/contact', name: 'contact_index')]
    public function contactIndex(ContactMessageRepository $repo): Response
    {
        return $this->render('admin/contact/index.html.twig', ['messages' => $repo->findBy([], ['createdAt' => 'DESC'])]);
    }

    #[Route('/contact/{id}/read', name: 'contact_read', methods: ['POST'])]
    public function contactRead(ContactMessage $msg, EntityManagerInterface $em): Response
    {
        $msg->setIsRead(true);
        $em->flush();
        return $this->redirectToRoute('admin_contact_index');
    }

    #[Route('/contact/{id}/delete', name: 'contact_delete', methods: ['POST'])]
    public function contactDelete(ContactMessage $msg, Request $r, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_contact_' . $msg->getId(), (string) $r->request->get('_token'))) {
            $em->remove($msg);
            $em->flush();
        }
        return $this->redirectToRoute('admin_contact_index');
    }

    // ── Newsletter ────────────────────────────────────────────────────────────

    #[Route('/newsletter', name: 'newsletter_index')]
    public function newsletterIndex(NewsletterSubscriberRepository $repo): Response
    {
        return $this->render('admin/newsletter/index.html.twig', ['subscribers' => $repo->findAll()]);
    }

    #[Route('/newsletter/{id}/delete', name: 'newsletter_delete', methods: ['POST'])]
    public function newsletterDelete(NewsletterSubscriber $sub, Request $r, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_nl_' . $sub->getId(), (string) $r->request->get('_token'))) {
            $em->remove($sub);
            $em->flush();
        }
        return $this->redirectToRoute('admin_newsletter_index');
    }

    #[Route('/newsletter/export.csv', name: 'newsletter_export')]
    public function newsletterExport(NewsletterSubscriberRepository $repo): \Symfony\Component\HttpFoundation\Response
    {
        $rows = $repo->findAll();
        $csv  = "id,email,data\n";
        foreach ($rows as $s) {
            $csv .= sprintf("%d,%s,%s\n", $s->getId(), $s->getEmail(), $s->getCreatedAt()->format('Y-m-d H:i'));
        }
        return new \Symfony\Component\HttpFoundation\Response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="newsletter.csv"',
        ]);
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    #[Route('/page', name: 'page_index')]
    public function pageIndex(PageRepository $repo): Response
    {
        return $this->render('admin/page/index.html.twig', ['pages' => $repo->findBy([], ['position' => 'ASC', 'title' => 'ASC'])]);
    }

    #[Route('/page/reorder', name: 'page_reorder', methods: ['POST'])]
    public function pageReorder(Request $r, PageRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        return $this->reorderEntities($r, $repo, $em);
    }

    #[Route('/page/new', name: 'page_new', methods: ['GET', 'POST'])]
    public function pageNew(Request $r, EntityManagerInterface $em, TenantContext $tc, SluggerInterface $slugger, CategoryRepository $cats): Response
    {
        $page = new Page();
        if ($r->isMethod('POST')) {
            $page->setTenant($tc->requireTenant());
            $this->populatePage($page, $r, $slugger, $em, $cats);
            $em->persist($page);
            $em->flush();
            $this->addFlash('success', 'Página criada.');
            return $this->redirectToRoute('admin_page_index');
        }
        return $this->render('admin/page/new.html.twig', ['page' => $page, 'categories' => $cats->findAll()]);
    }

    #[Route('/page/{id}/edit', name: 'page_edit', methods: ['GET', 'POST'])]
    public function pageEdit(Page $page, Request $r, EntityManagerInterface $em, SluggerInterface $slugger, CategoryRepository $cats): Response
    {
        if ($r->isMethod('POST')) {
            $this->populatePage($page, $r, $slugger, $em, $cats);
            $em->flush();
            $this->addFlash('success', 'Página atualizada.');
            return $this->redirectToRoute('admin_page_index');
        }
        return $this->render('admin/page/edit.html.twig', ['page' => $page, 'categories' => $cats->findAll()]);
    }

    #[Route('/page/{id}/delete', name: 'page_delete', methods: ['POST'])]
    public function pageDelete(Page $page, Request $r, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_page_' . $page->getId(), (string) $r->request->get('_token'))) {
            $em->remove($page);
            $em->flush();
        }
        return $this->redirectToRoute('admin_page_index');
    }

    // ── PageSection ───────────────────────────────────────────────────────────

    #[Route('/page/{pageId}/section', name: 'section_index')]
    public function sectionIndex(int $pageId, PageRepository $pages): Response
    {
        $page = $pages->find($pageId) ?? throw $this->createNotFoundException();
        return $this->render('admin/section/index.html.twig', ['page' => $page, 'sections' => $page->getSections()]);
    }

    #[Route('/page/{pageId}/section/new', name: 'section_new', methods: ['GET', 'POST'])]
    public function sectionNew(int $pageId, Request $r, EntityManagerInterface $em, PageRepository $pages): Response
    {
        $page = $pages->find($pageId) ?? throw $this->createNotFoundException();
        $section = new PageSection();
        if ($r->isMethod('POST')) {
            $section->setPage($page);
            $this->populateSection($section, $r);
            $em->persist($section);
            $em->flush();
            $this->addFlash('success', 'Seção criada.');
            return $this->redirectToRoute('admin_section_index', ['pageId' => $pageId]);
        }
        return $this->render('admin/section/new.html.twig', ['page' => $page, 'section' => $section]);
    }

    #[Route('/section/{id}/edit', name: 'section_edit', methods: ['GET', 'POST'])]
    public function sectionEdit(PageSection $section, Request $r, EntityManagerInterface $em): Response
    {
        if ($r->isMethod('POST')) {
            $this->populateSection($section, $r);
            $em->flush();
            $this->addFlash('success', 'Seção atualizada.');
            if ($section->getCategory()) {
                return $this->redirectToRoute('admin_cat_section_index', ['catId' => $section->getCategory()->getId()]);
            }
            return $this->redirectToRoute('admin_section_index', ['pageId' => $section->getPage()?->getId()]);
        }
        return $this->render('admin/section/edit.html.twig', ['section' => $section]);
    }

    private function populateSection(PageSection $section, Request $r): void
    {
        $section->setTitlePart1($r->request->get('titlePart1') ?: null);
        $section->setTitlePart2($r->request->get('titlePart2') ?: null);
        $section->setActive((bool) $r->request->get('active'));
        
        // Background type and cleanups
        $bgType = $r->request->get('bgType', 'none');
        $section->setBgType($bgType);
        
        if ($bgType === 'color') {
            $section->setBgColor($r->request->get('bgColor') ?: null);
            $section->setBgGradient(null);
        } elseif ($bgType === 'gradient') {
            $section->setBgColor(null);
            $section->setBgGradient($r->request->get('bgGradient') ?: null);
        } elseif ($bgType === 'video') {
            $section->setBgColor($r->request->get('bgColor') ?: null); // Video overlay color
            $section->setBgGradient(null);
        } else {
            // none or image
            $section->setBgColor(null);
            $section->setBgGradient(null);
        }
        
        $section->setBgImageOpacity((int) ($r->request->get('bgImageOpacity') ?? 100));
        $section->setBgImagePosition($r->request->get('bgImagePosition', 'center'));
        $bgImg = $r->files->get('bgImageFile');
        if ($bgImg instanceof UploadedFile && $bgImg->isValid()) { $section->setBgImageFile($bgImg); }
        $bgVid = $r->files->get('bgVideoFile');
        if ($bgVid instanceof UploadedFile && $bgVid->isValid()) { $section->setBgVideoFile($bgVid); }
    }


    #[Route('/section/{id}/delete', name: 'section_delete', methods: ['POST'])]
    public function sectionDelete(PageSection $section, Request $r, EntityManagerInterface $em): Response
    {
        $catId  = $section->getCategory()?->getId();
        $pageId = $section->getPage()?->getId();
        if ($this->isCsrfTokenValid('del_sec_' . $section->getId(), (string) $r->request->get('_token'))) {
            $em->remove($section);
            $em->flush();
        }
        if ($catId) {
            return $this->redirectToRoute('admin_cat_section_index', ['catId' => $catId]);
        }
        return $this->redirectToRoute('admin_section_index', ['pageId' => $pageId]);
    }

    #[Route('/section/reorder', name: 'section_reorder', methods: ['POST'])]
    public function sectionReorder(Request $r, PageSectionRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        return $this->reorderEntities($r, $repo, $em);
    }

    // ── PageBlock ─────────────────────────────────────────────────────────────

    #[Route('/section/{sectionId}/block', name: 'block_index')]
    public function blockIndex(int $sectionId, PageSectionRepository $sections): Response
    {
        $section = $sections->find($sectionId) ?? throw $this->createNotFoundException();
        return $this->render('admin/block/index.html.twig', ['section' => $section, 'blocks' => $section->getBlocks()]);
    }

    #[Route('/section/{sectionId}/block/new', name: 'block_new', methods: ['GET', 'POST'])]
    public function blockNew(
        int $sectionId, Request $r, EntityManagerInterface $em,
        PageSectionRepository $sections, CategoryRepository $cats,
        PageRepository $pages
    ): Response {
        $section = $sections->find($sectionId) ?? throw $this->createNotFoundException();
        $block = new PageBlock();
        $type  = $r->query->get('type', $r->request->get('type', ''));

        if ($r->isMethod('POST')) {
            $this->populateBlock($block, $r, $em, $cats);
            $block->setSection($section);
            $em->persist($block);
            $em->flush();
            $this->addFlash('success', 'Bloco criado.');
            return $this->redirectToRoute('admin_block_index', ['sectionId' => $sectionId]);
        }
        return $this->render('admin/block/new.html.twig', [
            'section'    => $section,
            'block'      => $block,
            'type'       => $type ?: null,
            'categories' => $cats->findRootCategories(),
            'pages'      => $pages->findBy([], ['position' => 'ASC', 'title' => 'ASC']),
        ]);
    }

    #[Route('/block/{id}/edit', name: 'block_edit', methods: ['GET', 'POST'])]
    public function blockEdit(PageBlock $block, Request $r, EntityManagerInterface $em, CategoryRepository $cats, PageRepository $pages): Response
    {
        if ($r->isMethod('POST')) {
            $this->populateBlock($block, $r, $em, $cats);
            $em->flush();
            $this->addFlash('success', 'Bloco atualizado.');
            return $this->redirectToRoute('admin_block_index', ['sectionId' => $block->getSection()?->getId()]);
        }
        return $this->render('admin/block/edit.html.twig', [
            'block'      => $block,
            'type'       => $block->getType(),
            'categories' => $cats->findRootCategories(),
            'pages'      => $pages->findBy([], ['position' => 'ASC', 'title' => 'ASC']),
        ]);
    }

    private function populateBlock(PageBlock $block, Request $r, EntityManagerInterface $em, CategoryRepository $cats): void
    {
        $type = $r->request->get('type', $block->getType() ?: 'text_image');
        $block->setType($type);
        $block->setPreTitle($r->request->get('preTitle') ?: null);
        $block->setTitle($r->request->get('title') ?: null);
        $block->setText($r->request->get('text') ?: null);
        $block->setEmbedUrl($r->request->get('embedUrl') ?: null);
        $block->setItemCount($r->request->get('itemCount') !== null ? (int)$r->request->get('itemCount') ?: null : null);

        // Related category
        $catId = (int) $r->request->get('relatedCategoryId');
        $block->setRelatedCategory($catId ? $cats->find($catId) : null);

        // Config JSON
        $cfg = $r->request->all('config');
        $block->setConfig($cfg ?: null);

        // Main image (text_image)
        $file = $r->files->get('imageFile');
        if ($file instanceof UploadedFile && $file->isValid()) { $block->setImageFile($file); }

        // Gallery images
        if ($type === 'gallery') {
            $delIds = array_filter(array_map('intval', (array)$r->request->all('delete_gallery')));
            foreach ($block->getGalleryImages() as $img) {
                if (in_array($img->getId(), $delIds, true)) { $em->remove($img); }
            }
            foreach ((array)$r->files->all('galleryFiles') as $gFile) {
                if (!$gFile instanceof UploadedFile || !$gFile->isValid()) { continue; }
                $img = new \App\Entity\PageBlockImage();
                $img->setBlock($block);
                $img->setFile($gFile);
                $img->setPosition($block->getGalleryImages()->count());
                $em->persist($img);
            }
        }

        // Testimonials
        if ($type === 'testimonials') {
            $delIds = array_filter(array_map('intval', (array)$r->request->all('delete_testimonial')));
            foreach ($block->getTestimonials() as $t) {
                if (in_array($t->getId(), $delIds, true)) { $em->remove($t); }
            }
            $items = $r->request->all('testimonials');
            $files = $r->files->all('testimonials');
            foreach ($items as $idx => $data) {
                if (!empty($data['id'])) {
                    // Find existing and update
                    foreach ($block->getTestimonials() as $t) {
                        if ($t->getId() === (int)$data['id'] && !in_array($t->getId(), $delIds, true)) {
                            $t->setName($data['name'] ?? '');
                            $t->setRole($data['role'] ?? null);
                            $t->setText($data['text'] ?? '');
                            $t->setRating((int)($data['rating'] ?? 5));
                            if (isset($files[$idx]['avatarFile']) && $files[$idx]['avatarFile'] instanceof UploadedFile && $files[$idx]['avatarFile']->isValid()) {
                                $t->setAvatarFile($files[$idx]['avatarFile']);
                            }
                        }
                    }
                } else {
                    $t = new \App\Entity\PageBlockTestimonial();
                    $t->setBlock($block);
                    $t->setName($data['name'] ?? '');
                    $t->setRole($data['role'] ?? null);
                    $t->setText($data['text'] ?? '');
                    $t->setRating((int)($data['rating'] ?? 5));
                    $t->setPosition($idx);
                    if (isset($files[$idx]['avatarFile']) && $files[$idx]['avatarFile'] instanceof UploadedFile && $files[$idx]['avatarFile']->isValid()) {
                        $t->setAvatarFile($files[$idx]['avatarFile']);
                    }
                    $em->persist($t);
                }
            }
        }

        // Partner logos
        if ($type === 'partner_logos') {
            $delIds = array_filter(array_map('intval', (array)$r->request->all('delete_logo')));
            foreach ($block->getPartnerLogos() as $l) {
                if (in_array($l->getId(), $delIds, true)) { $em->remove($l); }
            }
            $logos = $r->request->all('logos');
            $logoFiles = $r->files->all('logos');
            foreach ($logos as $idx => $data) {
                if (!empty($data['id'])) {
                    foreach ($block->getPartnerLogos() as $l) {
                        if ($l->getId() === (int)$data['id'] && !in_array($l->getId(), $delIds, true)) {
                            $l->setName($data['name'] ?? null);
                            $l->setUrl($data['url'] ?? null);
                            if (isset($logoFiles[$idx]['logoFile']) && $logoFiles[$idx]['logoFile'] instanceof UploadedFile && $logoFiles[$idx]['logoFile']->isValid()) {
                                $l->setLogoFile($logoFiles[$idx]['logoFile']);
                            }
                        }
                    }
                }
            }
            // Bulk upload
            foreach ((array)$r->files->all('logoFiles') as $lf) {
                if (!$lf instanceof UploadedFile || !$lf->isValid()) { continue; }
                $l = new \App\Entity\PageBlockPartnerLogo();
                $l->setBlock($block);
                $l->setLogoFile($lf);
                $l->setPosition($block->getPartnerLogos()->count());
                $em->persist($l);
            }
        }

        // Team members
        if ($type === 'team') {
            $delIds = array_filter(array_map('intval', (array)$r->request->all('delete_member')));
            foreach ($block->getTeamMembers() as $m) {
                if (in_array($m->getId(), $delIds, true)) {
                    $em->remove($m);
                }
            }
            $items = $r->request->all('team') ?: [];
            $files = $r->files->all('team') ?: [];
            foreach ($items as $idx => $data) {
                if (!empty($data['id'])) {
                    // Find existing and update
                    foreach ($block->getTeamMembers() as $m) {
                        if ($m->getId() === (int)$data['id'] && !in_array($m->getId(), $delIds, true)) {
                            $m->setName($data['name'] ?? '');
                            $m->setRole($data['role'] ?? null);
                            $m->setBio($data['bio'] ?? null);
                            $m->setLinkedinUrl($data['linkedinUrl'] ?? null);
                            $m->setFacebookUrl($data['facebookUrl'] ?? null);
                            $m->setInstagramUrl($data['instagramUrl'] ?? null);
                            $m->setWhatsappUrl($data['whatsappUrl'] ?? null);
                            $m->setPhone($data['phone'] ?? null);
                            $m->setEmail($data['email'] ?? null);
                            $m->setPosition($idx);
                            if (isset($files[$idx]['imageFile']) && $files[$idx]['imageFile'] instanceof UploadedFile && $files[$idx]['imageFile']->isValid()) {
                                $m->setImageFile($files[$idx]['imageFile']);
                            }
                        }
                    }
                } else {
                    $m = new \App\Entity\PageBlockTeamMember();
                    $m->setBlock($block);
                    $m->setName($data['name'] ?? '');
                    $m->setRole($data['role'] ?? null);
                    $m->setBio($data['bio'] ?? null);
                    $m->setLinkedinUrl($data['linkedinUrl'] ?? null);
                    $m->setFacebookUrl($data['facebookUrl'] ?? null);
                    $m->setInstagramUrl($data['instagramUrl'] ?? null);
                    $m->setWhatsappUrl($data['whatsappUrl'] ?? null);
                    $m->setPhone($data['phone'] ?? null);
                    $m->setEmail($data['email'] ?? null);
                    $m->setPosition($idx);
                    if (isset($files[$idx]['imageFile']) && $files[$idx]['imageFile'] instanceof UploadedFile && $files[$idx]['imageFile']->isValid()) {
                        $m->setImageFile($files[$idx]['imageFile']);
                    }
                    $em->persist($m);
                }
            }
        }

        // Banners (multi-slide support)
        if ($type === 'banner') {
            $banners = $r->request->all('banners') ?: [];
            $files = $r->files->all('banners') ?: [];
            $savedBanners = [];
            $existingBanners = $block->getConfig()['banners'] ?? [];

            foreach ($banners as $idx => $data) {
                $slideImage = $data['image'] ?? null;

                if (isset($files[$idx]['imageFile']) && $files[$idx]['imageFile'] instanceof UploadedFile && $files[$idx]['imageFile']->isValid()) {
                    /** @var UploadedFile $uploadedFile */
                    $uploadedFile = $files[$idx]['imageFile'];
                    $extension = $uploadedFile->guessExtension() ?: 'bin';
                    $newFilename = uniqid('banner_', true) . '.' . $extension;
                    $targetDir = $this->getParameter('kernel.project_dir') . '/public/uploads/page_block';
                    $uploadedFile->move($targetDir, $newFilename);
                    $slideImage = $newFilename;
                }

                if (empty($slideImage) && isset($existingBanners[$idx]['image'])) {
                    $slideImage = $existingBanners[$idx]['image'];
                }

                $savedBanners[] = [
                    'title' => $data['title'] ?? '',
                    'text' => $data['text'] ?? '',
                    'ctaText' => $data['ctaText'] ?? '',
                    'ctaLink' => $data['ctaLink'] ?? '',
                    'active' => (isset($data['active']) && $data['active'] === '1') ? '1' : '0',
                    'image' => $slideImage,
                ];
            }

            $cfg = $block->getConfig() ?: [];
            $cfg['banners'] = $savedBanners;
            $reqCfg = $r->request->all('config') ?: [];
            foreach ($reqCfg as $k => $v) {
                if ($k !== 'banners') {
                    $cfg[$k] = $v;
                }
            }
            $block->setConfig($cfg);

            $firstSlide = null;
            foreach ($savedBanners as $b) {
                if (($b['active'] ?? '0') === '1') {
                    $firstSlide = $b;
                    break;
                }
            }
            if (!$firstSlide && !empty($savedBanners)) {
                $firstSlide = $savedBanners[0];
            }

            if ($firstSlide) {
                $block->setTitle($firstSlide['title'] ?? null);
                $block->setText($firstSlide['text'] ?? null);
                $block->setImage($firstSlide['image'] ?? null);
                $cfg['ctaText'] = $firstSlide['ctaText'] ?? '';
                $cfg['ctaLink'] = $firstSlide['ctaLink'] ?? '';
                $block->setConfig($cfg);
            } else {
                $block->setTitle(null);
                $block->setText(null);
                $block->setImage(null);
            }
        }
    }

    #[Route('/block/{id}/delete', name: 'block_delete', methods: ['POST'])]
    public function blockDelete(PageBlock $block, Request $r, EntityManagerInterface $em): Response
    {
        $sectionId = $block->getSection()?->getId();
        if ($this->isCsrfTokenValid('del_block_' . $block->getId(), (string) $r->request->get('_token'))) {
            $em->remove($block);
            $em->flush();
        }
        return $this->redirectToRoute('admin_block_index', ['sectionId' => $sectionId]);
    }

    #[Route('/block/reorder', name: 'block_reorder', methods: ['POST'])]
    public function blockReorder(Request $r, PageBlockRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        return $this->reorderEntities($r, $repo, $em);
    }

    // ── Duplicar ──────────────────────────────────────────────────────────────

    #[Route('/page/{id}/duplicate', name: 'page_duplicate', methods: ['POST'])]
    public function pageDuplicate(Page $page, Request $r, \App\Service\DuplicatorService $dup): Response
    {
        if ($this->isCsrfTokenValid('dup_page_' . $page->getId(), (string) $r->request->get('_token'))) {
            $dup->duplicatePage($page);
            $this->addFlash('success', 'Página duplicada.');
        }
        return $this->redirectToRoute('admin_page_index');
    }

    #[Route('/section/{id}/duplicate', name: 'section_duplicate', methods: ['POST'])]
    public function sectionDuplicate(PageSection $section, Request $r, \App\Service\DuplicatorService $dup): Response
    {
        $pageId = $section->getPage()?->getId();
        $catId  = $section->getCategory()?->getId();
        if ($this->isCsrfTokenValid('dup_sec_' . $section->getId(), (string) $r->request->get('_token'))) {
            $dup->duplicateSection($section);
            $this->addFlash('success', 'Seção duplicada.');
        }
        if ($catId) return $this->redirectToRoute('admin_cat_section_index', ['catId' => $catId]);
        return $this->redirectToRoute('admin_section_index', ['pageId' => $pageId]);
    }

    #[Route('/block/{id}/duplicate', name: 'block_duplicate', methods: ['POST'])]
    public function blockDuplicate(PageBlock $block, Request $r, \App\Service\DuplicatorService $dup): Response
    {
        $sectionId = $block->getSection()?->getId();
        if ($this->isCsrfTokenValid('dup_block_' . $block->getId(), (string) $r->request->get('_token'))) {
            $dup->duplicateBlock($block);
            $this->addFlash('success', 'Bloco duplicado.');
        }
        return $this->redirectToRoute('admin_block_index', ['sectionId' => $sectionId]);
    }



    // ── Editor Users (managed by Tenant Admin) ────────────────────────────────

    #[Route('/editors', name: 'editor_index')]
    public function editorIndex(UserRepository $users): Response
    {
        /** @var User $me */
        $me = $this->getUser();
        return $this->render('admin/editor/index.html.twig', [
            'editors' => $users->findBy(['tenant' => $me->getTenant(), 'workGroup' => [1, 2]]),
        ]);
    }

    #[Route('/editors/new', name: 'editor_new', methods: ['GET', 'POST'])]
    public function editorNew(Request $r, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var User $me */
        $me = $this->getUser();
        $editor = new User();
        if ($r->isMethod('POST')) {
            $editor->setUsername((string) $r->request->get('username'));
            $editor->setName((string) $r->request->get('name'));
            $editor->setEmail($r->request->get('email') ?: null);
            $editor->setWorkGroup((int) $r->request->get('workGroup', 1));
            $editor->setTenant($me->getTenant());
            $editor->setPassword($hasher->hashPassword($editor, (string) $r->request->get('password')));
            $em->persist($editor);
            $em->flush();
            $this->addFlash('success', 'Usuário criado.');
            return $this->redirectToRoute('admin_editor_index');
        }
        return $this->render('admin/editor/new.html.twig', ['editor' => $editor]);
    }

    #[Route('/editors/{id}/delete', name: 'editor_delete', methods: ['POST'])]
    public function editorDelete(User $editor, Request $r, EntityManagerInterface $em): Response
    {
        /** @var User $me */
        $me = $this->getUser();
        if ($editor->getTenant() === $me->getTenant()) {
            if ($this->isCsrfTokenValid('del_editor_' . $editor->getId(), (string) $r->request->get('_token'))) {
                $em->remove($editor);
                $em->flush();
            }
        }
        return $this->redirectToRoute('admin_editor_index');
    }

    // ── Tenant Settings ───────────────────────────────────────────────────────

    #[Route('/settings', name: 'settings', methods: ['GET', 'POST'])]
    public function settings(
        Request $r,
        EntityManagerInterface $em,
        TenantContext $tc,
        PageRepository $pageRepo
    ): Response {
        $tenant = $tc->requireTenant();
        if ($r->isMethod('POST')) {
            $tenant->setTheme((string) $r->request->get('theme', 'wab'));
            $tenant->setPrimaryColor($r->request->get('primaryColor') ?: '#0044cc');
            $tenant->setSecondaryColor($r->request->get('secondaryColor') ?: '#ffaa00');
            $tenant->setPrimaryColorDark($r->request->get('primaryColorDark') ?: '#3b82f6');
            $tenant->setSecondaryColorDark($r->request->get('secondaryColorDark') ?: '#fbbf24');

            // Home page
            $homePageId = (int) $r->request->get('homePageId');
            $tenant->setHomePage($homePageId ? $pageRepo->find($homePageId) : null);
            // SEO
            $tenant->setSeoTitle($r->request->get('seoTitle') ?: null);
            $tenant->setSeoDescription($r->request->get('seoDescription') ?: null);
            $tenant->setSeoKeywords($r->request->get('seoKeywords') ?: null);
            $tenant->setOgImage($r->request->get('ogImage') ?: null);
            // Favicon
            $faviconFile = $r->files->get('faviconFile');
            if ($faviconFile instanceof UploadedFile && $faviconFile->isValid()) { $tenant->setFaviconFile($faviconFile); }
            // Contact
            $tenant->setContactEmail($r->request->get('contactEmail') ?: null);
            $tenant->setPhone($r->request->get('phone') ?: null);
            $tenant->setAddress($r->request->get('address') ?: null);
            $tenant->setMapsEmbedUrl($r->request->get('mapsEmbedUrl') ?: null);
            // Social
            $tenant->setYoutubeLink($r->request->get('youtubeLink') ?: null);
            $tenant->setInstagramLink($r->request->get('instagramLink') ?: null);
            $tenant->setFacebookLink($r->request->get('facebookLink') ?: null);
            $tenant->setWhatsappLink($r->request->get('whatsappLink') ?: null);
            $tenant->setLinkedinLink($r->request->get('linkedinLink') ?: null);

            // Menu and TopBar settings
            $showMenuIcons = (bool) $r->request->get('showMenuIcons');
            $topBarEnabled = (bool) $r->request->get('topBarEnabled');
            $topBarLeft = $r->request->all('topBarLeft');
            $topBarRight = $r->request->all('topBarRight');

            $tenant->setNavigationSettings([
                'showMenuIcons' => $showMenuIcons,
                'topBarEnabled' => $topBarEnabled,
                'topBarLeft'    => $topBarLeft,
                'topBarRight'   => $topBarRight,
            ]);

            $em->flush();
            $this->addFlash('success', 'Configurações salvas.');
            return $this->redirectToRoute('admin_settings');
        }
        return $this->render('admin/settings.html.twig', [
            'tenant' => $tenant,
            'pages'  => $pageRepo->findBy([], ['position' => 'ASC', 'title' => 'ASC']),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function reorderEntities(Request $r, $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode((string) $r->getContent(), true);
        $ids = $data['ids'] ?? [];
        foreach ($ids as $position => $id) {
            $entity = $repo->find((int) $id);
            if ($entity && method_exists($entity, 'setPosition')) {
                $entity->setPosition($position);
            }
        }
        $em->flush();
        return new JsonResponse(['ok' => true]);
    }

    private function populateCategory(Category $cat, Request $r, SluggerInterface $slugger): void
    {
        $cat->setName((string) $r->request->get('name'));
        $cat->setSlug($r->request->get('slug') ?: strtolower((string) $slugger->slug($cat->getName())));
        $cat->setPreTitle($r->request->get('preTitle') ?: null);
        $cat->setDescription($r->request->get('description') ?: null);
        $cat->setShowInHeader((bool) $r->request->get('showInHeader'));
        $cat->setShowInFooter((bool) $r->request->get('showInFooter'));
        $cat->setIcon($r->request->get('icon') ?: null);
    }

    private function populatePage(Page $page, Request $r, SluggerInterface $slugger, ?EntityManagerInterface $em = null, ?CategoryRepository $cats = null): void
    {
        $page->setTitle((string) $r->request->get('title'));
        $page->setSlug($r->request->get('slug') ?: strtolower((string) $slugger->slug($page->getTitle())));
        $page->setShowInHeader((bool) $r->request->get('showInHeader'));
        $page->setShowInFooter((bool) $r->request->get('showInFooter'));
        $page->setSeoTitle($r->request->get('seoTitle') ?: null);
        $page->setSeoDescription($r->request->get('seoDescription') ?: null);
        $page->setShowTitle((bool) $r->request->get('showTitle'));
        // Category
        if ($cats) {
            $catId = (int) $r->request->get('categoryId');
            $page->setCategory($catId ? $cats->find($catId) : null);
        }
        // Cover image
        $coverFile = $r->files->get('coverImageFile');
        if ($coverFile instanceof UploadedFile && $coverFile->isValid()) { $page->setCoverImageFile($coverFile); }
    }

}
