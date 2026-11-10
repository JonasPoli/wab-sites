# Design: tipografia-g1-no-tema-noticias

## Context

Motivação em `proposal.md`; requisitos em `specs/navegacao-e-aparencia/spec.md`.

**Medidas do g1** (Playwright com Chrome, 10/11/2026, estilos calculados na notícia de referência e na home): a fonte é a `glbOpenSans` (Open Sans própria do g1). Cada linha traz tamanho/entrelinha, peso e espaço entre letras; no celular (390 px), só o que muda.

| Elemento | 1280 px | 390 px |
|---|---|---|
| título da notícia (h1) | 40/48 px, 700, −1,8 px | 28/32 px, −0,84 px |
| linha fina | 15/22 px, 400, −0,15 px | igual |
| autor | 14/20 px, 600 | igual |
| data | 12/16 px | igual |
| texto | 18/28 px, −0,27 px; 32 px entre parágrafos | 28 px entre parágrafos |
| intertítulo | 24/28 px, 700, −0,72 px | igual |
| item de lista | 20/28 px, −0,6 px | 18 px |
| citação | 20/28 px, 700, sem itálico, sem borda | 18/26 px |
| link no texto | 700, sem sublinhado | igual |
| legenda | 12 px | igual |
| editoria no cabeçalho | 32 px, 300, caixa alta | 24 px |
| coluna | 648 px | largura da tela |
| chapéu (lista) | 16 px, 600 | igual |
| título (lista) | 24/26,4 px, 700, −0,84 px | 20/22 px |
| linha fina (lista) | 16/24 px | igual |
| relacionados | 14 px, 400 | igual |
| data (lista) | 12/18 px | igual |
| títulos do "Mais lidas" | 16 px, 700 | igual |
| "Mostrar mais" | 14 px, 700 | igual |

Outros espaços: 16 px entre o título e a linha fina (12 px no celular) e 24 px entre a linha fina e o autor (20 px no celular).

**Tema hoje:**
- `tenant_css_vars()` gera `hN { font-family …; font-weight …; font-size … !important }` para os cinco títulos, a partir de `fontSettings`. Isso vale para toda tag `hN` da página, e o `!important` passa por cima das regras do tema.
- No tema `noticias`, as tags são usadas assim:
  - h1: título da notícia e das páginas;
  - h2: título dos itens da lista, intertítulo do texto e cabeçalho dos quadros (`nt-card__title`);
  - h3: subtítulo do texto (vem do editor).
- O ajope tem hoje H1 a H5 em Outfit 400, no tamanho do tema. O `fonte-open-sans` põe a Open Sans na lista.

## Goals / Non-Goals

**Goals:**
- Tamanho, peso, entrelinha, espaço entre letras e espaçamentos do g1 no tema `noticias`, com as cores do tema.
- Os tamanhos e os pesos da edição do tenant continuam mandando nos títulos, como já fazem em todos os temas.
- O celular fica proporcional ao g1 sem um segundo campo de tamanho na edição.

**Non-Goals:**
- Os temas `cetec`, `moderno` e `wab`.
- Mudar o `tenant_css_vars()`.
- Hospedar a fonte, porque a Open Sans continua vindo do Google Fonts.
- Copiar a disposição do g1: blocos, banners, ícones e cores ficam como estão.
- Os blocos do cetec nas páginas institucionais, que só recebem a família, o peso e o tamanho dos títulos pelas regras globais.

## Decisions

### 1. Tamanhos dos títulos em variáveis do próprio tema
O `base.html.twig` do `noticias` lê `currentTenant.fontSettings.hN.size` de H1, H2 e H3 e declara três variáveis: `--nt-h1-size`, `--nt-h2-size` e `--nt-h3-size`.
- Só aceita valores que casam com `^\d+(\.\d+)?(px|rem|em)$`.
- Sem valor gravado, usa os padrões do g1: 2.5rem, 1.5rem e 1.25rem.
- Com as variáveis, o tema calcula o celular (`calc(var(--nt-h1-size) * .7)`) e aplica o tamanho padrão quando o tenant está em "Padrão do Tema".

**Alternativa descartada:** pôr as variáveis no `tenant_css_vars()`. Isso mexeria no requisito "Variáveis de cor e fontes", que o `fonte-open-sans` também altera, e os dois deltas iam conflitar no arquivamento.

### 2. Quem segue a edição do tenant e quem é rótulo do tema
- **Seguem o tenant:**
  - o h1 da página (notícia, editoria, autor, `/noticias`, páginas e cadastro) usa o tamanho e o peso do H1;
  - o h2 dos itens da lista e do texto usa os do H2;
  - o h3 do texto usa os do H3.
- **Rótulos do tema:** o `.nt-card__title` (h2 dos quadros) recebe `font-size: 14px !important; font-weight: 600 !important`. A regra tem seletor de classe e vem depois do `tenant_css_vars()` no `<head>`, então ganha da regra `h2 { … !important }`.
- **O que o tema define em tudo:** entrelinha, espaço entre letras e margens. O tenant não mexe nessas propriedades, então não é preciso `!important`.

