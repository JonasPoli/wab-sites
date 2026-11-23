# Design

## Context

Segue o molde da sanfona (`2026-11-23-bloco-sanfona`): dados em `page_block.config`, normalização num serviço usado pela gravação e pela prévia, parcial do cetec reaproveitado por moderno, wab e noticias e parcial próprio no atelie (que não pode citar `themes/cetec/`). Logos usam o envio na hora (`POST /admin/block/midia`) e o filtro `logo_bloco` de `imagem-na-celula-da-sanfona`. Referência visual: bloco "Mapa de representantes" de bigdutchman.com.br (mapa cinza com contornos brancos, estado escolhido e cartão arredondado na cor da marca, chamada com ícone de localização antes da escolha).

## Goals / Non-Goals

**Goals:**
- Mapa em SVG próprio, leve e nas cores de qualquer site.
- Funcionar por clique, teclado, seletor (celular) e sem JavaScript.

**Non-Goals:**
- Pinos por cidade ou geocodificação de endereços.
- Mapas de outros países ou de regiões.

## Decisions

### D1. SVG dos estados a partir da malha do IBGE

Fonte: `servicodados.ibge.gov.br/api/v3/malhas/paises/BR?formato=application/vnd.geo+json&qualidade=minima&intrarregiao=UF` (dado público do IBGE, baixado com autorização do Jonas em 23/11/2026). Conversão feita uma vez por script (projeção equirretangular com correção pelo cosseno da latitude média, `viewBox 0 0 1000 1027`, simplificação de Douglas-Peucker com tolerância 1,5 unidade, anéis fechados divididos no ponto mais distante). Resultado: 27 caminhos, 23 KB (cerca de 8 KB com gzip), guardados em `templates/themes/_comum/_mapa_do_brasil.html.twig` com o comentário da fonte. O script fica em `bin/mapa-do-brasil.py` para refazer o desenho, se preciso.

O parcial recebe `id` do bloco, a lista de UFs com registro e as classes do tema, e desenha cada estado como `<a href="#mapa-<id>-<UF>" data-mapa-uf="UF" aria-label="Bahia: 2 representantes"><path …/></a>` (link no SVG: foco pelo Tab, Enter funciona, e sem JavaScript o link leva ao cartão).

*Alternativa descartada:* copiar o SVG de outro site ou de banco de mapas com licença incerta.

### D2. Dados

`config` do tipo `representatives`:

| Chave | Valor |
|---|---|
| `emptyMessage` | texto simples (estados sem registro) |
| `reps[]` | `{uf, image, html}` |

Pré-título, título e chamada usam `preTitle`, `title` e `text` do bloco. A lista chega em `config[repsJson]` (um campo só, como as tabelas da sanfona: não esbarra no `max_input_vars` e a prévia não troca campos vazios pelo rótulo). `App\Service\MapaDeRepresentantes::normalizar()`: UF entre as 27 siglas, `image` por `MidiaDoBloco::nomeValido()`, `html` pelo `ArticleBodySanitizer` e depois `h2` → `h3` (o sanitizador já troca `h1` por `h2`); descarta registro sem UF válida ou sem texto e sem imagem; até 1.000 registros; ordem do admin. `estados()` devolve sigla → nome; `exemplos()` três registros (AC, BA, RS).

`ArticleBodySanitizer` passa a aceitar o esquema `tel:` nos links (vale também para as notícias, onde também é útil).

### D3. Comportamento (script comum `themes/_comum/_mapa_script.html.twig`)

Marcação: raiz `[data-mapa]`; estados `a[data-mapa-uf]`; `select[data-mapa-seletor]`; cartões `[data-mapa-painel="UF"]` (um por UF com registro), `[data-mapa-painel="vazio"]` (mensagem, com `[data-mapa-vazio-nome]` para o nome do estado) e `[data-mapa-inicio]` (chamada).

