# construtor-de-paginas Specification

## Purpose
Monta o site público de cada tenant com páginas, categorias, seções e blocos cadastrados no `/admin`, renderizados pelo tema do tenant (`templates/themes/<tema>/`). Implementado em `App\Controller\pub\WabPublicController`, `App\Controller\pub\SitemapController`, `App\Controller\admin\AdminContentController`, `App\Service\DuplicatorService`, `App\Entity\Enum\BlockType` e nos templates `page.html.twig`, `category.html.twig` e `_block.html.twig` de cada tema. O `MainController` define rotas com os mesmos nomes, mas as do `WabPublicController` são as que valem.

## Requirements

### Requirement: Home do site
O sistema SHALL, em `/`, renderizar a página definida em `Tenant.homePage` com o template `page.html.twig` do tema; sem página definida, SHALL renderizar `home.html.twig` com os banners ativos ordenados por `position` e `id`.

#### Scenario: Site com página home
- **WHEN** o tenant tem `homePage` definida
- **THEN** `/` mostra as seções dessa página

#### Scenario: Site sem página home
- **WHEN** o tenant não tem `homePage`
- **THEN** `/` mostra o `home.html.twig` com o primeiro banner ativo como destaque e a lista de banners ativos

### Requirement: Página pública por slug
O sistema SHALL exibir em `/p/{slug}` a primeira página do tenant com aquele slug e responder 404 "Página não encontrada." quando não existe. Não há rascunho nem status de publicação: `publishedAt` só ordena listagens, e o slug não é único no banco.

#### Scenario: Página com data de publicação futura
- **WHEN** uma página tem `publishedAt` no futuro
- **THEN** `/p/{slug}` exibe a página normalmente

### Requirement: Seções da página
O sistema SHALL renderizar só as seções ativas da página ou categoria, na ordem de `position`, cada uma num `<section id="section-{id}">`, e SHALL mostrar o título em duas cores (`titlePart1` e `titlePart2`) quando `Tenant.showSectionTitles` está ligado, a seção tem "Exibir o título da seção na página" marcado e uma das partes está preenchida.

#### Scenario: Seção desativada
- **WHEN** uma seção tem `active` falso
- **THEN** ela não aparece na página nem no menu do Modo Landing Page

#### Scenario: Título da seção oculto
- **WHEN** o admin desmarca "Exibir o título da seção na página" na seção "Quem | Somos" e salva
- **THEN** a página mostra os blocos da seção sem o título em duas cores, e o título continua gravado e no menu do Modo Landing Page

#### Scenario: Seção nova
- **WHEN** o admin cria uma seção sem mexer na caixa
- **THEN** o título da seção aparece na página

### Requirement: Fundo da seção
O sistema SHALL aplicar o fundo da seção pelo `bgType`: `color` usa `bgColor`; `gradient` usa `linear-gradient(bgGradient)`; `image` sobrepõe a imagem com `bgImagePosition` e opacidade `bgImageOpacity`/100; `video` toca o MP4 em loop, mudo, com uma camada de `bgColor` a 50% quando há cor.

#### Scenario: Fundo de vídeo com cor
- **WHEN** a seção tem `bgType` video, um vídeo e `bgColor` preenchida
- **THEN** o vídeo toca em autoplay, mudo e em loop, coberto pela cor com opacidade 0,5

### Requirement: Blocos por tipo
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 15 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact); tipo desconhecido não renderiza nada.

#### Scenario: Bloco de newsletter com a newsletter desligada
- **WHEN** a seção tem um bloco `newsletter` e o tenant tem `newsletterEnabled` falso
- **THEN** o bloco não é renderizado

### Requirement: Chamada para notícias
O bloco `news_call` SHALL listar as páginas da categoria escolhida e das subcategorias dela, da mais recente para a mais antiga por `publishedAt` (e `id` no empate), limitadas a `itemCount` (3 quando vazio), com link para a categoria; sem categoria escolhida, não lista nada.

#### Scenario: Categoria com subcategorias
- **WHEN** o bloco aponta para uma categoria raiz com 5 páginas próprias e 4 em subcategorias, e `itemCount` é 6
- **THEN** aparecem as 6 páginas mais recentes entre as 9

### Requirement: Página de categoria
O sistema SHALL exibir em `/categoria/{slug}` a categoria do tenant com ícone, descrição, subcategorias, páginas e seções ativas, e responder 404 quando o slug não existe no tenant.

#### Scenario: Categoria inexistente
- **WHEN** alguém abre `/categoria/nao-existe`
- **THEN** a resposta é 404

### Requirement: Subcategoria de um nível
O `/admin` SHALL recusar a criação de subcategoria dentro de uma subcategoria, com a mensagem "Sub-categorias não podem ter sub-categorias.", e SHALL oferecer como categoria de bloco só categorias raiz.

#### Scenario: Subcategoria dentro de subcategoria
- **WHEN** o admin tenta criar uma subcategoria a partir de outra subcategoria
- **THEN** nada é gravado e ele volta para a lista de categorias com o aviso