### 3. Celular
Dentro de `@media (max-width: 767px)`, o tema define, com `!important` para ganhar da regra global do tenant:
- `h1 { font-size: calc(var(--nt-h1-size) * .7) }`, com entrelinha 1,143 (28/32) e espaço entre letras de −0,03 em;
- `.nt-item__title { font-size: calc(var(--nt-h2-size) * .8333) }`;
- `.nt-body li` e `.nt-body blockquote` com 18 px.

O intertítulo continua com o tamanho do H2, como no g1. O fator é aplicado ao tamanho gravado no tenant: quem gravar um H1 de 3rem terá 2.1rem no celular.

### 4. Medidas fixas do tema (fora dos títulos)
Ficam em `_news_styles.html.twig` (e no `base` para o cabeçalho), em px. O espaço entre letras é guardado em em, para acompanhar o tamanho.

| Elemento | Hoje | Novo |
|---|---|---|
| `.nt-kicker` | 13 px | 16/17,6 px, 600, −0,02 em |
| `.nt-item__summary` | 16/1,5 | 16/24 px, −0,02 em |
| `.nt-related li` | 13 px | 14 px, entrelinha 1,3, −0,015 em |
| `.nt-meta` | 12 px | 12/18 px, −0,01 em |
| `.nt-featured a` e `.nt-rank a` | 14 px | 16/17,6 px, 700, −0,02 em |
| `.nt-more` e `.nt-pager a` | 700 | 14 px, 700 |
| `.nt-article__kicker` | 14 px | 16 px, 600 |
| `.nt-article__summary` | 18 px | 15/22 px, −0,01 em, 16 px acima |
| `.nt-byline` | 13 px, 700 | 14/20 px, 600, 24 px acima |
| `.nt-article__date` | 12 px | 12/16 px |
| legenda | 12 px | 12 px, entrelinha 1,1 |
| `.nt-body` | 18 px, entrelinha 1,65 (17 px no celular) | 18/28 px, −0,015 em, em todas as larguras |
| `.nt-body p` | — | 32 px de margem embaixo (28 px no celular) |
| `.nt-body a` | — | peso 700, sem sublinhado |
| `.nt-body li` | — | 20/28 px, −0,03 em, 16 px entre itens |
| `.nt-body blockquote` | itálico | 20/28 px, 700, sem itálico, com a borda colorida de hoje (a cor é do tema) |
| `.nt-article` | 712 px | 648 px |
| `.nt-header__section` | 22 px (16 px no celular) | 32 px, 300 (24 px no celular) |

A primeira notícia da lista perde o tamanho de 28 px e fica com o do H2, como no g1.

### 5. Gravação do tenant 10
A gravação é feita pela tela de edição do tenant no `/superadmin` de produção, com H1 a H5 em Open Sans e 700 de peso e os tamanhos 2.5rem, 1.5rem, 1.25rem, 1rem e 0.875rem. Todos esses tamanhos já existem na lista do formulário. Os valores de antes ficaram anotados no `fonte-open-sans`, tarefa 1.1: H1 a H5 em Outfit 400, no tamanho do tema.

### 6. Isolamento
O change não lê nem grava conteúdo. O tema lê só o `fontSettings` do `currentTenant`, que o `TenantSubscriber` resolveu pelo domínio. A gravação do tenant 10 é feita pelo `/superadmin`, que trata um tenant por id.

## Risks / Trade-offs

- **[Tags que o tema não controla seguem a edição do tenant]** Os h2 e h3 dos blocos do cetec, nas páginas institucionais, passam a 24 e 20 px quando o H2 e o H3 estão gravados. É o comportamento que esses temas já têm, e a conferência inclui a página "Todos os blocos".
- **[Tamanho estranho gravado em outro portal]** A regex da decisão 1 descarta o valor e usa o padrão; a regra global do tenant aplica o tamanho de qualquer jeito.
- **[Open Sans do Google e `glbOpenSans` do g1]** A diferença é pequena (a do g1 é a mesma família, com outro hinting) e é aceita.
- **[Linha fina menor que a de hoje]** A linha fina cai de 18 para 15 px, como no g1. Ela fica abaixo de 16 px, mas o contraste do tema continua acima de 4,5:1.

## Migration Plan

1. Local: implementar e conferir em `noticias.localhost`:
   - medir com Playwright os mesmos elementos do g1, a 1280 e a 390 px, com o tenant em Open Sans e nos tamanhos do g1;
   - conferir os dois modos e "Todos os blocos".
2. Produção (escrita, com aprovação do Jonas): deploy junto com o `fonte-open-sans`, com `git pull` e `./build.sh` (mudou template).
3. Produção (escrita pela tela): gravar a tipografia do tenant 10 no `/superadmin`.
4. Conferência (leitura): medir o ajope com o mesmo script e comparar com o g1.
5. Reversão:
   - pelo código: `git revert` e `./build.sh`;
   - pela tela: voltar o tenant 10 para Outfit 400 e "Padrão do Tema".
