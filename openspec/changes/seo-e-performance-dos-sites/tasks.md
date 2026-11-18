# Tasks

## 1. Medição de referência

- [x] 1.1 Criar `sh/medir-pagespeed.sh <antes|depois>`, que roda o Lighthouse nos 9 domínios em celular e computador e grava um JSON por execução; verificar rodando com `antes` e conferindo que saem 18 arquivos com as 4 categorias
- [x] 1.2 Criar `sh/comparar-pagespeed.php`, que lê os JSON e imprime a tabela de notas e a lista de auditorias reprovadas por domínio; verificar que a saída bate com o diagnóstico de 17/11/2026 (SEO 92 nos 9, `meta-description` reprovada nas 18 execuções)
- [x] 1.3 Guardar o resultado do `antes` em `docs/pagespeed-17-11-2026.md` com a tabela, o peso de imagem por home e as auditorias reprovadas; verificar que o arquivo cita os 9 domínios

## 2. Serviço de SEO e `<head>` único

- [x] 2.1 Criar `src/Service/PageSeo.php` com `paraPagina()`, `paraCategoria()`, `paraNoticia()`, `paraAutor()`, `paraListagem()` e `paraHome()`, devolvendo um objeto com título, descrição, canônico, imagem (URL, largura, altura), robots e o grafo JSON-LD; verificar com `bin/console lint:container` sem erro
- [x] 2.2 Implementar a cascata da descrição (página → site → texto do conteúdo sem HTML → nome do site), o corte em 160 caracteres na fronteira de palavra e o mínimo de 50; cobrir em `tests/Service/PageSeoTest.php` os cenários da spec `seo-das-paginas` (home sem descrição, página com descrição própria, texto longo) e verificar com `vendor/bin/phpunit --filter PageSeo` passando
- [x] 2.3 Implementar o canônico absoluto sem parâmetros de consulta, com a página de `homePage` apontando para `/`; cobrir em teste os dois cenários da spec e verificar com o filtro do PHPUnit passando
- [x] 2.4 Implementar o JSON-LD por tipo (`Organization`+`WebSite`, `WebPage`, `BreadcrumbList`, `NewsArticle`, `CollectionPage`+`ItemList`, `ProfilePage`+`Person`), omitindo propriedade sem valor e sem `NewsArticle` em notícia de exemplo; cobrir em teste e verificar que o JSON gerado passa no validador de schema.org para uma home, uma notícia e uma editoria
- [x] 2.5 Criar `templates/pub/_seo_head.html.twig` imprimindo título, descrição, canônico, Open Graph com largura e altura, Twitter Card, robots e o `application/ld+json`; verificar no HTML local que a home traz todas as tags e nenhuma vazia
- [x] 2.6 Incluir o parcial nos quatro temas (`cetec`, `noticias`, `moderno`, `wab`) e em `templates/pub/base.html.twig`, removendo os blocos duplicados que ele substitui; verificar com `grep -rn "og:site_name" templates/themes` apontando só para o parcial
- [x] 2.7 Passar os dados de SEO em `MainController`, `WabPublicController` e nos controllers do portal; verificar com `curl` em `localhost`, `127.0.0.1` e `noticias.localhost` que home, página, categoria, notícia, editoria, listagem e autor trazem título e descrição diferentes entre si

## 3. Trilha de navegação, robots e sitemap

- [x] 3.1 Criar `templates/pub/_trilha.html.twig` (nav com `aria-label`, item atual com `aria-current="page"`) e usá-lo nas páginas que não são a home dos temas `cetec` e `noticias`; verificar numa notícia local que a trilha mostra Início › editoria › título e bate com o `BreadcrumbList`
- [x] 3.2 Criar a rota `/robots.txt` em `src/Controller/pub/RobotsController.php` com `Sitemap:` do domínio aberto, `Disallow` de `/admin`, `/superadmin`, `/jornalista`, `/login` e `/busca`, e bloqueio total em domínio sem tenant; apagar `public/robots.txt`; verificar com `curl` em dois hosts locais que cada um traz o próprio `Sitemap:`
- [x] 3.3 Reescrever `src/Controller/pub/SitemapController.php` conforme a requirement `Sitemap` do delta de `construtor-de-paginas`: só publicadas, sem repetir a home, com categorias com conteúdo e, no tema `noticias`, editorias e autores com notícia visível; `lastmod` em ISO 8601 com fuso e o da home igual à alteração mais recente
- [x] 3.4 Cobrir o sitemap em `tests/Controller/SitemapTest.php` com os cenários da spec (página que serve de home, rascunho, data de alteração, portal só com exemplos, site institucional) e verificar com `vendor/bin/phpunit --filter Sitemap` passando
- [x] 3.5 Marcar `noindex` em `/busca`, `/jornalista/cadastro`, `/jornalista`, `/login` e nos painéis, e conferir que as páginas públicas seguem com `index, follow`; verificar com `curl` nas rotas locais

