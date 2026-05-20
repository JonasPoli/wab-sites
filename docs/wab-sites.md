# WAB Sites — Plano de Implementação Completo

> **Sistema:** Gerador de sites multi-tenant em Symfony  
> **Stack:** PHP 8.3 · Symfony 7 · Doctrine ORM · VichUploader · Twig  
> **Banco:** MySQL/MariaDB — todas as alterações via `doctrine:migrations`  
> **Admin:** Templates próprios (`wab_base.html.twig`) com Font Awesome, Icon Picker Modal, Shoelace Web Components

---

## Status por Módulo

| Legenda | Significado |
|---|---|
| ✅ | Implementado e migrado |
| 🔜 | Pendente de implementação |
| ⚠️ | Parcialmente feito |

---

## 1. Identidade e Branding

### 1.1 Renomear NEPE → WAB ✅
- `nepe_base.html.twig` substituído por `wab_base.html.twig`
- Todos os templates admin estendendo o novo base
- Logo "W WAB Sites" no topo do painel
- Tema padrão do `Tenant` alterado de `'nepe'` para `'wab'`

### 1.2 Remoção de módulos obsoletos ✅
Removidos completamente:
- `Article`, `ArticleApproval`, `ArticleImage` — entidades, repositórios, controller, templates
- `Message` — entidade, repositório, service
- `Study`, `StudyMaterial` — entidades, repositórios, métodos no controller
- `VideoSupport`, `VideoMaterial` — entidades, repositórios, métodos no controller, templates públicos
- `ResearchLine` — métodos do controller
- `TenantExtension` — removidas referências a `VideoSupportRepository` e `StudyRepository`
- Migrations aplicadas: tabelas `article`, `article_approval`, `article_image`, `message` dropadas

---

## 2. Entidades e Banco de Dados

> **Regra:** Todo ajuste de banco deve ser feito exclusivamente via `php bin/console doctrine:migrations:diff` + `doctrine:migrations:migrate`.

### 2.1 Tenant ✅
Campos adicionados:
| Campo | Tipo | Descrição |
|---|---|---|
| `homePage` | FK → Page | Página usada como home do site |
| `favicon` | varchar(255) | Filename do favicon (Vich) |
| `seoTitle` | varchar(255) | Título padrão para SEO |
| `seoDescription` | text | Meta description padrão |
| `seoKeywords` | varchar(500) | Meta keywords |
| `ogImage` | varchar(500) | URL da imagem Open Graph |

**Vich mapping:** `tenant_favicon` → `/uploads/tenant/favicon`

### 2.2 Category ✅
Campo adicionado:
| Campo | Tipo | Descrição |
|---|---|---|
| `icon` | varchar(100) | Classe Font Awesome (ex: `fa-solid fa-home`) |

### 2.3 Page ✅
Campos adicionados:
| Campo | Tipo | Descrição |
|---|---|---|
| `coverImage` | varchar(255) | Imagem de capa para cards (Vich) |
| `position` | int | Ordem de exibição (drag-and-drop) |
| `category` | FK → Category | Categoria a que a página pertence |
| `updatedAt` | datetime_immutable | Necessário para Vich |
| `seoTitle` | varchar(255) | Título SEO da página |
| `seoDescription` | text | Meta description da página |

**Vich mapping:** `page_cover_image` → `/uploads/page_cover`

### 2.4 PageSection ✅
Campos de background adicionados:
| Campo | Tipo | Padrão | Descrição |
|---|---|---|---|
| `bgType` | varchar(20) | `none` | `none` \| `color` \| `gradient` \| `image` \| `video` |
| `bgColor` | varchar(100) | null | Cor CSS (`#1e293b`) ou overlay do vídeo |
| `bgGradient` | varchar(500) | null | Args do `linear-gradient()` |
| `bgImage` | varchar(255) | null | Filename da imagem (Vich) |
| `bgImageOpacity` | int | 100 | Opacidade 0–100 |
| `bgImagePosition` | varchar(20) | `center` | `center\|top\|bottom\|left\|right` |
| `bgVideo` | varchar(255) | null | Filename do vídeo MP4 (Vich) |
| `updatedAt` | datetime_immutable | null | Necessário para Vich |

**Vich mappings:**
- `section_bg_image` → `/uploads/section/bg`
- `section_bg_video` → `/uploads/section/video`

### 2.5 PageBlock ✅
Campos adicionados:
| Campo | Tipo | Descrição |
|---|---|---|
| `type` | varchar(50) | Tipo do bloco (enum `BlockType`) |
| `config` | JSON | Configuração livre (blurbs, stats items, etc.) |
| `embedUrl` | varchar(1000) | URL de embed (Google Maps) |
| `itemCount` | int | Qtd de itens a exibir (news_call, page_list) |
| `relatedCategory` | FK → Category | Categoria fonte (sub_categories, page_list) |

