<?php

namespace App\Entity\Enum;

enum BlockType: string
{
    case TextImage     = 'text_image';
    case TextOnly      = 'text_only';
    case Gallery       = 'gallery';
    case Newsletter    = 'newsletter';
    case Stats         = 'stats';
    case NewsCall      = 'news_call';
    case Map           = 'map';
    case SubCategories = 'sub_categories';
    case PageList      = 'page_list';
    case Blurbs4       = 'blurbs4';
    case Testimonials  = 'testimonials';
    case PartnerLogos  = 'partner_logos';
    case Banner        = 'banner';
    case Team          = 'team';
    case Contact       = 'contact';
    case HeroSplit     = 'hero_split';
    case Highlights    = 'highlights';
    case Portfolio     = 'portfolio';
    case Statement     = 'statement';
    case Steps         = 'steps';
    case Cta           = 'cta';
    case Video         = 'video';

    public function label(): string
    {
        return match($this) {
            self::TextImage     => 'Imagem + Texto',
            self::TextOnly      => 'Apenas Texto',
            self::Gallery       => 'Galeria de Imagens',
            self::Newsletter    => 'Newsletter / Captura de E-mail',
            self::Stats         => 'Estatísticas',
            self::NewsCall      => 'Chamada para Notícia',
            self::Map           => 'Mapa',
            self::SubCategories => 'Listar Subcategorias',
            self::PageList      => 'Listar Páginas',
            self::Blurbs4       => 'Texto com 4 Blocos',
            self::Testimonials  => 'Depoimentos',
            self::PartnerLogos  => 'Logos de Parceiros',
            self::Banner        => 'Banner',
            self::Team          => 'Membros da Equipe',
            self::Contact       => 'Formulário de Contato',
            self::HeroSplit     => 'Destaque com foto',
            self::Highlights    => 'Faixa de destaques',
            self::Portfolio     => 'Portfólio com filtro',
            self::Statement     => 'Manifesto com imagem',
            self::Steps         => 'Etapas',
            self::Cta           => 'Chamada para ação',
            self::Video         => 'Vídeo',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::TextImage     => 'fa-solid fa-columns',
            self::TextOnly      => 'fa-solid fa-align-left',
            self::Gallery       => 'fa-solid fa-images',
            self::Newsletter    => 'fa-solid fa-envelope-open-text',
            self::Stats         => 'fa-solid fa-chart-bar',
            self::NewsCall      => 'fa-solid fa-newspaper',
            self::Map           => 'fa-solid fa-map-marker-alt',
            self::SubCategories => 'fa-solid fa-sitemap',
            self::PageList      => 'fa-solid fa-list',
            self::Blurbs4       => 'fa-solid fa-th-large',
            self::Testimonials  => 'fa-solid fa-quote-left',
            self::PartnerLogos  => 'fa-solid fa-handshake',
            self::Banner        => 'fa-solid fa-image',
            self::Team          => 'fa-solid fa-users',
            self::Contact       => 'fa-solid fa-envelope',
            self::HeroSplit     => 'fa-solid fa-panorama',
            self::Highlights    => 'fa-solid fa-grip-lines',
            self::Portfolio     => 'fa-solid fa-table-cells-large',
            self::Statement     => 'fa-solid fa-quote-right',
            self::Steps         => 'fa-solid fa-list-ol',
            self::Cta           => 'fa-solid fa-bullhorn',
            self::Video         => 'fa-solid fa-circle-play',
        };
    }
}
