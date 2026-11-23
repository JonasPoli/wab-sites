# Design

## Context

Como um tipo de bloco existe hoje (o último a entrar foi `blog_posts`): caso em `BlockType`, cartão na lista `types` de `admin/block/new.html.twig`, formulário `admin/block/_form_<tipo>.html.twig`, gravação em `AdminContentController::populateBlock()` (tudo que vem em `config[...]` vai para o JSON `page_block.config`), prévia em `PreviaDoBloco::montar()` + `admin/block/_previa.html.twig`, desenho em `themes/cetec/blocks/_<tipo>.html.twig` e `themes/atelie/blocks/_<tipo>.html.twig`, despachantes `_block.html.twig` de cetec, moderno, wab e atelie (o noticias inclui o do cetec) e o rótulo do menu do Modo Landing Page nos `_header.html.twig`. O `tests/Entity/BlockTypeCoberturaTest.php` confere formulário, parciais e despachantes de todo `BlockType::cases()`.

Restrições que moldam a solução:

- **`max_input_vars`** (1000 por padrão no PHP): uma tabela como a da referência (27 estados, ~7 colunas, centenas de linhas) passa de mil campos. Campos além do limite são descartados em silêncio.
- **Prévia**: troca todo `input`/`textarea` de texto **com `name`** vazio pelo rótulo do campo; ignora os que estão dentro de `<template>`. Célula vazia não pode virar "Coluna 3".
- **Versão Markdown** (`MarkdownDaPagina`) remove `[hidden]`, `[aria-hidden=true]`, `template`, `button`, `input`, `textarea`: o conteúdo fechado da sanfona não pode estar escondido por esses meios.
- O editor de texto rico do painel é o Quill 2 (`admin/_rich_editor.html.twig`), feito para um campo fixo por página. O HTML das notícias já é limpo pelo `ArticleBodySanitizer` (HTMLPurifier).
- Todos os temas carregam `styles/app.css` (Tailwind). cetec, moderno, wab e noticias marcam o modo escuro com a classe `dark` no `<html>`; seções de fundo escuro levam `wab-section-dark`/`mp-section-dark`. O atelie é só claro, com CSS próprio (`at-*`) em `_styles.html.twig`.

## Goals / Non-Goals

**Goals:**
- Um tipo de bloco que comporte a página de revendas da referência: muitos itens, tabelas largas e longas, células com várias linhas.
- Edição prática: colar uma planilha ou uma tabela de site em vez de digitar célula por célula.
- Sem coluna nova no banco e sem mudar o HTML das páginas existentes.

**Non-Goals:**
- Busca/filtro dentro da sanfona, imagens nas células, mesclar células, largura de coluna configurável.
- Tabela dentro do editor de texto rico (o Quill continua sem tabela; tabela é o tipo próprio do item).
- Item que mistura texto e tabela (o admin usa dois itens ou o texto do bloco).

## Decisions

### D1. Dados: tudo em `config`, tabela como JSON num campo só por item

Chaves de `page_block.config` do tipo `accordion`:

| Chave | Valor |
|---|---|
| `firstOpen` | `'1'` ou ausente |
| `multiple` | `'1'` ou ausente |
| `panels[]` | `{title, kind: 'text'\|'table', html, table: {header: bool, rows: string[][]}}` |

`html` e `table` ficam gravados juntos, qualquer que seja o `kind`: trocar o tipo do item e voltar não perde nada (o desenho só usa o do `kind`).

No formulário, título (`config[panels][i][title]`), tipo (`[kind]`) e HTML (`[html]`, textarea oculto do Quill) têm `name` e passam pela prévia como os outros campos. As **células não têm `name`**: o JavaScript da tabela mantém um `input type=hidden name="config[panels][i][table]"` com o JSON `{header, rows}` atualizado a cada digitação. Assim uma tabela de qualquer tamanho custa um campo só (resolve o `max_input_vars`), e a prévia nunca troca célula vazia por rótulo (ela só olha campos com `name` e ignora `type=hidden`).

*Alternativa descartada:* um campo por célula (`[rows][r][c]`). Simples, mas a página de referência inteira não caberia nos 1000 campos e o PHP cortaria o fim da tabela sem aviso.

