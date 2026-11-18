# Proposal

## Why

Os 9 sites em produção perdem posição no Google por dois motivos medidos em 17/11/2026. **SEO:** nenhuma home tem `<meta name="description">` — o Lighthouse reprova a auditoria `meta-description` nas 18 execuções (9 domínios × celular e computador) e a nota de SEO trava em 92 em todos eles; não há Open Graph completo, dados estruturados nem `robots.txt` por domínio (o arquivo em produção é o mesmo texto de exemplo para os 9 sites, com a linha `Sitemap:` comentada), e o `/sitemap.xml` do ajope lista 3 URLs e nenhuma notícia. **Performance:** o tema `cetec`, usado pelos 8 sites de cliente, não usa nenhum filtro da biblioteca de miniaturas (LiipImagine) que o projeto já tem configurada — serve o PNG original do upload. A home do `site.abifer.com.br` baixa 17,9 MB em 30 imagens, a do `site.topdrim.com.br` 15,6 MB e a do `cemporcentoexpresso.com.br` 13,2 MB, com arquivos de até 3,0 MB. O PageSpeed oficial dá 74 de desempenho no celular para `imperiodainformatica.com.br`; o Lighthouse local mede de 69 a 92 no celular e reprova `modern-image-formats`, `uses-responsive-images`, `unsized-images`, `render-blocking-resources`, `uses-long-cache-ttl` e `font-display` nas 18 execuções.

## What Changes

- **Metas de toda página pública.** Todo template público passa a sair com `<title>`, `<meta name="description">`, `<link rel="canonical">`, Open Graph completo (`og:title`, `og:description`, `og:url`, `og:image`, `og:locale`) e Twitter Card, com uma cascata de origens que nunca deixa a descrição vazia: campo de SEO da página → campo de SEO do site → primeiro texto do conteúdo → nome e ramo do site. Vale para `cetec`, `noticias`, `moderno` e `wab`.
- **Dados estruturados (JSON-LD).** Cada página passa a trazer o tipo certo do schema.org: `Organization` + `WebSite` na home, `WebPage` nas internas, `BreadcrumbList` em tudo que não é home, e, no tema `noticias`, `NewsArticle` na notícia (com autor, editoria, datas, imagem e seção), `CollectionPage` + `ItemList` na editoria e na listagem, e `ProfilePage` + `Person` na página do autor.
- **`robots.txt` por tenant.** O arquivo estático sai e vira rota do Symfony, com o `Sitemap:` do domínio aberto, bloqueio de `/admin`, `/superadmin`, `/jornalista`, `/login` e `/busca`, e `noindex` para domínio que não é tenant.
- **`/sitemap.xml` correto.** Passa a listar só páginas publicadas, sem repetir a home como `/p/<slug>`, com as categorias do site e, no tema `noticias`, as notícias, as editorias e os autores aprovados; `lastmod` com data e hora reais em vez da data de hoje.
- **Acessibilidade das páginas públicas.** Correção do que o Lighthouse reprova hoje e que o Google usa como sinal: hierarquia de títulos (`heading-order`), contraste (`color-contrast`), link dentro de parágrafo sem distinção visual (`link-in-text-block`), `title` no iframe do mapa (`frame-title`), nome acessível em botão de ícone (`button-name`) e área mínima de toque (`target-size`).
- **Imagens pela biblioteca de miniaturas.** Todas as imagens dos temas passam a sair pelo LiipImagine, em WebP, no tamanho em que são exibidas: banner/hero com versão de celular e de computador, capa de página, imagem de bloco, galeria, foto de equipe, avatar de depoimento, logo de parceiro, logo e favicon do site, fundo de seção e capa da notícia. Toda `<img>` passa a ter `width` e `height`, `loading` e `fetchpriority` coerentes com a posição na página, e a imagem do LCP ganha `preload`.
- **CSS e JS locais no lugar dos CDNs.** Os quatro CSS externos de três origens (Swiper, Bootstrap Icons, AOS, FontAwesome) e os dois JS externos saem do caminho de renderização: passam a ser servidos pelo próprio domínio, com subconjunto dos ícones usados, `font-display: swap` e carregamento só nas páginas que precisam.
- **Cache dos estáticos.** `/assets` e `/media/cache` (conteúdo com impressão digital no nome) passam a 1 ano imutável; `/uploads` fica em 30 dias.
- Fora do escopo: `abifer.com.br`, `transterraararaquara.com.br`, `petvida.com.br` e `topdrim.com.br` na raiz — esses quatro domínios são servidos por outros sistemas (dois sites antigos em HTML e dois WordPress no wab2); deste sistema são os subdomínios `site.*` correspondentes.