Coleções adicionadas:
- `galleryImages` → `PageBlockImage` (galeria)
- `testimonials` → `PageBlockTestimonial` (depoimentos)
- `partnerLogos` → `PageBlockPartnerLogo` (logos de parceiros)

### 2.6 PageBlockImage ✅
Tabela para imagens da galeria.  
**Vich mapping:** `page_block_gallery` → `/uploads/page_block_gallery`

### 2.7 PageBlockTestimonial ✅
| Campo | Tipo | Descrição |
|---|---|---|
| `block` | FK → PageBlock | |
| `name` | varchar(255) | Nome do depoente |
| `role` | varchar(255) | Cargo/função |
| `text` | text | Texto do depoimento |
| `rating` | smallint | 1–5 estrelas |
| `avatar` | varchar(255) | Foto do depoente (Vich) |
| `position` | int | Ordem |

**Vich mapping:** `testimonial_avatar` → `/uploads/testimonial_avatar`

### 2.8 PageBlockPartnerLogo ✅
| Campo | Tipo | Descrição |
|---|---|---|
| `block` | FK → PageBlock | |
| `name` | varchar(255) | Nome do parceiro |
| `logoFilename` | varchar(255) | Arquivo do logo (Vich) |
| `url` | varchar(500) | Link externo (opcional) |
| `position` | int | Ordem |

**Vich mapping:** `partner_logo` → `/uploads/partner_logo`

### 2.9 ContactFormField ✅
| Campo | Tipo | Descrição |
|---|---|---|
| `tenant` | FK → Tenant | |
| `label` | varchar(255) | Rótulo do campo |
| `type` | varchar(20) | `text\|email\|tel\|textarea\|select` |
| `options` | JSON | Opções para tipo `select` |
| `required` | bool | Campo obrigatório? |
| `position` | int | Ordem |

### 2.10 ContactMessage ✅
Campos adicionados:
- `phone` — varchar(50)
- `extraData` — JSON (campos extras configuráveis)

---

## 3. BlockType Enum ✅

```
text_image     → Imagem + Texto
gallery        → Galeria de Imagens
newsletter     → Newsletter / Captura de E-mail
blurbs4        → Texto com 4 Blocos
stats          → Estatísticas
news_call      → Chamada para Notícia
map            → Mapa (embed)
sub_categories → Listar Subcategorias
page_list      → Listar Páginas
testimonials   → Depoimentos          ← NOVO
partner_logos  → Logos de Parceiros   ← NOVO
```

---

## 4. Área Administrativa

### 4.1 Dashboard ✅
- Exibe: Contatos não lidos, Páginas, Categorias, Assinantes newsletter

### 4.2 Configurações (`/admin/settings`) ✅
Seções:
- **Página Home** — select de páginas existentes
- **SEO** — seoTitle, seoDescription, seoKeywords, ogImage
- **Favicon** — upload com preview
- **Contato** — email, telefone, endereço, mapsEmbedUrl
- **Redes Sociais** — YouTube, Instagram, Facebook, LinkedIn, WhatsApp

### 4.3 Categorias ✅
- CRUD completo com campo `icon` (Icon Picker Font Awesome)
- Subcategorias (nível único)
- `showInHeader`, `showInFooter`
- Seções por categoria

### 4.4 Páginas ⚠️
- CRUD básico ✅
- Campos SEO ✅
- Cover image — **formulário ainda sem os novos campos** 🔜
- Categoria/subcategoria — **formulário ainda sem select** 🔜
- Drag-and-drop de ordem (`/admin/page/reorder`) — **rota criada** ✅ / **UI pendente** 🔜
- Duplicar página 🔜

### 4.5 Seções ⚠️
- CRUD básico ✅
- Formulário de edição com seletor de background ✅
- Seletor suporta: Nenhum, Cor, Gradiente, Imagem, Vídeo ✅
- Formulário de criação (new.html.twig) ainda sem painel de background 🔜
- Duplicar seção 🔜

### 4.6 Blocos ⚠️
- Seletor de tipo (grade de 11 cards) ✅
- Formulários por tipo: `text_image`, `gallery`, `newsletter`, `blurbs4`, `stats`, `map`, `news_call`, `sub_categories`, `page_list` ✅
- Formulário `testimonials` 🔜
- Formulário `partner_logos` 🔜
- Duplicar bloco 🔜

### 4.7 Banners ✅
- CRUD com: imagem de fundo, título, subtítulo, CTA (texto + link), ativo, posição
- Reordenação drag-and-drop via SortableJS