## 4. Ícones em SVG embutido

- [x] 4.1 Instalar `symfony/ux-icons` com `composer require symfony/ux-icons` e verificar com `bin/console debug:config ux_icons` respondendo
- [x] 4.2 Trocar os ~31 ícones fixos dos temas `cetec` e `noticias` de `<i class="bi bi-...">` para `ux_icon('bi:...')` com `aria-hidden` ou rótulo acessível conforme o uso; verificar com `grep -rn 'class="bi ' templates/themes` sem resultado
- [x] 4.3 Criar o filtro Twig `icone_do_painel` que traduz a classe gravada (`fa-solid fa-building`, `fa-brands fa-whatsapp`, `bi bi-...`) para o nome do Iconify e desenha o SVG, não desenhando nada e registrando no log quando o ícone não existe; cobrir a tradução em `tests/Twig/IconeDoPainelTest.php` e verificar com o PHPUnit passando
- [x] 4.4 Criar o comando `app:icones:sincronizar` que varre os ícones gravados em todos os tenants (blocos `blurbs4`, categorias e demais campos de ícone), baixa os SVG e grava no repositório; rodar e verificar que os 8 sites de cliente têm todos os seus ícones gravados
- [x] 4.5 Rodar `bin/console ux:icons:lock`, remover os `<link>` do Bootstrap Icons e do FontAwesome dos quatro temas e verificar com `curl` numa home local que não sobra nenhuma referência a `cdn.jsdelivr.net` nem a `cdnjs.cloudflare.com`
- [x] 4.6 Documentar em `README.md` que ícone novo cadastrado no painel pede `app:icones:sincronizar` antes do deploy; verificar que o comando documentado roda como está escrito

## 5. Fontes locais

- [x] 5.1 Criar o comando `app:fontes:sincronizar` que baixa os `woff2` das 6 famílias de `HeadingFonts` para `assets/fonts/` e gera `assets/styles/fontes.css` com `@font-face` e `font-display: swap`; rodar e verificar que as 6 famílias têm arquivo
- [x] 5.2 Tirar o `@import` do Google Fonts do `tenant_css_vars()` e carregar `fontes.css`; dar `preload` só nas famílias que o tenant usa; verificar com `curl` numa home local que não há requisição para `fonts.googleapis.com` nem `fonts.gstatic.com`
- [x] 5.3 Conferir nos quatro temas que os títulos continuam com a família, o peso e o tamanho gravados no tenant, inclusive a Open Sans do tema `noticias`; verificar comparando a home do ajope local antes e depois

## 6. Carrossel local e animação sem biblioteca

- [x] 6.1 Trazer o Swiper para o `importmap` e servi-lo pelo próprio domínio, carregando-o só quando a página tem carrossel (banner, galeria ou depoimentos), por variável ligada no template do bloco; verificar que uma página sem carrossel não baixa o Swiper e uma com carrossel continua funcionando
- [x] 6.2 Substituir o AOS por `assets/styles/animacoes.css` mais um `IntersectionObserver` que lê os atributos `data-aos` já existentes; remover os `<link>` e `<script>` do `unpkg.com` dos quatro temas; verificar numa página com bloco animado que a animação acontece e que nada sai para `unpkg.com`
- [x] 6.3 Conferir que o CSS compilado do tema não carrega regra de biblioteca que saiu, rodando `./build.sh` local e comparando o tamanho de `assets/styles/app.css` antes e depois

## 7. Imagens pela biblioteca de miniaturas