## Capabilities

### New Capabilities

- `seo-das-paginas`: o que toda página pública manda para buscador e rede social — título, descrição, canonical, Open Graph e Twitter, dados estruturados por tipo de página, `robots.txt` por domínio, trilha de navegação, e o SEO próprio da notícia, da editoria, da listagem e do autor no tema `noticias`.
- `desempenho-das-paginas`: como a página é entregue — imagens pela biblioteca de miniaturas, em formato moderno e no tamanho exibido, com dimensões, prioridade e preload; CSS e JS servidos pelo próprio domínio e só onde são usados; política de cache dos estáticos; e o patamar de nota do PageSpeed que os sites precisam manter.

### Modified Capabilities

- `isolamento-por-dominio`: a requirement "Domínio não cadastrado" ganha uma exceção — `/robots.txt` segue para o controller mesmo sem tenant, para responder proibindo o rastreamento em vez de 404.
- `construtor-de-paginas`: a requirement do `/sitemap.xml` muda — hoje ela manda listar a home e uma URL `/p/<slug>` por página do tenant; passa a excluir rascunhos e a página usada como home, a incluir as categorias e, no tema `noticias`, as editorias e os autores, e a usar `lastmod` com a data real de alteração.

## Impact

- **Templates** (`templates/themes/cetec`, `noticias`, `moderno`, `wab` e `templates/pub/base.html.twig`): bloco de `<head>` comum com metas, JSON-LD e preload; todas as `<img>` passando pelo filtro de miniatura com `width`/`height`; iframe do mapa com `title`; correções de hierarquia de títulos e de contraste. Os 8 tenants de cliente usam o `cetec`; o ajope usa o `noticias` e, nas páginas institucionais, os blocos do `cetec`.
- **Código novo**: serviço de SEO (`src/Service/`) que monta a cascata de metas e o JSON-LD por tipo de página; extensão Twig com o filtro genérico de miniatura (generalização do `news_image`, mantendo o desvio para o arquivo original quando o GD do servidor não converte o formato) e com a função de dados estruturados; controller de `robots.txt`.
- **Código alterado**: `src/Controller/pub/SitemapController.php` (regras novas), `src/Twig/NewsImageExtension.php` (vira o filtro geral), `src/Controller/pub/MainController.php` e `WabPublicController.php` (dados de SEO para a view).
- **Configuração**: `config/packages/liip_imagine.yaml` ganha os conjuntos de filtro das imagens do `cetec`; `config/packages/routing.yaml` e rotas novas; `importmap.php`/`assets/` recebem Swiper, AOS e os ícones locais.
- **Produção**: `public/robots.txt` deixa de ser servido como arquivo. O Nginx do servidor wabsites precisa de ajuste de `expires` em `/assets` e `/media/cache` — escrita em produção, só com aprovação do Jonas. O primeiro acesso depois do deploy gera as miniaturas novas, o que pesa num servidor de 1 vCPU: o `build.sh` apaga `public/media/cache/*`, então o aquecimento precisa ser feito antes de medir.
- **Dependência nova**: `symfony/ux-icons` (pacote oficial do Symfony UX, já descrito no `CLAUDE.md` do projeto como a ferramenta de ícones SVG), para trocar as duas bibliotecas de ícones por SVG embutido. Os ícones usados ficam gravados no repositório, então o servidor não busca nada na hora de renderizar. Sem mudança de esquema do banco.
