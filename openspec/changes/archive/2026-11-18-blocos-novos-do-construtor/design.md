# Design

## Context

Como um tipo de bloco existe hoje (conferido no código em 18/11/2026):

| Ponto | Arquivo | O que tem |
|---|---|---|
| Catálogo | `src/Entity/Enum/BlockType.php` | 15 `case`, `label()` e `icon()` |
| Escolha do tipo | `templates/admin/block/new.html.twig` | lista `types` escrita à mão (key, label, icon, desc) |
| Formulário | `templates/admin/block/_form_<tipo>.html.twig` | incluído por nome em `new` e `edit`; campo oculto `type` |
| Gravação | `AdminContentController::populateBlock()` (linhas 584–856) | `preTitle`, `title`, `text`, `embedUrl`, `itemCount`, `relatedCategoryId`, `imageFile`, e **tudo que vier em `config[...]` é gravado como está** no JSON; ramos próprios só para `gallery`, `testimonials`, `partner_logos`, `team`, `banner` |
| Desenho | `templates/themes/<tema>/_block.html.twig` | cadeia `if/elseif` por tipo → `blocks/_<tipo>.html.twig`. `noticias/_block` só inclui o do cetec |
| Duplicar | `DuplicatorService::duplicateBlock()` | `clone` (leva `config` e `image`) + cópia de galeria, depoimentos, logos, equipe |
| Exportar/importar | `TenantExporter::serializeBlocks()` e `collectConfigImages()`; `TenantImporter` (bloco "6. Blocks") | serializa colunas + `config`; imagens em `config.banners[].image` e `config.image` |
| Miniaturas | `config/packages/liip_imagine.yaml`, `AquecerMiniaturasCommand` (mapa pasta → filtros) | `uploads/page_block` → `bloco_imagem`, `banner_desktop`, `banner_mobile`; `uploads/page_block_gallery` → `galeria` |
| Menu landing | `themes/cetec/_header.html.twig` (desktop, linha ~35, e celular, linha ~128) | rótulo pelo tipo do primeiro bloco |

Os quatro temas carregam `styles/app.css` (Tailwind compilado de todos os templates), então as classes `cetec-*` funcionam em qualquer tema.

Todos os tenants reais (1 a 9) usam o cetec; o 10 usa o noticias, que desenha blocos com os parciais do cetec. `moderno` e `wab` só existem nas fixtures.

## Goals / Non-Goals

**Goals:**
- Sete tipos novos utilizáveis em qualquer site, com o menor número possível de pontos de código tocados.
- Nenhuma diferença no HTML das páginas que existem hoje.
- Deixar pronto o contrato de dados (`config`) que o tema `atelie` (mudança 3) vai desenhar do jeito dele.

**Non-Goals:**
- Desenho próprio dos blocos novos em `moderno` e `wab` (usam o parcial do cetec).
- Bloco `blog_posts` (vem na mudança `blog-nos-sites-institucionais`, que cria o endereço do blog).
- Refatorar o dispatcher, o `populateBlock` ou os 15 blocos existentes.
- Campos extras no formulário de contato (Cidade, Tipo de projeto): fora desta série; o bloco `contact` fica como está.

## Decisions

### D1. Dados dos blocos: colunas que já existem + `config`

Nenhuma coluna nova em `page_block`. O `populateBlock` já grava `config[...]` inteiro, então os formulários novos só precisam nomear os campos como `config[chave]`.

| Tipo | Colunas usadas | Chaves de `config` |
|---|---|---|
| `hero_split` | `preTitle`, `title`, `text`, `image` | `titleHighlight` (trecho do título a destacar), `ctaText`, `ctaLink`, `stampNumber`, `stampLabel`, `caption` |
| `highlights` | — (sem título) | `items[0..3]`: `title`, `text` |
| `portfolio` | `preTitle`, `title`, `text`, fotos em `galleryImages` | `note` (texto ao pé), `ctaText`, `ctaLink` |
| `statement` | `preTitle`, `title`, `text`, `image` | `statNumber`, `statLabel`, `imagePosition` (`right` padrão, `left`) |
| `steps` | `preTitle`, `title`, `text` | `steps[0..5]`: `label`, `title`, `text` |
| `cta` | `preTitle`, `title`, `text` | `ctaText`, `ctaLink`, `ctaNewTab` (`1`/vazio) |
| `video` | `preTitle`, `title`, `text`, `embedUrl` (link do YouTube), `image` (capa do MP4) | `videoFile` (nome do MP4 enviado), `orientation` (`horizontal` padrão, `vertical`), `videoPosition` (`right` padrão, `left`), `autoplay` (`1`/vazio), `ctaText`, `ctaLink` |

