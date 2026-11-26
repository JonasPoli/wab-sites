# Design

## Context

- O bloco `banner` guarda os slides em `page_block.config.banners` (lista de `{title, text, ctaText, ctaLink, active, image}`) e as opções gerais em `config.fullWidth`, `config.fullHeight` e `config.active`, como strings `'1'`/`'0'` (campo oculto `0` + `sl-switch` `1`). Quem grava é `AdminContentController::populateBlock()`; quem monta a prévia é `PreviaDoBloco::slides()`.
- Há quatro parciais de banner: `themes/cetec/blocks/_banner.html.twig` (usado também pelo noticias, cujo `_block` inclui o do cetec), `themes/moderno/…`, `themes/wab/…` e `themes/atelie/…`. Todos desenham a imagem como fundo `position:absolute` com camada por cima (`.hero-overlay` no cetec e no atelie; cor primária a 15% e degradê preto no moderno e no wab), texto e botões, altura mínima de 550/750 px e zoom no `:hover`.
- As miniaturas `banner_desktop` (1600×900) e `banner_mobile` (768×560) são `outbound`: recortam a imagem antes mesmo do CSS. `NewsImageExtension::tamanho()` já calcula `width`/`height` certos para filtros `inset`.
- O `<head>` dos cinco temas pré-carrega a imagem do LCP com `imagem_do_lcp()` e `filtros_do_lcp()` (`AtivosExtension`); para bloco `banner` os filtros devolvidos são sempre `banner_mobile`/`banner_desktop`.
- A prévia do painel (`_previa.html.twig`) refaz o pedido nos eventos `input` e `change` do formulário; o `sl-switch` dispara `sl-change`, que ela não escuta.
- Bloco 147 da Transmai (lido no painel em 26/11/2026): 8 slides, só o primeiro ativo, `fullWidth` ligado, `fullHeight` desligado; imagens entre 1416×701 e 1884×835.

## Goals / Non-Goals

**Goals:**
- Banner "só a imagem" sem nenhum efeito sobre a imagem e sem texto, nos cinco temas, sem mudar uma linha do HTML dos banners que não usam a opção.
- Imagem inteira, sem recorte e sem deslocamento de layout, inclusive no carrossel.
- Manter o LCP: `preload` e `<img>` pedindo os mesmos arquivos.

**Non-Goals:**
- Mexer no desenho dos banners com texto ou no `HeroBanner` da home sem página.
- Imagem própria para o celular, link na imagem, opção por slide.

## Decisions

### D1. Chaves no `config`
- `config.imageOnly`: `'1'` ou `'0'`, no mesmo molde de `fullWidth` (campo oculto `0` + `sl-switch` `1`). Ausente vale `'0'`, então todo banner existente continua como está, sem migration.
- `banners[i].alt`: string, `trim`, sem tags (`strip_tags`), até 250 caracteres (`mb_substr`). Gravada sempre, com a opção ligada ou não.
- Alternativa descartada: `config.mode` com valores nomeados. Um interruptor a mais segue o padrão do formulário e do template (`== '1'`), e não há terceiro modo previsto.

### D2. Um parcial comum para os quatro temas
- Parcial novo `templates/themes/_comum/_banner_so_imagem.html.twig`, que filtra os slides ativos de `block.config.banners`, respeita `config.active`, `fullWidth` e `fullHeight` e desenha imagem, carrossel e o `<style>` do bloco.
- Cada `_banner.html.twig` ganha no topo `{% if (block.config.imageOnly ?? '0') == '1' %}{% include 'themes/_comum/_banner_so_imagem.html.twig' with {raio: …, margem: …} %}{% else %}` e o `{% endif %}` no fim; o corpo antigo não é reindentado nem alterado, o que mantém o HTML dos banners com texto idêntico ao de hoje.
- O tema passa só o que difere: raio do canto quando não é largura inteira (cetec e atelie `24px`, moderno e wab `16px`) e a margem inferior (cetec e atelie `2.5rem`, moderno e wab `3rem`), os mesmos valores que o parcial do tema usa hoje.
- Sem a borda, a sombra e o `:hover` dos banners com texto: são moldura e efeito, que o pedido exclui.
- Alternativa descartada: um `{% if %}` dentro de cada um dos quatro parciais. Repetiria o mesmo HTML quatro vezes, com o risco de os temas divergirem.

