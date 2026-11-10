# Proposta: busca-no-portal

## Why

O Jonas pediu em 10/11/2026 uma busca para os sites com o tema `noticias` (hoje, o portal da AJoPE em https://ajope.wab.com.br). O portal só tem a lista por data, as editorias e a página do autor. Não há como achar uma notícia por palavra.

## What Changes

- **Busca no cabeçalho:** um botão de lupa no canto direito do cabeçalho do tema `noticias`, ao lado do botão de aparência, abre um campo de busca logo abaixo da barra, em todas as larguras de tela. O menu lateral também ganha um campo de busca.
- **Página `/busca?q=termo`:**
  - mostra as notícias visíveis do site que têm todas as palavras do termo no título, no chapéu, na linha fina ou no texto, sem diferenciar maiúsculas nem acentos;
  - as que têm alguma palavra no título vêm primeiro, depois as mais recentes;
  - são 20 por página, com `?pagina=N`, no mesmo item de lista da home;
  - no topo, "N resultados para "termo"";
  - sem resultado, "Nenhuma notícia encontrada para "termo"." e o campo para tentar de novo;
  - também aparecem as páginas institucionais do site cujo título tem os termos, num quadro "Páginas".
- **Regras do termo:**
  - palavras com menos de 2 letras são ignoradas;
  - vale no máximo 5 palavras e 100 caracteres;
  - termo vazio mostra só o campo.
- A página de busca sai com `noindex` e fica fora do sitemap.
- Em sites de outro tema, `/busca` responde 404.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `noticias`: requisitos novos sobre a busca do portal (campo, página de resultados, regras do termo e indexação).

## Impact

- **Código:**
  - rota `pub_search` no `WabPublicController`;
  - consultas novas no `ArticleRepository` e no `PageRepository`, que passam pelo filtro de tenant da requisição pública.
- **Templates do tema `noticias`:**
  - `search.html.twig` (novo);
  - `_header.html.twig` (lupa e campo no menu lateral);
  - `base.html.twig` e `_news_styles.html.twig` (estilos).

  Os temas cetec, moderno e wab não mudam.
- **Sem migration:** a busca usa `LIKE` nas colunas que já existem. O banco é pequeno: o portal tem dezenas de notícias.