- [x] 7.1 Generalizar `src/Twig/NewsImageExtension.php` no filtro `thumb`, mantendo o `news_image` como apelido e o desvio para o arquivo original quando o servidor não converte o formato; cobrir os dois caminhos em teste e verificar com o PHPUnit passando
- [x] 7.2 Acrescentar em `config/packages/liip_imagine.yaml` os conjuntos `banner_desktop`, `banner_mobile`, `capa_pagina`, `bloco_imagem`, `galeria`, `equipe`, `avatar`, `logo_parceiro`, `logo_site`, `fundo_secao`, `capa_noticia` e `capa_noticia_mobile`, em WebP, com largura igual ao dobro da exibição e o modo de corte certo para cada um; verificar gerando uma miniatura de cada no ambiente local
- [x] 7.3 Aplicar o filtro em todas as `<img>` do tema `cetec` (home, página, categoria, artigo e os 12 blocos), com `width`, `height`, `loading` e `fetchpriority`, e `<picture>` com `srcset` onde a largura de exibição muda entre celular e computador; verificar com `grep -rn 'uploads/' templates/themes/cetec` sem `<img src>` direto
- [x] 7.4 Fazer o mesmo no tema `noticias` (capa da notícia em `<picture>`, foto do autor, logo e favicon) e nos temas `moderno` e `wab`; verificar com `grep -rn 'uploads/' templates/themes` sem `<img src>` direto
- [x] 7.5 Trocar o fundo de seção de `style="background-image:url(/uploads/...)"` por `<img>` atrás do conteúdo, com miniatura e dimensões, em `cetec/page`, `cetec/category` e `noticias/_sections`; verificar visualmente que opacidade, posição e sobreposição de texto continuam iguais
- [x] 7.6 Dar `preload` com `imagesrcset` à imagem do LCP de cada tipo de página e `fetchpriority="high"` na `<img>` correspondente, com o mesmo endereço nos dois; verificar no Lighthouse local que `prioritize-lcp-image` e `lcp-discovery-insight` passam numa home
- [x] 7.7 Conferir lado a lado, em `localhost`, `127.0.0.1` e `noticias.localhost`, que nenhuma imagem mudou de enquadramento, e registrar no `docs/pagespeed-17-11-2026.md` o peso da home antes e depois

## 8. Acessibilidade das páginas públicas

- [x] 8.1 Corrigir a hierarquia de títulos nos temas (um `h1` por página e sem pular nível) e verificar que a auditoria `heading-order` passa no Lighthouse local das 9 páginas de referência
- [x] 8.2 Pôr `title` no iframe do mapa e nome acessível nos botões de ícone (busca, menu, modo claro/escuro, WhatsApp); verificar que `frame-title` e `button-name` passam
- [x] 8.3 Corrigir o contraste das combinações reprovadas e dar distinção visual além da cor aos links dentro de parágrafo; verificar que `color-contrast` e `link-in-text-block` passam
- [x] 8.4 Levar os alvos de toque pequenos a 24×24 px com espaçamento e verificar que `target-size` passa
- [x] 8.5 Encontrar e corrigir o erro de console que derruba Práticas recomendadas para 96 no `site.topdrim.com.br`; verificar que `errors-in-console` passa

## 9. Cache dos estáticos

- [x] 9.1 Levantar, por leitura, a configuração atual do Nginx do webapp `app-wab-sites` e salvar uma cópia; verificar que a cópia está guardada antes de qualquer alteração
- [ ] 9.2 (bloqueada: o usuário `runcloud` não tem `sudo` e não lê `/etc/nginx-rc/conf.d/`; o trecho pronto está em `docs/nginx-cache-dos-estaticos.md`, para o painel do RunCloud. A auditoria `uses-long-cache-ttl` tem peso 0 na nota, então não trava o 100) Com aprovação do Jonas, pôr `expires 1y` e `Cache-Control: public, immutable` em `/assets` e `/media/cache`, e 30 dias em `/uploads`; verificar com `curl -I` nos três caminhos em produção

## 10. Conferência de ponta a ponta

- [x] 10.1 Rodar `vendor/bin/phpunit` inteiro e `openspec validate seo-e-performance-dos-sites --strict`, os dois sem erro
- [x] 10.2 Conferir no ambiente local, nos três hosts, que as 9 páginas de referência (home, página institucional, categoria, notícia, editoria, listagem, autor, busca e cadastro de jornalista) abrem sem erro e com as metas certas
- [x] 10.3 Depois do deploy e do aquecimento, rodar `sh/medir-pagespeed.sh depois` e `sh/comparar-pagespeed.php`; verificar que os 9 domínios marcam 100 nas quatro categorias nos dois formatos, ou registrar o que ficou abaixo e por quê
- [x] 10.4 Confirmar no PageSpeed Insights oficial, com print, a home de cada um dos 9 domínios em celular e computador

## Workflow follow-up

- Deploy em produção (só com aprovação do Jonas): `git pull`, `composer install`, `./build.sh` no servidor wabsites; depois aquecer as 9 homes antes de medir.
- Registrar o resultado no ticket interno da WAB (cliente 196) com as medições antes e depois.
- Preencher, com a equipe, a descrição de SEO dos tenants que estão sem ela.
- Arquivar a change depois da conferência em produção.