`items` em `highlights` usa a chave `items`, que o `populateBlock` percorre para tratar `link`/`externalUrl`; como os itens não têm `link`, o laço só grava `externalUrl: ''` em cada um. Aceitável e inofensivo. As etapas usam `steps` para não passar por esse laço.

*Alternativa descartada:* entidade filha para etapas e destaques (como depoimentos). Mais código em 5 lugares (entidade, migration, populate, duplicar, exportar) para listas curtas e sem imagem.

### D2. Portfólio reaproveita a foto de galeria

`PageBlockImage` ganha `title` (255, nulo) e `category` (100, nulo). O bloco `portfolio` usa a mesma coleção `galleryImages`, a mesma pasta `uploads/page_block_gallery` e o mesmo mapeamento do Vich. No `populateBlock`, o ramo `if ($type === 'gallery')` passa a valer para `gallery` e `portfolio`, e ganha, só para `portfolio`, a leitura de `gallery_meta[<id>][title|category|caption|position]` para as fotos já gravadas. Foto nova entra sem título nem categoria e é editada na gravação seguinte (mesmo fluxo de hoje: enviar, salvar, voltar).

As categorias do filtro são os valores distintos de `category` das fotos do bloco, na ordem da primeira ocorrência; "Todos" vem sempre primeiro. Foto sem categoria só aparece em "Todos". Com uma categoria só (ou nenhuma), o filtro não é desenhado.

*Alternativa descartada:* projeto como página (`Page`) com galeria própria. O site atual não tem projetos estruturados (o tipo `project` do WordPress está vazio; há 31 fotos soltas), e o layout aprovado é uma grade de fotos.

### D3. Vídeo: YouTube por link, MP4 por upload em pasta própria

- YouTube: o link vai em `embedUrl`. O id é extraído no Twig por um filtro novo `youtube_id` (aceita `watch?v=`, `youtu.be/`, `shorts/`, `embed/`), posto em `src/Twig/TenantExtension.php`. Player `youtube-nocookie.com`, com `loading="lazy"`, como na notícia.
- MP4: `videoFile` enviado pelo campo `videoUpload`; o `populateBlock` move o arquivo para `public/uploads/page_block_video/` com nome `video_<uniqid>.mp4` e grava o nome em `config.videoFile` (mesma técnica dos slides do banner). Só `video/mp4`, até 30 MB; arquivo recusado gera flash de erro e mantém o anterior. `<video>` com `preload="none"`, `playsinline`, `controls` (ou `autoplay muted loop` quando `autoplay` = 1) e `poster` da imagem do bloco.
- Exportador e importador ganham a pasta `page_block_video` (chave de mídia nova) e leem `config.videoFile`.

*Alternativa descartada:* reaproveitar o vídeo de fundo da seção. Serve para fundo, não para um vídeo com controles ao lado de um texto (três usos no site atual).

### D4. Temas

- **cetec**: sete parciais novos em `templates/themes/cetec/blocks/`, com as classes Tailwind `cetec-*` e o cabeçalho de bloco que os outros usam (`_pre_title.html.twig`, `h2`, traço laranja). Sete `elseif` novos no `_block.html.twig`.
- **moderno** e **wab**: um `elseif` só em cada `_block.html.twig`: `{% elseif t in ['hero_split','highlights','portfolio','statement','steps','cta','video'] %}{% include 'themes/cetec/blocks/_' ~ t ~ '.html.twig' %}`. A lista é literal, então o `t` vindo do banco nunca monta um caminho arbitrário.
- **noticias**: nenhum arquivo muda (já inclui `themes/cetec/_block.html.twig`).
- O parcial do cetec não pode depender de nada do `base.html.twig` do cetec além de `app.css`, `ux_icon` e as variáveis de `tenant_css_vars()`; o JavaScript do filtro do portfólio vai em `<script>` dentro do próprio parcial, no padrão do `_gallery.html.twig` (funções em `window`, criadas uma vez).

Isolamento por tenant: os parciais só leem o `block` que a página ou categoria do tenant entregou; nenhum faz consulta própria.

### D5. Imagens

Conjuntos novos no `liip_imagine.yaml`, no padrão dos existentes (WebP, largura = dobro da exibição):

| Filtro | Uso | Tamanho |
|---|---|---|
| `destaque_foto` | foto do `hero_split` e do `statement` | 1400 de largura, sem corte |
| `destaque_foto_mobile` | idem no celular | 800 de largura |
| `portfolio` | fotos da grade | 800×640, corte (`outbound`) |
| `portfolio_grande` | primeira foto da grade e lightbox | 1400 de largura, sem corte |

