# Proposta: tipografia-g1-no-tema-noticias

## Why

O Jonas quer que o portal da AJoPE (https://ajope.wab.com.br, tenant 10, tema `noticias`) tenha as fontes o mais parecidas possível com as do g1 em tamanho, peso e formato, mantendo as cores do tema. A referência é a notícia https://g1.globo.com/saude/noticia/2026/11/10/caso-fhc-por-que-o-alzheimer-avancado-pode-deixar-pacientes-acamados-e-totalmente-dependentes.ghtml, e a home do g1 serve para a lista.

Hoje o ajope sai com os títulos em Outfit 400, que é o que a edição do tenant grava por padrão. O tema também usa medidas próprias, diferentes das do g1:

| | g1 | tema hoje |
|---|---|---|
| título da notícia | 40 px, peso 700 | 38 px, peso 800 |
| linha fina | 15 px | 18 px |
| chapéu | 16 px | 13 px |
| citação | negrito, sem itálico | itálico |

A Open Sans, que é a fonte do g1, entra na lista da edição do tenant pelo change `fonte-open-sans`.

## What Changes

- No tema `noticias`, as fontes passam a seguir as medidas do g1, medidas em 10/11/2026 a 1280 px e a 390 px:
  - na notícia: título, linha fina, autor, data, legenda, texto, intertítulos, listas, citações e links;
  - na lista (home, editoria, autor e `/noticias`): chapéu, título, linha fina, links relacionados, data, quadros "Leia também" e "Mais lidas" e o botão "Mais notícias";
  - nome da editoria no cabeçalho.
- O que segue o g1:
  - tamanho, peso, entrelinha e espaço entre letras;
  - espaço entre parágrafos e largura da coluna da notícia (648 px);
  - links do texto em negrito sem sublinhado;
  - citação em negrito, sem itálico.
- As cores continuam as do tema e do site, nos modos claro e escuro.
- Os tamanhos de H1, H2 e H3 da edição do tenant valem no tema `noticias` a partir de 768 px.
  - Abaixo disso, o tema reduz o título da página e o título da lista na proporção do g1: 40 para 28 e 24 para 20.
  - Sem tamanho gravado ("Padrão do Tema"), o tema usa os do g1: H1 40 px, H2 24 px e H3 20 px.
- Os cabeçalhos dos quadros laterais ("Leia também" e "Mais lidas") são rótulos do tema e não seguem o tamanho nem o peso do H2.
- No tenant 10, os títulos H1 a H5 passam a usar a Open Sans, com os tamanhos e os pesos do g1. A gravação é feita pela edição do tenant no `/superadmin`.
  - H1: 2.5rem, 700;
  - H2: 1.5rem, 700;
  - H3: 1.25rem, 700;
  - H4: 1rem, 700;
  - H5: 0.875rem, 700.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `navegacao-e-aparencia`: novos requisitos sobre a tipografia do tema `noticias` (as medidas do g1, os títulos que seguem a edição do tenant e a escala no celular).

## Impact

- **Templates do tema `noticias`:** `base.html.twig` (tokens de tamanho a partir do tenant, cabeçalho e regras para o celular) e `_news_styles.html.twig`. Os temas `cetec`, `moderno` e `wab` não mudam.
- **Sem mudança em PHP, banco ou migration.** O `tenant_css_vars()` continua igual a como o `fonte-open-sans` o deixa.
- **Sites afetados:** só os de tema `noticias`; em produção, hoje, só o ajope.
- **Produção:**
  - deploy com `git pull` e `./build.sh`;
  - gravação da tipografia do tenant 10 pelo `/superadmin`, que é escrita no banco feita pela tela.
- **Dependência:** o `fonte-open-sans` precisa estar no ar antes da gravação do tenant 10.
