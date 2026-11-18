# Proposal

## Why

O site atual da Marcenaria Pichoneri tem um blog de 44 posts, e o novo site precisa mantê-lo. O wab-sites já tem um cadastro completo de notícias (painel, situação, data, capa, editorias, texto sanitizado, SEO), mas ele só é servido quando o tema do site é `noticias`. Esta mudança libera esse mesmo cadastro como **blog** em sites institucionais, por um interruptor do site, sem mexer no portal de notícias.

Mudança 2 de 4. Depende de `blocos-novos-do-construtor` arquivada (o delta de "Blocos por tipo" parte do texto com 22 tipos).

## What Changes

- Interruptor "Blog" por site (`Tenant.blogEnabled`, desligado por padrão), no cadastro do site no `/superadmin`. No tema `noticias` ele não tem efeito.
- Com o blog ligado, três endereços novos: `/blog` (lista paginada), `/blog/categoria/{slug}` (lista de uma categoria) e `/blog/{slug}` (post). Com o blog desligado, os três respondem 404.
- Templates de blog compartilhados, que herdam o `base.html.twig` do tema do site, para cetec, moderno e wab; um tema pode ter os seus em `themes/<tema>/blog/`.
- O menu do `/admin` mostra "Blog" (o cadastro de notícias de sempre) quando o blog está ligado.
- Bloco novo `blog_posts` (Últimas do blog): os posts mais recentes, de todas as categorias ou de uma.
- SEO: canônico em `/blog/{slug}`, `BlogPosting` no post, `CollectionPage` na lista; sitemap com os endereços do blog.
- `/noticias`, `/noticias/{slug}`, `/autor/{slug}`, `/busca` e tudo do tema `noticias` ficam exatamente como estão.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `noticias`: requisitos novos do blog em sites institucionais (endereços, lista, post, painel, SEO, sitemap).
- `construtor-de-paginas`: "Blocos por tipo" ganha `blog_posts`; requisito novo do bloco.
- `gestao-de-sites`: interruptor do blog no cadastro do site, na exportação e na importação.

## Impact

- Código novo: `src/Controller/pub/BlogController.php`, `templates/themes/_comum/blog/{list,post,_card,_pagination}.html.twig`, `templates/admin/block/_form_blog_posts.html.twig`, `templates/themes/cetec/blocks/_blog_posts.html.twig`.
- Código alterado: `src/Entity/Tenant.php` (+ migration), `src/Controller/superadmin/SuperAdminController.php` e `templates/superadmin/tenant/edit.html.twig`, `src/Service/TenantExporter.php` / `TenantImporter.php`, `src/Twig/ArticleExtension.php`, `src/Service/Seo/PageSeo.php`, `src/Controller/pub/SitemapController.php`, `templates/admin/wab_base.html.twig`, `templates/admin/article/_form.html.twig`, `src/Entity/Enum/BlockType.php`, os `_block.html.twig` de cetec, moderno e wab, `templates/admin/block/new.html.twig`.
- `WabPublicController` não é alterado.
- Banco: uma coluna booleana em `tenant`, padrão falso. Nenhum site existente muda de comportamento.