### 4.8 Newsletter ✅
- Listagem de assinantes
- Exportação CSV (`/admin/newsletter/export.csv`) ✅

### 4.9 Contatos ✅
- Listagem, marcar como lido, excluir

### 4.10 Editores de Conteúdo ✅
- CRUD de usuários com papel de editor por tenant

### 4.11 Fale Conosco — Campos configuráveis 🔜
Pendente:
- CRUD em `/admin/contact-fields`
- Listar campos do tenant, criar/editar/excluir
- Formulário com tipo, rótulo, opções (para select), obrigatório, posição

---

## 5. Frontend / Área Pública

### 5.1 Tema ativo
O tema ativo é `moderno` em `templates/themes/moderno/`.

### 5.2 Modo claro / escuro / automático 🔜

**Implementação:**

**CSS (`base.html.twig`):**
```css
:root {
    --bg:         #ffffff;
    --bg-alt:     #f8fafc;
    --bg-card:    #ffffff;
    --text:       #0f172a;
    --text-muted: #64748b;
    --border:     rgba(0,0,0,.08);
}
[data-theme="dark"] {
    --bg:         #060612;
    --bg-alt:     #0d0f1e;
    --bg-card:    #131625;
    --text:       #e2e8f0;
    --text-muted: rgba(255,255,255,.45);
    --border:     rgba(255,255,255,.07);
}
```

O `body` usa `background: var(--bg); color: var(--text)` em vez de valores fixos.

**JS de 3 modos (inline no `<head>`):**
```js
const MODES = ['auto', 'light', 'dark'];
function applyTheme(mode) {
    const dark = mode === 'auto'
        ? window.matchMedia('(prefers-color-scheme: dark)').matches
        : mode === 'dark';
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    localStorage.setItem('wab-theme', mode);
}
// Rodar antes do paint:
applyTheme(localStorage.getItem('wab-theme') || 'auto');
```

**Botão no header** — cicla entre ☀️ Claro / 🌙 Escuro / ⚙️ Auto

**Observações:**
- Cores de marca (`--color-primary`, `--color-secondary`) geradas por `tenant_css_vars()` permanecem intactas
- Header, footer e todos os partials precisam usar as variáveis CSS estruturais

### 5.3 Renderização de seções com background 🔜

Em `page.html.twig` (e `home.html.twig`), cada `<section>` deve:

```twig
{%- set bgStyle = '' -%}
{% if s.bgType == 'color' %}
    {%- set bgStyle = 'background:' ~ s.bgColor -%}
{% elseif s.bgType == 'gradient' %}
    {%- set bgStyle = 'background:linear-gradient(' ~ s.bgGradient ~ ')' -%}
{% elseif s.bgType in ['image', 'video'] %}
    {%- set bgStyle = 'position:relative;overflow:hidden' -%}
{% endif %}
<section style="{{ bgStyle }}">
    {% if s.bgType == 'image' and s.bgImage %}
        <div style="position:absolute;inset:0;background-image:url({{ asset('uploads/section/bg/' ~ s.bgImage) }});background-size:cover;background-position:{{ s.bgImagePosition }};opacity:{{ s.bgImageOpacity / 100 }};z-index:0"></div>
    {% endif %}
    {% if s.bgType == 'video' and s.bgVideo %}
        <video autoplay muted loop playsinline style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0">
            <source src="{{ asset('uploads/section/video/' ~ s.bgVideo) }}" type="video/mp4">
        </video>
        {% if s.bgColor %}
            <div style="position:absolute;inset:0;background:{{ s.bgColor }};opacity:.5;z-index:1"></div>
        {% endif %}
    {% endif %}
    <div style="position:relative;z-index:2">
        {# conteúdo da seção #}
    </div>
</section>
```

### 5.4 Dispatcher de blocos 🔜

Criar `templates/themes/moderno/_block.html.twig`:

```twig
{% set type = block.type %}
{% if type == 'text_image' %}
    {% include 'themes/moderno/blocks/_text_image.html.twig' %}
{% elseif type == 'gallery' %}
    {% include 'themes/moderno/blocks/_gallery.html.twig' %}
{% elseif type == 'newsletter' %}
    {% include 'themes/moderno/blocks/_newsletter.html.twig' %}
{# ... demais tipos ... #}
{% endif %}
```

### 5.5 Templates públicos por tipo de bloco 🔜

