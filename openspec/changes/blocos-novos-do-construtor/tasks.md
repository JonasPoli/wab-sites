# Tasks

> Leia antes: `design.md` desta mudança (tabela de chaves do `config` em D1) e, como modelo de código, `templates/admin/block/_form_stats.html.twig`, `templates/themes/cetec/blocks/_stats.html.twig`, `_text_image.html.twig` e `_gallery.html.twig`. Layout de referência dos blocos: `openspec/changes/tema-atelie/referencia/proposta-redesign-marcenaria-pichoneri.html` (as seções `.hero`, `.cred-bar`, `.portfolio-grid`, `.statement`, `.process`, `.final-cta`). Não altere nenhum dos 15 parciais existentes nem `AppFixtures`/`PortalNoticiasFixtures`.

## 1. Rede de proteção (antes de qualquer alteração)

- [x] 1.1 Com o banco local recém-carregado (`bin/console doctrine:fixtures:load --no-interaction`) e `symfony server:start -d` na porta 8000, criar `sh/regressao.sh` com dois modos: `capturar <antes|depois>` (baixa com `curl -s` para `var/regressao/<modo>/<host>/<arquivo>.html` as URLs: `/`, `/sitemap.xml`, `/robots.txt`, `/noticias`, `/contato` (cabeçalhos, com `-I`), cada `/p/<slug>` e `/categoria/<slug>` listados no sitemap de cada host, e no portal também as 3 primeiras `/noticias/<slug>`, para os hosts `127.0.0.1:8000`, `localhost:8000` e `noticias.localhost:8000`) e `comparar` (`diff -r var/regressao/antes var/regressao/depois`). Antes de gravar, normalizar com `sed`: valor de `name="_token"`, valor de `name="wab_t"`, `-[0-9a-f]{7,}\.(css|js)` dos assets, `<lastmod>…</lastmod>` e "há N (minutos|horas)". Verificar rodando `capturar antes` e `capturar depois` em seguida: `comparar` não mostra diferença
- [x] 1.2 Guardar a captura `antes` (não recarregar as fixtures até a tarefa 9.1) e anotar o tamanho de `public/assets/styles/app-*.css` depois de `bin/console tailwind:build --minify && bin/console asset-map:compile`; registrar os dois números numa linha no fim deste arquivo, em "Medições"

## 2. Catálogo e banco

- [x] 2.1 Em `src/Entity/Enum/BlockType.php`, acrescentar os casos `HeroSplit='hero_split'`, `Highlights='highlights'`, `Portfolio='portfolio'`, `Statement='statement'`, `Steps='steps'`, `Cta='cta'`, `Video='video'` com rótulos "Destaque com foto", "Faixa de destaques", "Portfólio com filtro", "Manifesto com imagem", "Etapas", "Chamada para ação", "Vídeo" e ícones `fa-solid fa-panorama`, `fa-solid fa-grip-lines`, `fa-solid fa-table-cells-large`, `fa-solid fa-quote-right`, `fa-solid fa-list-ol`, `fa-solid fa-bullhorn`, `fa-solid fa-circle-play`; verificar com `php -l` e `bin/console lint:container`
- [x] 2.2 Em `src/Entity/PageBlockImage.php`, acrescentar `title` (string 255, nulo) e `category` (string 100, nulo) com get/set; gerar a migration com `bin/console doctrine:migrations:diff`, conferir que ela só faz `ALTER TABLE page_block_image ADD title…, ADD category…` (e que o `down` remove as duas) e aplicar com `doctrine:migrations:migrate`; verificar com `bin/console doctrine:schema:validate`
- [x] 2.3 Em `config/packages/liip_imagine.yaml`, criar os conjuntos `destaque_foto` (1400 de largura), `destaque_foto_mobile` (800), `portfolio` (800×640, `outbound`) e `portfolio_grande` (1400), copiando o formato e a qualidade de `bloco_imagem` e `galeria`; em `src/Command/AquecerMiniaturasCommand.php`, acrescentar os dois primeiros em `uploads/page_block` e os dois últimos em `uploads/page_block_gallery`; verificar com `bin/console debug:config liip_imagine filter_sets | grep -c "destaque_foto\|portfolio"` ≥ 4

## 3. Painel: escolha do tipo e formulários