### Requirement: Perfil de membro da equipe
O sistema SHALL exibir em `/membro/{slug}` o membro de bloco `team` com `detailEnabled` ligado, cadastrado numa seção de página ou categoria do tenant do domínio, no template `team_member_<detailLayout>.html.twig` do tema (`classic` quando vazio), e responder 404 "Perfil não encontrado." quando não há membro com perfil ativo nesse tenant.

#### Scenario: Perfil desligado
- **WHEN** o membro existe mas `detailEnabled` é falso
- **THEN** `/membro/{slug}` responde 404

#### Scenario: Membro de outro site
- **WHEN** `pkwy.com.br` recebe `/membro/{slug}` com o slug de um membro com perfil ativo cadastrado num bloco do tenant 1
- **THEN** a resposta é 404 "Perfil não encontrado."

#### Scenario: Mesmo slug em dois sites
- **WHEN** os tenants 1 e 3 têm membros com perfil ativo e o mesmo slug, e `pkwy.com.br` recebe `/membro/{slug}`
- **THEN** aparece o membro do tenant 3, no tema do tenant 3

### Requirement: Duplicação de página, seção e bloco
O sistema SHALL duplicar uma página com o título acrescido de " (cópia)", o slug acrescido de "-" e um `uniqid`, posição 999 e cópia de todas as seções e blocos com galeria, depoimentos, logos e membros.

#### Scenario: Duplicar página
- **WHEN** o admin duplica a página "Sobre" de slug `sobre`
- **THEN** surge a página "Sobre (cópia)" com slug `sobre-<id único>` e as mesmas seções e blocos

### Requirement: Rotas antigas de notícias, vídeos e estudos
O sistema SHALL responder 404 em `/videos/{slug}` e `/estudos/{slug}`, e em `/noticias/{slug}` quando o tema do site não é `noticias`. Em `/videos`, `/estudos` e, fora do tema `noticias`, em `/noticias`, SHALL renderizar com lista vazia `videos.html.twig`, `studies.html.twig` e `articles.html.twig` do tema quando o tema tem o template, e responder 404 "Página não encontrada." quando não tem. No tema `noticias`, `/noticias` e `/noticias/{slug}` mostram as notícias do site (capability `noticias`).

#### Scenario: Vídeos num site com tema cetec
- **WHEN** um tenant com tema cetec ou moderno recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque só o tema wab tem `videos.html.twig` e `studies.html.twig`

#### Scenario: Vídeos no tema wab
- **WHEN** um tenant com tema wab recebe `/videos` ou `/estudos`
- **THEN** a página do tema é renderizada com a lista vazia

#### Scenario: Notícias no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias`
- **THEN** o `articles.html.twig` do cetec é renderizado com a lista vazia

#### Scenario: Notícia no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias/{slug}`, mesmo com uma notícia publicada com esse slug no banco
- **THEN** a resposta é 404

#### Scenario: Vídeos no tema noticias
- **WHEN** um tenant com tema `noticias` recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque o tema `noticias` não tem esses templates

### Requirement: Sitemap
O sistema SHALL responder em `/sitemap.xml` um XML com a home (prioridade 1.0, `lastmod` do dia) e uma URL `/p/{slug}` por página do tenant com slug (prioridade 0.8, `lastmod` do `updatedAt`), com o cabeçalho `X-Robots-Tag: noindex`; categorias não entram. No tema `noticias`, o XML SHALL trazer também uma URL `/noticias/{slug}` por notícia visível (prioridade 0.7, `lastmod` da última edição).

#### Scenario: Sitemap do site
- **WHEN** um robô lê `https://pkwy.com.br/sitemap.xml`
- **THEN** recebe a home e as páginas do tenant 3, com endereços no domínio pkwy.com.br

#### Scenario: Portal com rascunho
- **WHEN** um site com tema `noticias` tem 3 notícias publicadas e 1 em rascunho
- **THEN** o sitemap traz as 3 publicadas em `/noticias/{slug}` e não traz o rascunho

#### Scenario: Site institucional
- **WHEN** um site com tema cetec recebe `/sitemap.xml`
- **THEN** o XML traz só a home e as páginas, sem URLs de notícia

### Requirement: Pré-título dos blocos
Nos blocos dos temas cetec e noticias, o pré-título com até 40 caracteres SHALL aparecer como rótulo curto (pílula com texto pequeno em caixa alta), e o pré-título com mais de 40 caracteres SHALL aparecer como destaque legível: sem caixa alta, com texto de pelo menos 14 px, entrelinha folgada, espaço interno de pelo menos 12 px na vertical e 20 px na horizontal e largura máxima de leitura.

#### Scenario: Pré-título longo na página Quem somos
- **WHEN** o bloco tem o pré-título "O AJoPE é o portal de notícias da globo e líder de audiência no jornalismo digital no Brasil. Conheça mais sobre a história do g1."
- **THEN** o texto aparece sem caixa alta, com pelo menos 14 px, e com pelo menos 12 px de espaço acima e abaixo dentro do destaque

#### Scenario: Rótulo curto
- **WHEN** o bloco tem o pré-título "QUEM SOMOS"
- **THEN** ele aparece como hoje, na pílula em caixa alta