| Arquivo | Tipo | Notas |
|---|---|---|
| `blocks/_text_image.html.twig` | text_image | imagem + texto, posição configurável |
| `blocks/_gallery.html.twig` | gallery | carrossel com Swiper.js ou CSS puro |
| `blocks/_newsletter.html.twig` | newsletter | input de e-mail + submit AJAX + 2 blorbs |
| `blocks/_blurbs4.html.twig` | blurbs4 | 4 cards com ícone, título, texto |
| `blocks/_stats.html.twig` | stats | 4 números em destaque |
| `blocks/_news_call.html.twig` | news_call | N páginas recentes em cards |
| `blocks/_map.html.twig` | map | `<iframe>` do Google Maps |
| `blocks/_sub_categories.html.twig` | sub_categories | Grid de cards de subcategorias |
| `blocks/_page_list.html.twig` | page_list | Lista/grid de páginas com cover |
| `blocks/_testimonials.html.twig` | testimonials | Cards com avatar, estrelas, texto |
| `blocks/_partner_logos.html.twig` | partner_logos | Linha de logos com hover colorido |

### 5.6 Página pública (`/p/{slug}`) 🔜
- Controller: `PublicPageController::show(string $slug)`
- Busca `Page` pelo slug + tenant
- Renderiza `themes/moderno/page.html.twig`
- SEO: `<title>`, `<meta description>`, `<link rel="canonical">`

### 5.7 Home configurável 🔜
- Se `Tenant.homePage` preenchido: renderiza a página selecionada como home
- Senão: fallback para a home existente

### 5.8 Formulário de Fale Conosco público 🔜
- Busca `ContactFormField` do tenant para montar o form dinamicamente
- Campos fixos: nome, e-mail, telefone, mensagem
- Campos extras: iterados de `ContactFormField` por ordem
- Submit: grava `ContactMessage.extraData` + envia e-mail de notificação

---

## 6. Funcionalidades Extras

### 6.1 Duplicar páginas / seções / blocos 🔜
Criar `App\Service\DuplicatorService`:
```php
public function duplicatePage(Page $page): Page { ... }
public function duplicateSection(PageSection $section): PageSection { ... }
public function duplicateBlock(PageBlock $block): PageBlock { ... }
```

Rotas POST:
- `/admin/page/{id}/duplicate`
- `/admin/section/{id}/duplicate`
- `/admin/block/{id}/duplicate`

Botões "Duplicar" nas listagens.

### 6.2 Reordenação drag-and-drop de páginas 🔜
- Rota `POST /admin/page/reorder` ✅ (criada)
- `admin/page/index.html.twig` precisa do SortableJS e dos `data-id` nos itens

### 6.3 Exportação CSV de contatos 🔜
- Rota `GET /admin/contact/export.csv`
- Inclui: id, nome, e-mail, telefone, mensagem, extraData flatten, data

---

## 7. Migrações Aplicadas

| Versão | Descrição |
|---|---|
| `Version20260519210432` | Cria `page_block_image` · dropa `article`, `article_approval`, `article_image`, `message` · adiciona campos em `category`, `page`, `page_block`, `tenant` |
| `Version20260519210533` | Cria `contact_form_field` · adiciona `phone` e `extra_data` em `contact_message` |
| `Version20260519211921` | Cria `page_block_testimonial`, `page_block_partner_logo` · adiciona campos de background em `page_section` · dropa tabelas `study`, `study_material`, `video_support`, `video_material` |

---

## 8. Checklist de Execução

### Banco / Entidades
- [x] Migrations aplicadas e `schema:validate` OK

### Admin UI
- [x] wab_base.html.twig com Icon Picker Modal
- [x] Dashboard com métricas WAB
- [x] Configurações: homePage, favicon, SEO, redes sociais
- [x] Seletor de tipo de bloco (grade de cards)
- [x] Formulários de bloco: 9 tipos implementados
- [x] Formulário de edição de seção com painel de background
- [ ] Formulário de **criação** de seção com painel de background
- [ ] Formulário de **criação/edição** de página com coverImage + category select
- [ ] Formulário de bloco: **testimonials**
- [ ] Formulário de bloco: **partner_logos**
- [ ] CRUD `/admin/contact-fields`
- [ ] Drag-and-drop na listagem de páginas (SortableJS)
- [ ] Botões "Duplicar" nas listagens

### Frontend
- [ ] CSS custom properties (light/dark) no tema moderno
- [ ] Botão de tema (Claro / Escuro / Auto) no header
- [ ] JS de persistência de tema com `localStorage`
- [ ] Renderização de background nas seções
- [ ] Dispatcher `_block.html.twig`
- [ ] 11 partials de bloco público
- [ ] `PublicPageController::show()` + rota `/p/{slug}`
- [ ] Home configurável via `Tenant.homePage`
- [ ] Fale Conosco público dinâmico

### Serviços
- [ ] `DuplicatorService` (página, seção, bloco)
- [ ] Exportação CSV de contatos (`/admin/contact/export.csv`)
