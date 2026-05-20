<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\PageBlock;
use App\Entity\PageBlockImage;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageBlockPartnerLogo;
use Doctrine\ORM\EntityManagerInterface;

class DuplicatorService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function duplicatePage(Page $source): Page
    {
        $copy = clone $source;
        $copy->setTitle($source->getTitle() . ' (cópia)');
        $copy->setSlug($source->getSlug() . '-' . uniqid());
        $copy->setPosition(999);
        // Clear sections collection — we'll re-add cloned ones
        $copy->getSections()->clear();
        $this->em->persist($copy);

        foreach ($source->getSections() as $section) {
            $this->duplicateSection($section, $copy);
        }

        $this->em->flush();
        return $copy;
    }

    public function duplicateSection(PageSection $source, ?Page $newPage = null): PageSection
    {
        $copy = clone $source;
        if ($newPage) {
            $copy->setPage($newPage);
        }
        // Clear blocks collection
        $copy->getBlocks()->clear();
        $this->em->persist($copy);

        foreach ($source->getBlocks() as $block) {
            $this->duplicateBlock($block, $copy);
        }

        if (!$newPage) {
            $this->em->flush();
        }
        return $copy;
    }

    public function duplicateBlock(PageBlock $source, ?PageSection $newSection = null): PageBlock
    {
        $copy = clone $source;
        if ($newSection) {
            $copy->setSection($newSection);
        }
        $copy->getGalleryImages()->clear();
        $copy->getTestimonials()->clear();
        $copy->getPartnerLogos()->clear();
        $this->em->persist($copy);

        // Clone gallery images (filenames only — shared files)
        foreach ($source->getGalleryImages() as $img) {
            $c = new PageBlockImage();
            $c->setBlock($copy);
            $c->setFilename($img->getFilename());
            $c->setCaption($img->getCaption() ?? null);
            $c->setPosition($img->getPosition());
            $this->em->persist($c);
        }

        // Clone testimonials
        foreach ($source->getTestimonials() as $t) {
            $c = new PageBlockTestimonial();
            $c->setBlock($copy);
            $c->setName($t->getName());
            $c->setRole($t->getRole());
            $c->setText($t->getText());
            $c->setRating($t->getRating());
            $c->setAvatar($t->getAvatar());
            $c->setPosition($t->getPosition());
            $this->em->persist($c);
        }

        // Clone partner logos
        foreach ($source->getPartnerLogos() as $l) {
            $c = new PageBlockPartnerLogo();
            $c->setBlock($copy);
            $c->setName($l->getName());
            $c->setLogoFilename($l->getLogoFilename());
            $c->setUrl($l->getUrl());
            $c->setPosition($l->getPosition());
            $this->em->persist($c);
        }

        if (!$newSection) {
            $this->em->flush();
        }
        return $copy;
    }
}