*Alternativa descartada:* entidade filha (`PageBlockAccordionItem`). Mais migration, duplicação, exportação e importação para um conteúdo que não tem arquivo nem consulta própria.

### D2. Normalização num serviço só (`App\Service\SanfonaDoBloco`)

`normalizar(array $cfg): array`, usado pelo `populateBlock()` (gravação) e pelo `PreviaDoBloco` (prévia), para que a prévia mostre exatamente o que será gravado:

- título: `trim`, até 200 caracteres;
- `kind`: `table` ou `text` (qualquer outro valor vira `text`);
- `html`: `ArticleBodySanitizer::sanitize()` (mesma lista de tags das notícias; `h1` vira `h2`);
- `table`: JSON decodificado (inválido → tabela vazia); cada célula `trim` e até 2.000 caracteres; linhas totalmente vazias saem; no máximo 12 colunas e 500 linhas; linhas completadas com células vazias até a largura da maior;
- item sem título **e** sem conteúdo sai; item sem título mas com conteúdo fica gravado (o desenho o ignora), para o admin não perder o que digitou;
- `firstOpen`/`multiple`: `'1'` ou removidos.

Na prévia, sem nenhum item, o serviço devolve dois itens de exemplo (um texto, uma tabela 3×3 com cabeçalho), como as outras listas fazem.

### D3. Abrir e fechar com `<details>`/`<summary>`

Cada item é `<details>` com `<summary>` (título + seta). Funciona sem JavaScript, é acessível por teclado e leitor de tela, e o "localizar na página" do Chrome abre o item onde achou o texto. "Um de cada vez" usa o atributo nativo `name` igual em todos os `<details>` do bloco (`sanfona-<id do bloco>`; na prévia, sem id, um valor aleatório); com "vários abertos" o atributo não é posto. `firstOpen` põe `open` no primeiro item visível.

O conteúdo fechado continua no DOM sem `hidden`, então a versão Markdown o recebe. Nenhum JavaScript no site.

*Alternativa descartada:* botões com `aria-expanded` + JavaScript (padrão do `_gallery`). Mais código, não funciona sem JavaScript, e o conteúdo escondido com `hidden` sumiria do Markdown.

### D4. Células: texto puro com quebra de linha e links

Filtro Twig `celula_da_sanfona` (novo `App\Twig\SanfonaExtension`, com teste unitário): escapa o HTML, transforma e-mails em `mailto:` e `http(s)://…`/`www.…` em links (`target="_blank" rel="noopener"`), e quebras de linha em `<br>`. A célula nunca é HTML do admin, então não precisa de sanitizador.

### D5. Tabela responsiva

A tabela fica dentro de um contêiner com `overflow-x:auto`. Com cabeçalho, cada `<td>` recebe `data-rotulo="<cabeçalho da coluna>"`; abaixo de 640 px, CSS esconde o `<thead>`, faz cada `<tr>` virar um cartão e mostra `attr(data-rotulo)` antes do valor (célula vazia some do cartão). Sem cabeçalho, só a rolagem lateral.

### D6. Desenho por tema

- **cetec**: parcial `themes/cetec/blocks/_accordion.html.twig` com o cabeçalho de bloco padrão (`_pre_title`, `h2`, traço) e a lista com a classe de componente `.wab-sanfona`, definida em `assets/styles/app.css` (`@layer components`): bordas e fundo dos itens, seta que gira, texto rico (listas com marcador, links sublinhados na `--color-primary-text`, títulos), tabela listrada e o modo cartão do celular. As cores vêm das variáveis do `tenant_css_vars()` e há variantes para `.dark`, `.wab-section-dark` e `.mp-section-dark`. CSS escrito à mão (e não utilitários Tailwind) porque o texto rico e a tabela vêm de HTML gerado, sem classes.
- **moderno** e **wab**: `'accordion'` entra na lista literal de tipos que incluem o parcial do cetec.
- **noticias**: nenhum arquivo muda (inclui o despachante do cetec); o modo escuro dele também usa a classe `dark`.
- **atelie**: parcial próprio `themes/atelie/blocks/_accordion.html.twig` (sem depender do cetec, como o teste exige), usando `_head.html.twig` e classes `at-accordion*` novas em `themes/atelie/_styles.html.twig`: linhas finas `--at-line`, títulos em Manrope, cantos retos, sinal "+" que vira "−", texto com `.at-rich`. Fica na coluna central (não entra em `larguraTotal`).

