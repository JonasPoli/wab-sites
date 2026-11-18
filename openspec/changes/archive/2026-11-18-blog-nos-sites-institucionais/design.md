# Design

## Context

- `WabPublicController` serve notícias só com `isNewsTheme()` (tema `noticias`): `/noticias` (fora do portal, renderiza o `articles.html.twig` antigo do tema com lista vazia), `/noticias/{slug}`, `/autor/{slug}`, `/busca`, e `/categoria/{slug}` com notícias. Os `articles.html.twig` e `article.html.twig` de cetec, moderno e wab são sobras de antes do cadastro atual: usam `article.image`, `article.content` e `article.author` como texto, que a entidade `Article` não tem.
- O cadastro (`AdminArticleController`, `/admin/noticias`) não confere o tema; só o item de menu em `templates/admin/wab_base.html.twig` (linha 175) confere.
- `Article` é `TenantAware`; `ArticleRepository` já tem `findVisiblePage(?Category, $page, $perPage)`, `countVisible(?Category)`, `findOneVisibleBySlug()`, `findNextOlder()`, `incrementViews()`, `findAllVisible(withSamples)`, `findCategoryIdsWithVisibleArticles()`.
- `PageSeo::daNoticia()` monta canônico por `pub_article_show` e `NewsArticle`; `SitemapController` inclui notícias só quando `theme === 'noticias'`.
- O autor da notícia é opcional (`Article.author` nulo); o blog da Pichoneri tem um autor só ("wabmin"), que não será mostrado.

## Goals / Non-Goals

**Goals:**
- Blog funcionando em qualquer tema institucional com um interruptor.
- Zero alteração nas rotas e nos templates que o portal e os sites atuais usam.

**Non-Goals:**
- Busca, página de autor, comentários, RSS e "mais lidas" no blog.
- Reescrever os `articles.html.twig`/`article.html.twig` antigos de cetec, moderno e wab (continuam servindo `/noticias` com lista vazia, como a spec atual descreve).
- Área do jornalista em sites com blog (quem publica é a equipe do `/admin`).

## Decisions

### D1. Interruptor no tenant, e não "tema com blog"

`Tenant.blogEnabled` (bool, `default 0`). Regra única, num método `Tenant::hasBlog(): bool` → `blogEnabled && theme !== 'noticias'`. Editado só no `/superadmin` (ao lado do interruptor da newsletter, linha ~387 do `edit.html.twig`), gravado no `SuperAdminController` (linha ~708), incluído em `toArray`/`fromArray` do `Tenant` (linhas ~438–491), no `TenantExporter` (~160) e no `TenantImporter` (~223, `?? false`).

*Alternativa descartada:* ligar o blog pelo nome do tema. Prenderia o blog ao tema novo e não atenderia o cetec.

### D2. Controller novo, rotas novas

`src/Controller/pub/BlogController.php`, com `TenantContext`:

| Rota | Nome | Comportamento |
|---|---|---|
| `/blog` | `pub_blog` | lista, 9 por página, `?pagina=N` (404 fora do intervalo, mesma regra do `currentPage()` do `WabPublicController`: copiar o método) |
| `/blog/categoria/{slug}` | `pub_blog_category` | lista da categoria e das subcategorias (o `findVisiblePage($cat)` já faz); 404 se a categoria não existe |
| `/blog/{slug}` | `pub_blog_post` | post visível; conta visualização com a mesma regra de robôs; `next` = `findNextOlder()` |

Todas respondem 404 quando `!$tenant->hasBlog()`. Declarar `pub_blog_category` antes de `pub_blog_post`. Nenhuma rota existente começa com `/blog`.

Template: `themes/<tema>/blog/<nome>.html.twig` se o loader tiver; senão `themes/_comum/blog/<nome>.html.twig`. Nomes: `list` (lista e categoria, com a variável `category` nula na lista geral) e `post`.

Isolamento: `Article` e `Category` passam pelo filtro de tenant do domínio; o controller não recebe id, só slug.

*Alternativa descartada:* reaproveitar `/noticias` trocando a condição `isNewsTheme()`. Mudaria o comportamento descrito na spec "Rotas antigas de notícias, vídeos e estudos" e passaria pelos templates antigos, que quebram com um `Article` de verdade.

### D3. Templates compartilhados em `themes/_comum/blog/`

`list.html.twig` e `post.html.twig` começam com `{% extends 'themes/' ~ currentTenant.theme ~ '/base.html.twig' %}` e preenchem o bloco `body`. Marcação com Tailwind e as classes `cetec-*` (funcionam nos três temas porque todos carregam `app.css`). Parciais: `_card.html.twig` (capa com `capa_pagina`, categoria, data `d/m/Y`, título, resumo, "Ler mais") e `_pagination.html.twig` (anterior / próxima com `?pagina=N`).

