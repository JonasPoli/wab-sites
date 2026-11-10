# Design: busca-no-portal

## Context

- **Lista atual:** `/noticias` usa `ArticleRepository::visibleQueryBuilder()`, `currentPage()` (404 fora do intervalo) e `_article_item.html.twig`.
- **Isolamento:** na requisição pública, o filtro de tenant do Doctrine está ligado. Toda consulta com o `ArticleRepository` e o `PageRepository` já sai filtrada pelo site do domínio.
- **Banco:** colunas `utf8mb4` com collation que não diferencia acentos (`utf8mb4_unicode_ci` ou `0900_ai_ci`), então o `LIKE` já ignora maiúsculas e acentos. A conferência é feita na tarefa.
- **Cabeçalho do tema:** o canto direito tem o botão de aparência (`nt-mode-btn`).

## Decisions

1. **Rota `GET /busca` (`pub_search`) no `WabPublicController`.**
   - Fora do tema `noticias`, responde 404, como o `renderThemeOr404`.
   - O termo é `q`, cortado em 100 caracteres, e as palavras saem de `preg_split('/\s+/u')`.
   - Ficam até 5 palavras, ignorando as com menos de 2 caracteres (`mb_strlen`).
2. **`ArticleRepository::searchVisible(array $words, int $page)` e `countSearchVisible(array $words)`.**
   - Partem do `visibleQueryBuilder()`.
   - Cada palavra vira um `(a.title LIKE :wN OR a.kicker LIKE :wN OR a.summary LIKE :wN OR a.body LIKE :wN)`, com `%palavra%` e `%`, `_` e `\` escapados. As palavras se juntam por `AND`.
   - A ordem é `CASE WHEN (algum título casa) THEN 0 ELSE 1 END`, depois `publishedAt DESC`, depois `id DESC`.
   - Cada página tem 20 itens (`PER_PAGE`).
   - O texto (`body`) é HTML, então uma palavra pode casar com marcação (por exemplo, "strong"). Isso é aceito, porque o ganho de uma coluna de texto limpo não compensa uma migration agora.
   - **Alternativa descartada:** `FULLTEXT` do MySQL, que exige migration, tem tamanho mínimo de token e lista de palavras ignoradas, e não ajuda com dezenas de notícias.
3. **`PageRepository::searchByTitle(array $words, int $limit = 5)`:** páginas do site com todas as palavras no título, em ordem alfabética.
4. **Template `themes/noticias/search.html.twig`:**
   - mesma estrutura de `articles.html.twig` (lista e barra lateral);
   - no topo, o formulário de busca com o termo, e o título "N resultados para "termo"" ou a mensagem sem resultado;
   - o quadro "Páginas" com links `/p/{slug}`, quando houver;
   - paginação por `_pagination` com `params: {q: termo}`;
   - bloco `robots` com `noindex, follow`.
5. **Cabeçalho e menu lateral.**
   - Botão `nt-search-btn` (ícone `bi-search`, `aria-label="Buscar"`, `aria-expanded`, `aria-controls="nt-search"`) em `.nt-header__right`, antes do botão de aparência.
   - Ele mostra e esconde o `<form id="nt-search" role="search" action="/busca">`, que fica logo abaixo da barra (fundo do cartão e largura do container) e recebe o foco ao abrir. `Esc` fecha.
   - O menu lateral ganha um formulário igual no topo.
   - Tudo isso fica no `_header.html.twig` do tema `noticias`, e os outros temas não mudam.
6. **Sitemap.** O sitemap lista páginas e notícias, e não ganha `/busca`.

## Risks / Trade-offs

- **[`LIKE '%x%'` sem índice]** Varre a tabela de notícias do site. Com centenas de notícias, isso é instantâneo; se o portal crescer muito, troca-se por `FULLTEXT`.
- **[Busca por marcação HTML]** Ver a decisão 2.

## Migration Plan

Local: rota, consultas, templates e conferência em `noticias.localhost`. Produção: deploy junto com os outros changes pendentes (`git pull` e `./build.sh`), sem migration.