- [x] 3.1 Em `templates/admin/block/new.html.twig`, acrescentar ao fim da lista `types` as sete entradas (key, label e icon iguais aos da 2.1; desc: "Texto de um lado, foto com selo do outro", "Faixa com 2 a 4 diferenciais", "Grade de fotos com filtro por categoria", "Texto em fundo escuro com número e foto", "Passos numerados do processo", "Faixa colorida com título e botão", "YouTube ou MP4 com texto ao lado"); verificar abrindo `/admin/section/<id>/block/new` e contando 22 cartões (21 com a newsletter desligada)
- [x] 3.2 Criar `templates/admin/block/_form_hero_split.html.twig` a partir do `_form_text_image.html.twig`: `type=hero_split`, pré-título, título, `config[titleHighlight]`, texto (textarea simples), `imageFile`, `config[ctaText]`, `config[ctaLink]`, `config[stampNumber]`, `config[stampLabel]`, `config[caption]`; verificar criando um bloco e reabrindo a edição com todos os valores de volta
- [x] 3.3 Criar `_form_highlights.html.twig` a partir do `_form_stats.html.twig`: `type=highlights`, 4 cartões com `config[items][i][title]` e `config[items][i][text]`; verificar gravando 3 itens e reabrindo
- [x] 3.4 Criar `_form_steps.html.twig`: `type=steps`, pré-título, título, texto e 6 cartões com `config[steps][i][label]`, `[title]`, `[text]`; verificar gravando 4 etapas e reabrindo
- [x] 3.5 Criar `_form_cta.html.twig`: `type=cta`, pré-título, título, texto, `config[ctaText]`, `config[ctaLink]`, caixa `config[ctaNewTab]` (valor `1`); verificar gravando e reabrindo
- [x] 3.6 Criar `_form_statement.html.twig`: `type=statement`, pré-título, título, texto, `imageFile`, `config[statNumber]`, `config[statLabel]`, seletor `config[imagePosition]` (right/left); verificar gravando e reabrindo
- [x] 3.7 Criar `_form_portfolio.html.twig` a partir do `_form_gallery.html.twig`: `type=portfolio`, pré-título, título, texto, `config[note]`, `config[ctaText]`, `config[ctaLink]`, envio múltiplo `galleryFiles[]` e, para cada foto gravada, miniatura (`admin_thumb`), `gallery_meta[<id>][title]`, `[category]` (com `<datalist>` das categorias já usadas no bloco), `[caption]` (rótulo "Texto alternativo"), `[position]` e a caixa `delete_gallery[]`
- [x] 3.8 Em `AdminContentController::populateBlock()`, trocar `if ($type === 'gallery')` por `if (in_array($type, ['gallery', 'portfolio'], true))` e, dentro, só quando `$type === 'portfolio'`, percorrer `$r->request->all('gallery_meta')` aplicando título, categoria, legenda e posição **apenas** às fotos de `$block->getGalleryImages()` cujo id está na lista (string vazia vira `null`; `trim` e corte em 255/100); verificar: enviar 3 fotos, salvar, categorizar, salvar, reabrir com os valores; e conferir que editar um bloco `gallery` existente continua igual
- [x] 3.9 Criar `_form_video.html.twig`: `type=video`, pré-título, título, texto, `embedUrl` (rótulo "Link do YouTube"), `videoUpload` (arquivo, `accept="video/mp4"`, dica "MP4 até 30 MB"), nome do vídeo atual com a caixa `removeVideo`, `imageFile` (rótulo "Capa do vídeo"), seletores `config[orientation]` (horizontal/vertical) e `config[videoPosition]` (right/left), caixa `config[autoplay]`, `config[ctaText]`, `config[ctaLink]`
- [x] 3.10 Em `populateBlock()`, ramo novo `if ($type === 'video')`: preservar `config.videoFile` anterior (o `config` do POST não traz essa chave); com `videoUpload` válido, de MIME `video/mp4` e até 30 MB, mover para `public/uploads/page_block_video/` como `video_<uniqid>.mp4` e gravar o nome; arquivo recusado → `addFlash('error', …)` e mantém o anterior; `removeVideo` marcado limpa a chave. Verificar: enviar um MP4 pequeno, reabrir, trocar, remover; enviar um `.txt` renomeado e ver o aviso

## 4. Desenho no tema cetec