Isolamento por tenant: os parciais só leem o `block` que a página ou categoria do tenant entregou; nenhum faz consulta. No painel, gravação e prévia passam pelo `blockOr404`/`sectionOr404`, que já exigem que a seção pertença a uma página ou categoria do tenant do domínio.

### D7. Formulário do painel

`admin/block/_form_accordion.html.twig` com JavaScript próprio no fim do arquivo:

- Cabeçalho (`_campos.cabecalho`, título opcional), as duas caixas de opção e a lista de itens. Cada item é um `.card` cujo primeiro `div` filho tem só o texto "Item N" (a prévia usa isso para dizer "Item 2 — Título do item"); botões subir, descer e remover ficam em outro elemento.
- "Adicionar item" clona um `<template>`; após acrescentar, remover ou mover, uma função renumera os `name` (`config[panels][i][…]`) e os rótulos.
- Tipo do item (Texto/Tabela) alterna qual dos dois editores aparece; os dois continuam no formulário.
- Texto: o Quill 2 é carregado uma vez (mesma versão e CDN do `_rich_editor`) e cada item cria o seu editor ao aparecer; o contêiner tem id `quill-<id do textarea>` para a prévia achar o HTML, e o textarea oculto é atualizado a cada mudança.
- Tabela: grade de `textarea` sem `name` (uma por célula, crescendo com o texto), botões "+ linha", "+ coluna", remover linha (na ponta da linha) e coluna (no topo), caixa "primeira linha é cabeçalho", e uma área "Colar de planilha": no `paste`, se a área de transferência tiver `text/html` com `<table>`, lê as células pelo `DOMParser` (texto com quebras dos `<br>`/parágrafos); senão, lê o `text/plain` como TSV (aspas do Excel em células com quebra de linha). O conteúdo colado substitui a tabela do item, com confirmação em texto na própria área (sem `confirm()`).
- A cada mudança, o JSON da tabela vai para o campo oculto e um evento `input` é disparado no formulário, para a prévia se atualizar.

`_previa.html.twig` muda uma linha: campo com `data-previa-vazio` é ignorado na troca por rótulo (salvaguarda; as células já não têm `name`).

### D8. Markdown com tabelas

`MarkdownDaPagina` passa a registrar o `TableConverter` do `league/html-to-markdown` (já instalado). Hoje nenhuma página pública tem `<table>` (o Quill não cria e o sanitizador das notícias não aceita), então só as sanfonas mudam de saída. As células com `<br>` saem numa linha só, separadas por espaço (o conversor de tabela não aceita quebra de linha dentro da célula).

### D9. Menu do Modo Landing Page

`accordion` → "Informações" nas cadeias de rótulo dos `_header.html.twig` de cetec (desktop e celular), moderno, wab e no mapa `rotulos` do atelie.

## Risks / Trade-offs

- [`name` em `<details>` não existe em navegador antigo] → nele vários itens ficam abertos ao mesmo tempo; nada quebra.
- [`app.css` cresce para todos os sites] → medir antes e depois; aceitar até +3 KB minificado.
- [JSON da tabela grande demais para o `post_max_size`] → 500 linhas × 12 colunas × 2.000 caracteres é o pior caso teórico (12 MB); o uso real (centenas de linhas curtas) fica em dezenas de KB. Conferir o `post_max_size` do pool em produção (leitura).
- [Admin cola uma tabela por cima da que já tinha] → a área de colar mostra quantas linhas e colunas entraram e há "Desfazer colagem" até a próxima mudança.
- [Quill carregado de CDN no painel] → o mesmo que o editor das notícias já faz; sem CDN, o textarea continua lá com o HTML.
- [Rollback do código com sanfonas gravadas] → tipo desconhecido não desenha nada (comportamento atual do despachante); o `config` fica intacto.

## Migration Plan

1. Sem migration.
2. Deploy (escrita em produção; o pedido do Jonas autoriza "publicar"): backup do banco; `git push`; no servidor `git pull` e `./build.sh` (template, CSS e JS mudaram); HTTP 200 nos 8 domínios de cliente e em ajope.wab.com.br.
3. Rollback: `git checkout` do commit anterior + `./build.sh`.
