# Tasks

> Pré-requisito: `blocos-novos-do-construtor` implementada e arquivada. Leia o `design.md` desta mudança. Não altere `WabPublicController` nem nada em `templates/themes/noticias/`.

## 1. Preparação

- [ ] 1.1 Com as fixtures carregadas (incluindo o grupo `cetec`) e o servidor local no ar, rodar `sh/regressao.sh capturar antes`; acrescentar ao script o host `cetec.localhost:8000`. Verificar com `capturar depois` + `comparar` sem diferença

## 2. Interruptor do site

- [ ] 2.1 Em `src/Entity/Tenant.php`: propriedade `blogEnabled` (bool, `options: ['default' => false]`), `isBlogEnabled()`, `setBlogEnabled()`, `hasBlog()` (`blogEnabled && theme !== 'noticias'`) e a chave `blogEnabled` nos dois métodos de serialização (junto de `newsletterEnabled`, linhas ~442 e ~491, com `?? false`); gerar a migration com `doctrine:migrations:diff`, conferir que só adiciona `blog_enabled TINYINT(1) DEFAULT 0 NOT NULL` em `tenant`, aplicar e validar com `doctrine:schema:validate`
- [ ] 2.2 Em `templates/superadmin/tenant/edit.html.twig`, copiar o interruptor da newsletter (linha ~387) para um "Blog" com `name="blogEnabled"` e a dica "Serve as notícias cadastradas em /blog. Não se aplica ao tema Notícias."; gravar em `SuperAdminController` (linha ~708) com `=== '1'`; verificar ligando e desligando em `cetec.localhost` e conferindo o valor no banco pelo próprio formulário reaberto
- [ ] 2.3 `TenantExporter` (linha ~160) e `TenantImporter` (linha ~223, `?? false`): levar `blogEnabled`; verificar exportando `cetec.localhost` com o blog ligado e importando em `cetec3.localhost`

## 3. Funções de endereço

- [ ] 3.1 Em `src/Twig/ArticleExtension.php`, criar `article_url`, `articles_url` e `latest_articles` conforme D4 (injetar `UrlGeneratorInterface` e `TenantContext` se ainda não houver); criar `tests/Twig/ArticleUrlTest.php` cobrindo site com blog, portal e site sem nenhum dos dois; verificar com `vendor/bin/phpunit --filter ArticleUrl`

## 4. Páginas do blog

- [ ] 4.1 Criar `src/Controller/pub/BlogController.php` com as três rotas de D2 (404 sem `hasBlog()`, `PER_PAGE = 9`, cópia do `currentPage()`, contagem de visualização com a mesma expressão de robôs do `WabPublicController`) e a escolha do template (tema, senão `_comum`); verificar com `bin/console debug:router | grep blog` mostrando as três, com `pub_blog_category` antes de `pub_blog_post`
- [ ] 4.2 Criar `templates/themes/_comum/blog/_card.html.twig`, `_pagination.html.twig`, `list.html.twig` e `post.html.twig` conforme D3; verificar em `cetec.localhost:8000/blog` (depois da 6.1) lista, categoria, página 2 e um post, todos 200
- [ ] 4.3 Verificar os mesmos quatro endereços com o tema trocado para `moderno` e para `wab` (trocar o tema de `cetec.localhost` no `/superadmin` e voltar para `cetec` no fim)

## 5. Bloco "Últimas do blog"

- [ ] 5.1 `BlockType::BlogPosts` (rótulo "Últimas do blog", ícone `fa-solid fa-rss`), entrada na lista de `templates/admin/block/new.html.twig` (desc "Posts mais recentes do blog") e `templates/admin/block/_form_blog_posts.html.twig` conforme D5 (copiar a estrutura de `_form_news_call.html.twig`)
- [ ] 5.2 Criar `templates/themes/cetec/blocks/_blog_posts.html.twig`, o `elseif` em `cetec/_block.html.twig`, `'blog_posts'` na lista literal de `moderno/_block.html.twig` e `wab/_block.html.twig`, e o rótulo "Blog" nas cadeias do menu landing do cetec; verificar com `vendor/bin/phpunit --filter BlockTypeCobertura` e com o bloco na página `todos-os-blocos`

## 6. Fixtures e painel

- [ ] 6.1 Em `CetecFixtures`: `setBlogEnabled(true)`, 2 categorias ("Tendências", "Dicas") e 12 notícias publicadas com capa do `PlaceholderImageFactory` (mais 1 rascunho e 1 com data futura), e o bloco `blog_posts` na página `todos-os-blocos`; recarregar só o grupo (`--append --group=cetec` num banco recém-carregado) e verificar `/blog` com 9 posts e `/blog?pagina=2` com 3
- [ ] 6.2 Em `templates/admin/wab_base.html.twig` (linha ~175), mostrar o item do cadastro com `theme == 'noticias' or currentTenant.hasBlog`, com o rótulo "Blog" quando `hasBlog`, mantendo os itens de jornalistas dentro da condição do portal; em `templates/admin/article/_form.html.twig` (linha ~34), dica do endereço com `/blog/` quando `hasBlog`; verificar no `/admin` de `cetec.localhost` (mostra "Blog"), de `127.0.0.1` (não mostra) e de `noticias.localhost` (mostra "Notícias" e jornalistas, como antes)

## 7. SEO e sitemap

- [ ] 7.1 Em `src/Service/Seo/PageSeo.php`, aplicar D6 em `daNoticia()`, `daListagemOuHome()`, `lista()` e nas trilhas; verificar no HTML de um post de `cetec.localhost` o canônico `/blog/<slug>` e o JSON-LD `BlogPosting`, e no de uma notícia de `noticias.localhost` que nada mudou (`NewsArticle`, `/noticias/<slug>`)
- [ ] 7.2 Em `src/Controller/pub/SitemapController.php`, aplicar D6; estender `tests/Controller/SitemapERobotsTest.php` (ou criar `tests/Controller/BlogTest.php` no mesmo molde) com: blog desligado → `/blog` 404 e sitemap sem `/blog`; blog ligado → `/blog` 200, post 200, rascunho 404, sitemap com `/blog`, `/blog/<slug>` e `/blog/categoria/<slug>`; tema `noticias` com o interruptor ligado → `/blog` 404; verificar com `vendor/bin/phpunit`
- [ ] 7.3 Atualizar o `README.md` (seção de temas e SEO) com o blog: interruptor, endereços, pasta `themes/_comum/blog/` e como um tema sobrescreve

## 8. Integração

- [ ] 8.1 Desligar o blog de `cetec.localhost`, rodar `sh/regressao.sh capturar depois` e `comparar`: sem diferença em `127.0.0.1`, `localhost` e `noticias.localhost`; em `cetec.localhost`, diferença só no bloco `blog_posts` que deixou de aparecer
- [ ] 8.2 `vendor/bin/phpunit` inteiro e `openspec validate blog-nos-sites-institucionais --strict` sem falha

## 9. Deploy (só com aprovação explícita do Jonas)

- [ ] 9.1 Backup do banco de produção no servidor wabsites e anotação do HTTP dos domínios (leitura + arquivo de backup)
- [ ] 9.2 Deploy (escrita): `git push origin main`; no servidor `git pull`, `./build.sh`, `bin/console doctrine:migrations:migrate --no-interaction`
- [ ] 9.3 Conferência: HTTP 200 nos 8 domínios de cliente e em ajope.wab.com.br; `/blog` respondendo 404 em um deles (blog desligado); `/noticias` do ajope igual a antes

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido, antes de começar `tema-atelie`.
