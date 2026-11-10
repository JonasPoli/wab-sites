# Proposta: titulo-da-secao-opcional

## Why

Na página https://ajope.wab.com.br/p/quem-somos, a seção mostra o título em duas cores ("Quem | Somos"). O Jonas pediu em 10/11/2026 uma caixa de marcar na edição da seção (por exemplo, https://ajope.wab.com.br/admin/section/77/edit) para mostrar ou esconder esse título na página.

Hoje só dá para esconder os títulos de todas as seções do site de uma vez (`showSectionTitles` no `/superadmin`).

## What Changes

- **Caixa nova na criação e na edição da seção, no `/admin`:** "Exibir o título da seção na página", marcada por padrão.
- **Desmarcada:** a página e a categoria deixam de mostrar o título em duas cores daquela seção, nos quatro temas (cetec, moderno, wab e noticias).
  - O título continua gravado.
  - O título continua nomeando a seção no menu do Modo Landing Page (que tem a caixa própria "Exibir no menu?") e na lista de seções do painel.
- **Lista de seções do painel:** marca "título oculto" nas seções com a caixa desmarcada.
- **Duplicação, exportação e importação** levam a caixa. Um ZIP antigo importa com o título visível.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `construtor-de-paginas`: o requisito "Seções da página" passa a exigir também a caixa da seção para mostrar o título.

## Impact

- **Banco:** uma migration com a coluna `show_title` (booleana, padrão 1) em `page_section`. As seções que já existem continuam com o título visível.
- **Código:**
  - `PageSection`;
  - `AdminContentController::populateSection()`;
  - `DuplicatorService`;
  - `TenantExporter` e `TenantImporter`.
- **Templates:**
  - `admin/section/new`, `edit` e `index`;
  - `page` e `category` dos temas cetec, moderno e wab, e `_sections` do noticias.

  Os sites de cliente (cetec) não mudam até alguém desmarcar a caixa.
