# Design: titulo-da-secao-opcional

## Context

- `PageSection` já tem `active` e `showInMenu`. O `populateSection()` lê as caixas com `(bool) $r->request->get(...)`.
- O título é montado em `page.html.twig` e `category.html.twig` do cetec, do moderno e do wab, e em `_sections.html.twig` do noticias, sempre com a condição `(currentTenant.showSectionTitles ?? true) and (section.titlePart1 or section.titlePart2)`.
- O menu do Modo Landing Page (`_header` de cada tema) usa o título para nomear o link da seção.

## Decisions

1. **Campo `showTitle` (bool, padrão `true`)** em `PageSection`, criado por `doctrine:migrations:diff` (`show_title TINYINT(1) DEFAULT 1 NOT NULL`).
2. **Formulário.** A caixa "Exibir o título da seção na página" entra ao lado de "Seção ativa" e "Exibir no menu?" em `new` (marcada) e em `edit`. A gravação é `(bool) $r->request->get('showTitle')`.
3. **Templates.** As sete condições ganham `and section.showTitle`. Os `_header` (menu) ficam como estão.
4. **Lista de seções.** Ao lado do título, mostra o selo "título oculto" quando a caixa está desmarcada.
5. **Duplicação, exportação e importação.**
   - O `DuplicatorService` copia o campo.
   - O `TenantExporter` grava `showTitle`.
   - O `TenantImporter` lê `$s['showTitle'] ?? true`.
6. **Isolamento.** A seção é carregada pelo `sectionOr404()`, que já confere o tenant pela página ou pela categoria. A caixa é só mais um campo da mesma gravação.

## Risks / Trade-offs

- **[Mudança em templates dos sites de cliente]** Com o padrão `true`, nada muda até alguém desmarcar. A conferência compara o HTML de antes e de depois de um site cetec local.

## Migration Plan

Local: migration, código e conferência. Produção (escrita, com aprovação do Jonas): backup (HEAD e dump), `git pull`, `doctrine:migrations:migrate` e `./build.sh`; HTTP 200 nos 8 domínios de cliente e no ajope.