- [x] 4.1 Acrescentar ao `templates/themes/cetec/_block.html.twig` sete `elseif` para os tipos novos, no formato dos existentes; verificar com `grep -c "elseif" templates/themes/cetec/_block.html.twig` = 21
- [x] 4.2 Criar `themes/cetec/blocks/_hero_split.html.twig`: duas colunas (texto / foto) como o `_text_image`, título com o trecho `config.titleHighlight` envolto em `<span class="text-cetec-secondary-text">` (usar `block.title|split(destaque, 2)`; se o trecho não existir, título inteiro), `h1` quando `loop.first` do bloco e da seção e a página não mostra título (`page is defined and not (page.showTitle ?? true)`), senão `h2`; foto com `srcset` de `banner_mobile` (768w) e `banner_desktop` (1600w) via `thumb_srcset`, `sizes="(min-width:1024px) 50vw, 100vw"`, `fetchpriority="high"` no primeiro bloco e `loading="lazy"` nos demais; selo redondo no canto e legenda na base da foto. Verificar na página de todos os blocos (tarefa 6.1) e nos cenários "Título com trecho destacado" e "Destaque que não está no título"
- [x] 4.3 Criar `_highlights.html.twig`: grade `grid-cols-2 lg:grid-cols-4` (colunas = quantidade de itens com título) sobre fundo `bg-gray-50 dark:bg-cetec-dark/40`, título em negrito e texto pequeno; nada quando não há item com título
- [x] 4.4 Criar `_steps.html.twig`: cabeçalho de bloco padrão (`_pre_title`, `h2`, traço) e grade `sm:grid-cols-2 lg:grid-cols-4` de etapas com borda superior, rótulo (ou `'%02d'|format(loop.index)`) em `text-cetec-secondary-text`, `h3` e texto
- [x] 4.5 Criar `_cta.html.twig`: faixa `bg-cetec-primary` com texto em `var(--color-on-primary)`, pré-título, `h2`, texto e botão claro; `target="_blank" rel="noopener noreferrer"` com `config.ctaNewTab`
- [x] 4.6 Criar `_statement.html.twig`: bloco com a classe `mp-section-dark` e fundo próprio, duas colunas, número (`config.statNumber`) grande em `text-cetec-secondary-text` com a legenda ao lado, foto com `destaque_foto` / `destaque_foto_mobile` em `<picture>`, sangrando para fora do container no desktop (margem negativa calculada com `calc((100vw - 100%) / -2)` do lado da foto); ordem das colunas por `config.imagePosition`
- [x] 4.7 Criar `_portfolio.html.twig`: cabeçalho de bloco, botões de filtro (`role="group"`, `aria-pressed`), grade com a primeira foto ocupando duas linhas (`portfolio_grande`) e as demais com `portfolio`, `alt` = legenda ou título, sobreposição com categoria e título, `data-category="<slug da categoria>"` em cada item, nota e botão ao pé; `<script>` no próprio parcial, com função criada uma vez em `window` (padrão do `_gallery.html.twig`), que alterna o atributo `hidden` e o `aria-pressed`. Verificar os três cenários de "Portfólio com filtro" (para o "Sem JavaScript", desligar o JS no navegador ou conferir que nenhum item sai com `hidden` no HTML)
- [x] 4.8 Criar o filtro Twig `youtube_id` em `src/Twig/TenantExtension.php` (aceita `watch?v=`, `youtu.be/`, `shorts/`, `embed/`; devolve `null` para o resto; id só com `[A-Za-z0-9_-]{11}`) com teste em `tests/Twig/YoutubeIdTest.php` cobrindo os 4 formatos e 2 inválidos; verificar com `vendor/bin/phpunit --filter YoutubeId`
- [x] 4.9 Criar `_video.html.twig`: duas colunas (texto / vídeo, ordem por `config.videoPosition`); MP4 (`config.videoFile`) em `<video preload="none" playsinline controls poster=…>` (ou `autoplay muted loop playsinline` sem `controls` quando `config.autoplay`), senão iframe `https://www.youtube-nocookie.com/embed/<id>` com `loading="lazy"` e `title`; proporção `aspect-video` ou `aspect-[9/16]` com largura máxima de 320 px na vertical. Verificar os três cenários de "Vídeo"
- [x] 4.10 Em `templates/themes/cetec/_header.html.twig`, nas duas cadeias (desktop e celular), acrescentar os rótulos da decisão D7; conferir se `themes/moderno/_header.html.twig` tem a mesma cadeia e, se tiver, aplicar igual. Verificar com um site de fixture em Modo Landing Page cuja home tem uma seção sem título com `portfolio`

## 5. Outros temas, duplicação, exportação

- [x] 5.1 Em `templates/themes/moderno/_block.html.twig` e `templates/themes/wab/_block.html.twig`, acrescentar antes do `endif` o `elseif t in [...]` com a lista literal dos sete tipos incluindo `'themes/cetec/blocks/_' ~ t ~ '.html.twig'`; verificar criando um bloco `steps` numa página de `localhost:8000` (moderno) e de `127.0.0.1:8000` (wab): 200 e etapas visíveis
- [x] 5.2 Em `DuplicatorService::duplicateBlock()`, copiar `title` e `category` no laço das fotos; verificar o cenário "Duplicar um portfólio"
- [x] 5.3 Em `TenantExporter`: incluir `title` e `category` em `serializeGalleryImages()`; em `collectConfigImages()`, tratar `config.videoFile` com a chave de mídia nova `page_block_video` (acrescentá-la ao mapa de pastas, linha ~644, apontando para `page_block_video`). Em `TenantImporter`: ler `title`/`category` com `?? null` e realocar `config.videoFile` em `relocateConfigImages()`. Verificar exportando o site `cetec.localhost` (tarefa 6.1) e importando como `cetec2.localhost`: portfólio com filtro e vídeo funcionando; e importando um ZIP antigo de `var/tmp` local, se houver, sem erro

