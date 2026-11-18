# Spec Delta

## MODIFIED Requirements

### Requirement: Blocos por tipo
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 23 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact, hero_split, highlights, portfolio, statement, steps, cta, video, blog_posts); tipo desconhecido não renderiza nada.

#### Scenario: Bloco de newsletter com a newsletter desligada
- **WHEN** a seção tem um bloco `newsletter` e o tenant tem `newsletterEnabled` falso
- **THEN** o bloco não é renderizado

#### Scenario: Tipo que não existe
- **WHEN** a seção tem um bloco gravado com um tipo que não está em `BlockType`
- **THEN** a página responde 200 e nada é desenhado no lugar desse bloco

## ADDED Requirements

### Requirement: Últimas do blog
O bloco `blog_posts` SHALL mostrar os posts visíveis mais recentes do site (3 quando a quantidade não é informada, no máximo 12), de todas as categorias ou só da categoria escolhida, com link para a lista do blog; num site sem blog e sem portal de notícias, ou sem post visível, o bloco não é desenhado. No portal de notícias os links apontam para `/noticias`.

#### Scenario: Blog desligado depois
- **WHEN** a home tem um bloco `blog_posts` e o blog do site é desligado
- **THEN** a home responde 200 sem o bloco

#### Scenario: Categoria escolhida
- **WHEN** o bloco aponta para a categoria "Tendências" com quantidade 3
- **THEN** aparecem os 3 posts mais recentes dessa categoria e o link vai para `/blog/categoria/tendencias`