### D3. Imagem inteira sem deslocamento de layout
- Filtros novos em `liip_imagine.yaml`: `banner_inteiro` (`thumbnail {size: [1600, 1600], mode: inset}`, webp, qualidade 72) e `banner_inteiro_mobile` (`[768, 768]`, `inset`, qualidade 68). `inset` mantém a proporção e não amplia. As larguras seguem as dos filtros de banner atuais (dobro da exibição no celular, 1600 no computador).
- O contêiner recebe `aspect-ratio: L / A`, com `L` e `A` vindos de `thumb_size(<imagem do 1º slide ativo>, 'banner_inteiro')`; cada `<img>` ocupa o contêiner com `position:absolute; inset:0; width:100%; height:100%; object-fit:cover`. No primeiro slide a proporção é a dele, então nada é recortado; nos outros, a imagem preenche a área (recorta só se a proporção for outra).
- Com `fullHeight`, o contêiner troca `aspect-ratio` por `height:100vh` e o `object-fit:cover` faz o recorte. É `height`, e não o `min-height` dos parciais com texto: aqui nada fica no fluxo, e com `min-height` a altura de `.swiper-wrapper` e dos slides (`height:100%`) não se resolve e eles ficam com altura zero.
- Por que `aspect-ratio` no contêiner, e não `<img>` com `height:auto` no fluxo: o Swiper e o seu CSS só chegam depois do `load` (`assets/carrossel.js`); até lá os slides ficariam empilhados e a página cresceria. Com a área fixada pela proporção e `overflow:hidden`, o primeiro slide aparece sozinho antes e depois do Swiper, e o CLS fica em zero. A mesma marcação serve para um slide ou vários.
- `srcset` com `{'banner_inteiro_mobile': 768, 'banner_inteiro': 1600}` e `sizes="100vw"`, como hoje.

### D4. Carrossel
- Mesmo `new Swiper(...)` dos parciais atuais (`loop`, `effect: 'fade'`, `autoplay` de 5 s, `pagination` e `navigation`), no evento `swiper:pronto`. A regra que decide carregar o Swiper (`AtivosExtension`, mais de um slide ativo em `config.banners`) não muda.
- Primeiro slide com `src`, `fetchpriority="high"` e sem `loading="lazy"`; os demais com `data-adiada`/`data-adiada-srcset`, `loading="lazy"` e `width`/`height`, como hoje (`assets/imagens-adiadas.js`).
- Setas e bolinhas no estilo de hoje (bolinha com alvo de 24 px, ativa na cor secundária), mas as setas sem `backdrop-filter`: círculo `rgba(0,0,0,.45)` com borda clara, para não haver desfoque nenhum sobre a imagem. Setas escondidas abaixo de 768 px, como hoje.

### D5. Texto alternativo
- Com `alt` preenchido: `<img alt="{{ b.alt }}">`, sem `aria-hidden`. A versão Markdown (`MarkdownDaPagina`) já converte `<img>` em `![alt](url)`.
- Com `alt` vazio: `alt="" aria-hidden="true"`, como as imagens de fundo de hoje; o `MarkdownDaPagina` já descarta `[aria-hidden="true"]`.
- O banner "só a imagem" não tem título visível, então nenhum `h1`/`h2` sai do bloco. Isso não muda o título da página, que vem do SEO da página.

### D6. Pré-carga do LCP
- `AtivosExtension::filtrosDoLcp()`: quando o bloco `banner` encontrado tem `config.imageOnly == '1'`, devolve `['banner_inteiro_mobile' => 768, 'banner_inteiro' => 1600]` com `sizes` `100vw`. `imagemDoLcp()` não muda: já devolve a imagem do primeiro slide ativo.
- `AquecerMiniaturasCommand`: `banner_inteiro` e `banner_inteiro_mobile` entram na lista de `uploads/page_block`. Sem isso, a primeira visita depois do deploy recebe `/media/cache/resolve/…` (302), o que derruba o LCP num servidor de 1 vCPU (comentário do próprio comando).

### D7. Formulário do painel
- O interruptor "Só a imagem" fica no topo de "Slides de Banner", com a frase "Mostra só a imagem dos slides, sem escurecer, sem texto e sem botões".
- Em cada cartão de slide (e no `<template id="banner-tpl">`), título, subtítulo, texto e link do botão ficam num `<div class="campos-de-texto">`. Com a opção ligada, o script põe `inert` e `opacity:.45` nesse `div`, tira o `required` do título e mostra o aviso "Não aparecem no site com 'Só a imagem' ligada; ficam guardados para quando você desligar".
- `inert`, e não `disabled`: campo `disabled` não vai no POST, e o `populateBlock()` gravaria os textos vazios. Com `inert` os valores continuam sendo enviados e gravados, mas o campo não recebe clique, digitação nem foco pelo teclado.
- O campo "Descrição da imagem" (`banners[i][alt]`, com `data-previa-vazio` para a prévia não trocar o vazio pelo rótulo) só aparece com a opção ligada; escondido, continua sendo enviado e gravado.
- Rótulo da imagem: "Imagem de fundo do slide" vira "Imagem do slide", e a dica passa a ser "Recomendado: 1920 px de largura, todas as imagens do mesmo tamanho".
- O script do interruptor escuta `sl-change` e, depois de ajustar os campos, dispara `input` no formulário para a prévia se atualizar (a prévia não escuta `sl-change`). Os slides novos (`addSlide()`) nascem já no estado certo.