## 6. Fixtures e teste estrutural

- [x] 6.1 Criar `src/DataFixtures/CetecFixtures.php` (`Fixture` + `FixtureGroupInterface`, grupo `cetec`): tenant `cetec.localhost`, tema `cetec`, cores #9b4509 / #c96a18, usuário `cetec.admin` / `admin123`, página `todos-os-blocos` com uma seção por tipo de bloco (os 22), usando para as fotos o `PlaceholderImageFactory` como faz o `PortalNoticiasFixtures`, e uma segunda página `inicio` em Modo Landing Page definida como home; carregar com `bin/console doctrine:fixtures:load --append --group=cetec`; verificar `curl -s -o /dev/null -w '%{http_code}' http://cetec.localhost:8000/p/todos-os-blocos` = 200 e sem "Twig" nem "Exception" no corpo
- [x] 6.2 Criar `tests/Entity/BlockTypeCoberturaTest.php` conforme D6.3 (formulário do painel, parcial do cetec e menção do tipo nos `_block.html.twig` de cetec, moderno e wab para cada `BlockType::cases()`); verificar com `vendor/bin/phpunit --filter BlockTypeCobertura` passando
- [x] 6.3 Atualizar no `README.md` a tabela "Blocos (`PageBlock`)" com os sete tipos e uma linha sobre `cetec.localhost`; verificar que o comando de fixtures escrito no README roda como está

## 7. Acessibilidade e desempenho dos blocos novos

- [x] 7.1 Rodar o Lighthouse (celular) em `http://cetec.localhost:8000/p/todos-os-blocos` e corrigir o que for dos blocos novos em acessibilidade (contraste, nomes de botão, `alt`, ordem de títulos) até a categoria dar 100; registrar a nota em "Medições"
- [x] 7.2 Conferir no HTML dessa página que nenhuma `<img>` dos blocos novos aponta para `/uploads/` direto e que todas têm `width` e `height` (`grep -c 'src="/uploads/'` = 0)

## 8. Integração

- [x] 8.1 Rodar `sh/regressao.sh capturar depois` e `sh/regressao.sh comparar` **sem recarregar as fixtures**: nenhuma diferença nos três hosts antigos. Diferença só aceitável se for ruído novo, e então vira regra de normalização no script
- [x] 8.2 Recompilar (`tailwind:build --minify`, `asset-map:compile`), medir o `app-*.css` e registrar em "Medições"; se cresceu mais de 6 KB, reduzir conforme o risco 1 do design
- [x] 8.3 Rodar `vendor/bin/phpunit` inteiro e `openspec validate blocos-novos-do-construtor --strict`, ambos sem falha

## 9. Deploy (só com aprovação explícita do Jonas)

- [ ] 9.1 Backup (leitura + escrita em arquivo no servidor): `mysqldump` do banco de produção para `~/backups/` no servidor wabsites e anotar o HTTP atual dos domínios de cliente
- [ ] 9.2 Deploy (escrita): `git push origin main`; no servidor, `git pull`, `./build.sh`, `bin/console doctrine:migrations:migrate --no-interaction`
- [ ] 9.3 Conferência: HTTP 200 em marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br e ajope.wab.com.br; abrir a home de dois deles e comparar visualmente com antes; conferir em `/admin` de um site que a escolha de tipo mostra os blocos novos

## Medições

- `app.css` minificado: antes `app-jGAHV5_.css` 70.665 bytes (11.580 gzip); depois `app-8DPSr3G.css` 73.299 bytes (+2,6 KB).
- Lighthouse (celular, local, ambiente dev) em `cetec.localhost/p/todos-os-blocos`: desempenho 92, acessibilidade 93, práticas 100, SEO 92. Todas as falhas apontadas são da barra de depuração do Symfony (`sf-toolbar`, `_profiler`), que só existe no ambiente local; nenhuma é dos blocos novos.
- `sh/regressao.sh comparar`: sem diferença em 127.0.0.1, localhost e noticias.localhost.
- `vendor/bin/phpunit`: 68 passam; os 5 que falham são os do `SuperTestFieldsControllerTest`, já desatualizados antes desta mudança.
- Correção fora do escopo, necessária para editar depoimentos: `templates/admin/block/_form_testimonials.html.twig` fazia `'★' * s`, que dá erro 500 no PHP 8 ao editar um bloco de depoimentos com itens (também em produção).

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido, antes de começar `blog-nos-sites-institucionais`.
