<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\PageBlock;
use App\Entity\PageBlockImage;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageBlockPartnerLogo;
use App\Entity\PageBlockTeamMember;
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
            $newPage->getSections()->add($copy);
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
            $newSection->getBlocks()->add($copy);
        }
        $copy->getGalleryImages()->clear();
        $copy->getTestimonials()->clear();
        $copy->getPartnerLogos()->clear();
        $copy->getTeamMembers()->clear();
        $this->em->persist($copy);

        // Clone gallery images (filenames only — shared files)
        foreach ($source->getGalleryImages() as $img) {
            $c = new PageBlockImage();
            $c->setBlock($copy);
            $c->setFilename($img->getFilename());
            $c->setCaption($img->getCaption() ?? null);
            $c->setPosition($img->getPosition());
            $this->em->persist($c);
            $copy->getGalleryImages()->add($c);
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
            $copy->getTestimonials()->add($c);
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
            $copy->getPartnerLogos()->add($c);
        }

        // Clone team members
        foreach ($source->getTeamMembers() as $m) {
            $c = new PageBlockTeamMember();
            $c->setBlock($copy);
            $c->setName($m->getName());
            $c->setRole($m->getRole());
            $c->setBio($m->getBio());
            $c->setImage($m->getImage());
            $c->setLinkedinUrl($m->getLinkedinUrl());
            $c->setFacebookUrl($m->getFacebookUrl());
            $c->setInstagramUrl($m->getInstagramUrl());
            $c->setWhatsappUrl($m->getWhatsappUrl());
            $c->setPhone($m->getPhone());
            $c->setEmail($m->getEmail());
            $c->setPosition($m->getPosition());
            $this->em->persist($c);
            $copy->getTeamMembers()->add($c);
        }

        if (!$newSection) {
            $this->em->flush();
        }
        return $copy;
    }
}