### D8. Gravação e prévia
- `populateBlock()`: acrescenta `'alt'` ao array de cada slide. Nada mais muda: `config[imageOnly]` já entra pelo laço de `config` do formulário. Não há validação de título no servidor hoje, e continua não havendo.
- `PreviaDoBloco::slides()`: leva o `alt`. O `config[imageOnly]` já vem do formulário e chega ao template.
- O primeiro slide continua copiado para `title`, `text` e `image` do bloco (lista de blocos do painel). Com título vazio a lista mostra "(sem título)", como já faz.

### D8b. Resumo da página (descrição de SEO)
- Achado na implementação: `TextoDeResumo::daPagina()` (terceiro degrau da cascata da `<meta name="description">`) lê `text` e `title` de cada bloco, e o `populateBlock()` copia para o bloco o título e o subtítulo do 1º slide. Num banner "só a imagem" isso levaria à descrição um texto que não aparece na página e que o admin não consegue editar com a opção ligada.
- Decisão do Jonas (26/11/2026): para banner com `imageOnly == '1'`, o resumo usa só o `alt` do primeiro slide ativo com imagem; sem `alt`, o banner não contribui e vale o bloco seguinte. Os outros tipos de bloco e o banner com texto continuam como estão.
- Alternativas descartadas: ignorar o banner de todo (perde a única frase da página quando a arte é o conteúdo) e deixar como está (texto invisível e bloqueado na descrição).

### D8c. Imagem escolhida na prévia (defeito anterior, corrigido aqui)
- Achado na implementação: a prévia mandava `_previa_arquivos[<nome do campo>]=<quantidade>`. Com nome que já tem colchetes (`banners[0][imageFile]`, `galleryFiles[]`, `testimonials[0][avatarFile]`, `logos[0][logoFile]`, `team[0][imageFile]`), o PHP corta a chave no primeiro `]` e o `PreviaDoBloco` nunca achava o arquivo: a prévia mostrava a imagem antiga. Só o `imageFile` simples funcionava. Isso descumpria o requisito "Arquivo escolhido na prévia" para todos esses blocos.
- Correção (decisão do Jonas, 26/11/2026, para todos os blocos): os nomes vão num campo só, `_previa_arquivos_json` (`{"banners[0][imageFile]": 1}`), e `arquivosEscolhidos()` lê o JSON; JSON inválido vira lista vazia.
- No banner "só a imagem" a área tem a proporção da imagem de exemplo que o servidor põe no lugar do arquivo; a imagem do 1º slide leva `data-proporcao`, e a prévia, ao trocar essa imagem pelo arquivo do computador, refaz o `aspect-ratio` com as medidas dele.

### D9. Isolamento por tenant
Nenhuma consulta nova. O bloco é lido e gravado pelas rotas de `/admin/block`, que já passam pela seção da página ou categoria do tenant do domínio (`TenantAccessVoter`); a prévia segue a regra "Prévia restrita ao site". As imagens continuam em `uploads/page_block`, com nome gerado.

## Risks / Trade-offs

- [Imagens de proporções diferentes no carrossel são recortadas nos slides seguintes] → dica no formulário para usar o mesmo tamanho em todos; o primeiro slide define a área.
- [Arte com texto fica pequena no celular (≈173 px de altura em 390 px para 1884×835)] → aceito pelo Jonas ("imagem inteira"); imagem própria para o celular fica para outra mudança.
- [Mais duas miniaturas por imagem de `uploads/page_block` deixam o `build.sh` mais lento no servidor de 1 vCPU] → medir a contagem antes do deploy (tarefa 6.1); se pesar, restringir o aquecimento às imagens de banners "só a imagem".
- [`inert` em navegador antigo] → todos os navegadores atuais suportam; sem suporte, o campo só não fica bloqueado, e os valores continuam sendo gravados.
- [Imagem cuja medida não pode ser lida (arquivo faltando)] → `thumb_size()` devolve o tamanho do filtro (1:1); a imagem já estaria quebrada de qualquer jeito.

## Migration Plan

1. Deploy (escrita em produção): `git pull` e `./build.sh` (template, CSS no `<style>` do bloco e filtro novo de miniatura), sem migration. O `build.sh` já roda `app:miniaturas:aquecer`.
2. Nenhum banner muda sozinho: a opção nasce desligada em todos.
3. Rollback: `git revert` do commit e `./build.sh`. Banners gravados com `imageOnly` voltam a sair com texto e camada (os textos ficaram guardados); as chaves `imageOnly` e `alt` ficam no JSON sem efeito.