- Lista: `h1` "Blog" (ou o nome da categoria), fichas das categorias com post visível (`findCategoryIdsWithVisibleArticles()`), grade 1/2/3 colunas, paginação.
- Post: categoria, `h1`, data, capa (`capa_noticia` / `capa_noticia_mobile`, `fetchpriority="high"`), `article.body|raw` dentro de um contêiner com estilos de texto (parágrafo, `h2`, `h3`, listas, citação, imagem), vídeo do YouTube quando houver (mesmo trecho do tema noticias), "Próximo post" e link "Voltar para o blog".
- Variáveis de SEO no topo, como os outros templates: nada a declarar; o `_seo_head` lê `article` do contexto.

Temas em que existe: cetec, moderno e wab pelo compartilhado. O tema `atelie` (mudança 3) terá os seus em `themes/atelie/blog/`.

### D4. Endereço do post num lugar só

Em `src/Twig/ArticleExtension.php`:
- `article_url(Article $a): string` → `pub_blog_post` quando o site tem blog, senão `pub_article_show`.
- `articles_url(?Category $c = null): string` → `pub_blog` / `pub_blog_category` no blog; `pub_articles` / `pub_category` no portal.
- `latest_articles(int $limit = 3, ?Category $c = null): array` → `findVisiblePage($c, 1, $limit)` quando o site tem blog ou é portal; senão `[]`.

Os templates do blog e o bloco usam só essas funções. Os templates do tema `noticias` não são alterados.

### D5. Bloco `blog_posts`

- `BlockType::BlogPosts = 'blog_posts'`, rótulo "Últimas do blog", ícone `fa-solid fa-rss`.
- Formulário `_form_blog_posts.html.twig`: pré-título, título, texto, `itemCount` (padrão 3, de 1 a 12), `relatedCategoryId` (opcional), `config[ctaText]` (padrão "Ver todos os posts"). Usa colunas que já existem; o `populateBlock` não muda.
- Parcial `themes/cetec/blocks/_blog_posts.html.twig`: cabeçalho de bloco padrão + grade com `themes/_comum/blog/_card.html.twig` + link para `articles_url(block.relatedCategory)`. Sem posts, não desenha nada.
- `cetec/_block.html.twig` ganha o `elseif`; em moderno e wab, `'blog_posts'` entra na lista literal criada na mudança 1. Entrada nova na lista de `new.html.twig`. O teste `BlockTypeCoberturaTest` passa a cobrir o tipo sozinho.
- Rótulo no menu landing: `blog_posts` → "Blog".

### D6. SEO e sitemap

- `PageSeo::daNoticia()`: canônico e `url` por rota `pub_blog_post` quando `$tenant?->hasBlog()`; `@type` `BlogPosting` no lugar de `NewsArticle`; sem `author.url` (não há página de autor no blog); trilha Início → Blog → categoria → post, com os endereços do blog.
- Lista do blog: o `BlogController` passa `articles`, `page`, `pages` e `category` como o portal passa; em `PageSeo`, onde `daListagemOuHome()` e `lista()` montam endereços com `pub_articles`/`pub_article_show`, usar as rotas do blog quando `hasBlog()`. Título "Blog" (ou o nome da categoria); `CollectionPage` + `ItemList`.
- `SitemapController`: `$temNoticias = $ehPortal || $tenant?->hasBlog()`; para o blog, uma URL `/blog/{slug}` por post visível que não é de exemplo (0.7, weekly), `/blog` (0.9, daily) quando há posts e `/blog/categoria/{slug}` (0.6, weekly) por categoria com post visível. Autores não entram. O ramo do portal fica como está.

### D7. Painel

- `templates/admin/wab_base.html.twig`: o item do cadastro aparece com `theme == 'noticias' or currentTenant.hasBlog`; rótulo "Blog" quando `hasBlog`, "Notícias" no portal. Os itens de jornalistas continuam só no portal.
- `templates/admin/article/_form.html.twig` (linha 34): a dica do endereço mostra `/blog/slug` quando o site tem blog.
- O campo de autor do formulário continua opcional; em site com blog a lista de autores vem vazia e nada é mostrado no post.

## Risks / Trade-offs

- [Um site com blog ligado e categoria usada ao mesmo tempo por páginas e posts] → os dois endereços coexistem: `/categoria/{slug}` lista páginas (como hoje), `/blog/categoria/{slug}` lista posts.
- [Trocar o tema de um site com blog para `noticias`] → `hasBlog()` passa a falso e os posts continuam visíveis em `/noticias/{slug}`; os endereços `/blog/...` dão 404. Documentado no README.
- [`extends` dinâmico num tema sem bloco `body`] → os quatro `base.html.twig` têm `{% block body %}`; o teste funcional cobre cetec, moderno e wab.

## Migration Plan

Migration aditiva (`tenant.blog_enabled`, `default 0`). Deploy: backup, `git pull`, `./build.sh`, `doctrine:migrations:migrate`, HTTP 200 nos domínios. Nenhum site tem o blog ligado depois do deploy; ligar é uma ação por site no `/superadmin`. Rollback: commit anterior + `./build.sh`; a coluna pode ficar.