`AquecerMiniaturasCommand`: acrescentar `destaque_foto` e `destaque_foto_mobile` em `uploads/page_block`, e `portfolio` e `portfolio_grande` em `uploads/page_block_gallery`.

Pré-carga do LCP: `imagem_do_lcp()` já devolve a imagem do primeiro bloco da primeira seção quando não há banner. Os filtros `banner_*` recortam em 16:9, o que estragaria a foto do destaque; por isso foi criada a função `filtros_do_lcp(_context, sizes)` em `AtivosExtension`, que devolve `banner_mobile`/`banner_desktop` com `100vw` (o de sempre, mesma saída para todas as páginas que já existiam) e, quando a imagem vem de um `hero_split` ou `statement`, `destaque_foto_mobile`/`destaque_foto` com o `sizes` do tema. Os `<head>` dos quatro temas passaram a usar essa função, e o parcial desenha a foto com o mesmo `srcset` e `sizes` da pré-carga (e `fetchpriority="high"` quando é o primeiro bloco da página).

### D6. Rede de proteção

1. **Fotografia do HTML** (`sh/regressao.sh capturar|comparar`): baixa com `curl` uma lista fixa de URLs dos três tenants das fixtures (`127.0.0.1:8000`, `localhost:8000`, `noticias.localhost:8000`) para `var/regressao/<antes|depois>/`, normaliza o que muda sozinho (valor de `_token`, valor de `wab_t`, sufixo de hash em `/assets/…-<hash>.css|js`, `lastmod` do sitemap, textos "há N minutos") e roda `diff -r`. A captura "antes" é feita no commit anterior à primeira alteração, com as fixtures recém-carregadas, e **as fixtures não são recarregadas** até a comparação final (ids de seção e bloco aparecem no HTML). `var/` já está no `.gitignore`.
2. **Fixtures novas em grupo próprio**: `CetecFixtures` (domínio `cetec.localhost`, tema cetec, página `todos-os-blocos` com os 22 tipos), implementando `FixtureGroupInterface` com o grupo `cetec` e carregada com `doctrine:fixtures:load --append --group=cetec`. `AppFixtures` e `PortalNoticiasFixtures` não mudam.
3. **Teste estrutural** `tests/Entity/BlockTypeCoberturaTest.php`: para cada `BlockType::cases()`, existe `templates/admin/block/_form_<tipo>.html.twig`, existe `templates/themes/cetec/blocks/_<tipo>.html.twig`, e o texto do `_block.html.twig` de cetec, moderno e wab contém `'<tipo>'`. A mudança 3 estende o teste para o tema novo.

### D7. Menu do Modo Landing Page

Nas duas cadeias de `_header.html.twig` do cetec (desktop e celular), acrescentar: `hero_split` → "Início"; `portfolio` → "Portfólio" (o `in ['gallery', 'portfolio']` já existe e passa a ter efeito); `statement` → "Sobre"; `steps` → "Como trabalhamos"; `highlights` → "Diferenciais"; `video` → "Vídeo"; `cta` → "Contato". O `moderno` tem lógica parecida: conferir e, se tiver a mesma cadeia, aplicar igual.

## Risks / Trade-offs

- [O Tailwind recompila e o `app.css` cresce para todos os sites] → medir `public/assets/styles/app-*.css` antes e depois; aceitar até +6 KB minificado. Acima disso, trocar utilitários repetidos por classe própria no parcial.
- [Upload de MP4 grande estoura `upload_max_filesize` do PHP] → o formulário diz o limite; erro de upload vira flash, não exceção. Conferir o limite do pool em produção (leitura) antes do deploy.
- [`diff` da regressão acusa ruído] → cada padrão de ruído vira uma regra de normalização no script, nunca um "ignorar arquivo".
- [Bloco novo gravado e depois o código volta atrás (rollback)] → tipo desconhecido não renderiza nada (comportamento atual do dispatcher); as duas colunas novas são nulas e o `down` da migration as remove.
- [Importar em produção um ZIP exportado antes desta mudança] → chaves ausentes (`title`, `category`, `videoFile`) são tratadas como nulas.

## Migration Plan

1. Local: migration aditiva (`page_block_image.title`, `page_block_image.category`).
2. Deploy (escrita em produção, só com aprovação do Jonas): backup do banco; `git pull`; `./build.sh`; `bin/console doctrine:migrations:migrate`; HTTP 200 nos domínios de cliente.
3. Rollback: `git checkout` do commit anterior + `./build.sh`; a migration pode ficar aplicada (colunas nulas sem uso) ou ser desfeita com `migrate prev`.