O HTML sai com todos os cartões visíveis (sem JavaScript e para o Markdown dos agentes); o script, ao carregar, esconde os cartões e mostra a chamada. Clique, Enter ou troca no seletor: `preventDefault`, mostra o cartão do estado (ou o "vazio" com o nome do estado), marca o estado com a classe `is-ativo` e `aria-current="true"`, sincroniza o seletor e atualiza o endereço com `history.replaceState` (`#mapa-<id>-<UF>`). Ao carregar com esse `#`, abre o estado. Função `window.wabMapa` criada uma vez e aplicada a cada `[data-mapa]`.

### D4. Desenho no cetec (`themes/cetec/blocks/_representatives.html.twig`)

- Cabeçalho padrão do cetec (pré-título, `h2`, traço).
- Grade `lg:grid-cols-2`, mapa à esquerda (largura máxima 560 px), coluna da direita com o seletor (visível em todas as larguras, discreto no desktop) e os cartões.
- Chamada: caixa branca com sombra, ícone de localização na cor primária e o texto em negrito (como na referência).
- Cartão do estado: fundo `--color-primary`, texto `--color-on-primary`, cantos de 2rem; nome do estado em destaque; cada registro em grade `[auto 1fr]` com o logo num quadro branco arredondado; o HTML do registro herda a cor do cartão (links sublinhados).
- Componente `.wab-mapa` no `app.css` (cores dos estados por variáveis: neutro `#a3a3a3`, sem registro `#d4d4d4`, contorno `#fff`; no `.dark`, `.wab-section-dark` e `.mp-section-dark`: `#6b7280`, `#374151`, contorno `#111827`); limite de +2,5 KB.

### D5. Atelie (`themes/atelie/blocks/_representatives.html.twig`)

Mesmo SVG e script (de `_comum`), classes `at-mapa*` em `_styles.html.twig`: estados em tons da paleta do tema (`--at-card`, `--at-line`), escolhido na cor primária, cartão claro com linha fina e cantos retos, cabeçalho pelo `_head.html.twig` do atelie.

### D6. Painel (`admin/block/_form_representatives.html.twig`)

- Pré-título, título, chamada (textarea), mensagem para estados sem representante.
- Barra: filtro por estado (com a contagem de cada um), total, "Novo representante" (vem com o estado do filtro).
- Cada registro é um cartão recolhido (estado, logo, primeira linha do texto) com subir, descer, editar e remover (dois cliques); "Editar" abre estado, logo (envio na hora, trocar e tirar) e o Quill 2 com barra `[{header: 3}], bold, italic, underline, link, listas, clean`, criado só quando o cartão é aberto.
- Estado em memória → `config[repsJson]` a cada mudança, com evento `input` para a prévia. `PreviaDoBloco`: `representatives` passa por `normalizar()`; lista vazia usa `exemplos()`.

### D7. Outros pontos

- `MidiaDoBloco::arquivosDoConfig()` inclui `reps[].image` (exportação e importação).
- Menu do Modo Landing Page: `representatives` → "Representantes" (cetec nas 2 cadeias, moderno, wab, mapa do atelie).
- `BlocosDeExemplo`: um mapa de exemplo (5 registros em 3 estados) na página `todos-os-blocos`.

Isolamento por tenant: os parciais só leem o `block` entregue pela página do tenant; gravação e prévia passam por `blockOr404`/`sectionOr404`; o envio de imagem não lê conteúdo de tenant.

## Risks / Trade-offs

- [Estados pequenos (DF, SE, AL) difíceis de tocar] → seletor de estados sempre presente.
- [23 KB de SVG em cada página com o mapa] → aceitável (8 KB com gzip), só nas páginas que têm o bloco.
- [CSS cresce em todos os sites] → medir; limite +2,5 KB.

## Migration Plan

Sem migration. Deploy junto com `imagem-na-celula-da-sanfona`: backup, `git push`, `git pull`, `./build.sh`, HTTP 200 nos domínios. Rollback: commit anterior + `./build.sh` (tipo desconhecido não desenha nada).
