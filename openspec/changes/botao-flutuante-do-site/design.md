# Design

## Context

Estado atual (motivação em `proposal.md`):

- O botão flutuante do WhatsApp está escrito à mão em dois `base.html.twig`: no cetec (`.whatsapp-float`, círculo verde de 64 px com `bi:whatsapp`, CSS no próprio base) e no atelie (`.at-float-whatsapp`, pílula verde com o texto "WhatsApp", CSS em `themes/atelie/_styles.html.twig`). Os dois só olham `currentTenant.whatsappLink` e escondem o botão com `modoPrevia`. moderno, wab e noticias não têm botão flutuante.
- A edição do tenant é `SuperAdminController::tenantEdit()` + `populateTenantFromRequest()`, que também atende o cadastro (`tenantNew`). O `/superadmin` roda sem o filtro de tenant do domínio: toda consulta de página precisa dizer o tenant (o seletor de home já faz `findOneBy(['id' => …, 'tenant' => $tenant])`).
- O seletor de ícones do Blurbs4 (`openIconPicker(inputId, previewId)`) mora inteiro em `templates/admin/wab_base.html.twig`: CSS (`.icon-picker-wrap` a `.icon-grid button`), o modal `#icon-modal-overlay` e o JavaScript (lista do FontAwesome lida da folha de estilo do CDN, abas, busca, rolagem infinita). O layout do `/superadmin` (`superadmin/base.html.twig`) não tem nada disso nem o CSS do FontAwesome.
- O ícone escolhido é gravado como classe (`fa-solid fa-envelope`) e desenhado nas páginas públicas como SVG embutido pelo filtro `icone_do_painel` (sem CSS de ícone de terceiro). O `app:icones:sincronizar` baixa para `assets/icons/` os ícones em uso, lidos de `category.icon` e de `page_block.config`.
- `tenant_css_vars()` publica `--color-primary` com a cor clara e a troca pela escura em `[data-theme="dark"]` e `.dark`, nos cinco temas. O atelie é só claro.

## Goals / Non-Goals

**Goals:**
- Um único parcial público para o botão, incluído nos cinco temas, sem mudar o HTML do botão do WhatsApp nos sites cetec e atelie que já estão no ar.
- Nenhum site muda de aparência só por causa do deploy.

**Non-Goals:**
- Escolha do botão no `/admin/settings` do site (fica só no `/superadmin`, como pedido).
- Posição, tamanho ou cores configuráveis; mais de um botão ao mesmo tempo; abrir o formulário numa janela sobre a página.
- Mudar o campo WhatsApp do `/admin/settings`.

## Decisions

### D1. Dados: uma coluna JSON `tenant.floating_button`

`{ "mode": "none"|"whatsapp"|"form", "pageId": int|null, "label": string, "icon": string }`, nula por padrão. Nula ou `mode` desconhecido = `whatsapp` (comportamento de hoje). Getter/setter em `Tenant` (`getFloatingButton(): array` sempre devolve as quatro chaves normalizadas) e a chave entra em `__serialize`/`__unserialize`.

*Alternativa descartada:* quatro colunas, com `ManyToOne` para `Page`. A chave estrangeira daria `ON DELETE SET NULL` de graça, mas exige mais uma relação tenant↔página (como a `homePage`) para tratar na exclusão do tenant, na exportação e na importação. Com o id no JSON, página excluída simplesmente não é encontrada e o botão some (cenário "Página de destino excluída").

*Alternativa descartada:* guardar dentro de `navigationSettings`. O `/admin/settings` e o `/superadmin` remontam esse JSON chave por chave ao salvar, e um esquecido apagaria a escolha.

### D2. Migration com valor inicial por tema

Gerada por `doctrine:migrations:diff` (`ALTER TABLE tenant ADD floating_button JSON DEFAULT NULL`), acrescentando à mão no `up()` um `UPDATE tenant SET floating_button = '{"mode":"none",…}' WHERE theme IN ('moderno','wab','noticias')`. Assim o ajope (noticias) e os sites de teste desses temas não ganham um botão de WhatsApp que nunca tiveram; os de cetec e atelie ficam nulos = WhatsApp, como hoje. O `down()` derruba a coluna.

### D3. Gravação no `/superadmin`

Em `populateTenantFromRequest()`, só quando o POST traz `floatingButtonMode` (o formulário de cadastro não traz; tenant novo fica nulo = WhatsApp):
- `mode` fora da lista vira `whatsapp`;
- `pageId` só é gravado se `findOneBy(['id' => …, 'tenant' => $tenant])` acha a página (isolamento: o `/superadmin` roda sem filtro de domínio); senão, `null`;
- `label`: `trim` e `mb_substr(…, 0, 40)`;
- `icon`: `trim`, até 80 caracteres, só `[a-z0-9 -]` (é uma classe; outro caractere zera o campo).
Página, texto e ícone são gravados qualquer que seja o tipo, para a troca de tipo não perder o que foi digitado.

### D4. Tela de edição

Grupo "Botão flutuante" logo depois das redes sociais (perto do campo WhatsApp), com três rádios e um bloco "Formulário" que só aparece com o rádio Formulário marcado (JS de poucas linhas; sem JS o bloco fica visível). Página: `<select>` com as páginas já carregadas para o seletor de home (`$pages`, só do tenant editado). Ícone: `icon-picker-wrap` igual ao do Blurbs4 (prévia, campo de texto e botão "Escolher" que chama `openIconPicker`). Uma prévia do botão, na cor primária do tenant, mostra texto e ícone enquanto o SuperAdmin digita.

### D5. Seletor de ícones reaproveitado

O CSS, o modal e o JavaScript do seletor saem de `admin/wab_base.html.twig` para `templates/admin/_seletor_de_icones.html.twig` (sem mudar o código), incluído de volta no `wab_base` no mesmo lugar e incluído também em `superadmin/tenant/edit.html.twig`, que passa a carregar o CSS do FontAwesome do mesmo CDN (só nessa tela do `/superadmin`). O JavaScript do seletor que era chamado no `DOMContentLoaded` do `wab_base` (carregar a lista e ligar a rolagem infinita) vai junto para o parcial, num `DOMContentLoaded` próprio.

### D6. Resolução do botão: serviço + função Twig

`App\Service\BotaoFlutuante::doSite(?Tenant): ?array` devolve `null` (sem botão) ou
- `['tipo' => 'whatsapp', 'url' => whatsappLink]`, ou
- `['tipo' => 'form', 'url' => …, 'texto' => …, 'icone' => …, 'paginaId' => …]`.

A página de destino é buscada com `PageRepository::findOneBy(['id' => pageId, 'tenant' => $tenant])` (isolamento explícito, além do filtro do domínio). URL: `pub_home` se a página é a `homePage` do tenant, senão `pub_page_show` com o slug. Exposta como função Twig `botao_flutuante()` (extensão nova, lê o tenant do `TenantContext`), com o resultado guardado por requisição.

### D7. Parcial público `themes/_comum/_botao_flutuante.html.twig`

Incluído no fim do `<body>` dos cinco `base.html.twig`, no lugar do bloco atual do WhatsApp (cetec e atelie) ou antes do `_webmcp` (moderno, wab, noticias). Recebe `tema` e não desenha nada com `modoPrevia`.

- WhatsApp: no atelie, o HTML de hoje (`.at-float-whatsapp`, CSS do tema intocado); nos outros quatro, o HTML de hoje do cetec (`.whatsapp-float`, círculo verde), com o CSS movido do base do cetec para o parcial (num `<style>` só emitido quando há botão). Os rótulos acessíveis continuam os de hoje ("Fale conosco pelo WhatsApp" no círculo, "Conversar pelo WhatsApp" no atelie).
- Formulário: `<a class="wab-flutuante-form">` com `{{ icone|icone_do_painel('', '1.15em') }}` e o texto, pílula no canto inferior direito (mesma posição do WhatsApp: 28 px no desktop, 16 px abaixo de 640 px), `z-index` 999, sombra, `:hover` com leve elevação e `:focus-visible` com contorno. Cores (D8). Oculto quando a página aberta (`page.id`, variável dos templates de página) é a de destino.

### D8. Cores do botão de formulário

O parcial calcula no servidor, com `is_dark_color()`, a cor do texto para a primária clara e para a escura (`#fff` sobre escura, `#111827` sobre clara) e emite:

```css
.wab-flutuante-form{background:var(--color-primary);color:<texto da clara>}
[data-theme="dark"] .wab-flutuante-form,.dark .wab-flutuante-form{color:<texto da escura>}
```

O fundo vem da variável que todo tema já recebe, então o modo escuro troca a cor sozinho. A borda é 1 px `rgba(0,0,0,.12)` para a pílula não sumir num fundo de seção da mesma cor.

### D9. Exportação, importação e ícones

- `TenantExporter`: `floatingButton` = o JSON do tenant.
- `TenantImporter`: grava o JSON e, depois de montar o `$pageMap` (onde já reconecta a `homePage`), troca `pageId` pelo id novo; sem correspondência, `null`.
- `SincronizarIconesCommand::nomesEmUso()`: inclui o `icon` de `tenant.floating_button`.
- Isolamento: a escrita é só no tenant editado; a leitura pública só do tenant do domínio (D6). Nenhuma `PageSection`/`PageBlock` é tocada.

## Risks / Trade-offs

- [Ícone escolhido que não está em `assets/icons/`] → no `prod` o UX Icons busca no Iconify sob demanda e guarda no cache; se a rede falhar, o botão sai só com o texto (o `icone_do_painel` não quebra). Mitigação: rodar `app:icones:sincronizar` e commitar quando um ícone novo for usado.
- [Pílula sobre conteúdo no celular] → o texto tem até 40 caracteres e a pílula tem `max-width: calc(100vw - 32px)` com reticências.
- [Valor inicial "Nenhum" por tema] → um site moderno/wab/noticias que quisesse o WhatsApp precisa marcar no `/superadmin`; é o comportamento de hoje, então nada se perde.
- [CSS do cetec movido para o parcial] → o HTML do botão do WhatsApp do cetec não muda; a regressão é conferida comparando o HTML da home de `cetec.localhost` antes e depois (só muda a posição do `<style>`).

## Migration Plan

1. Backup do banco de produção (`mysqldump`, escrita de arquivo no servidor).
2. `git pull`, `./build.sh` (templates e CSS) e `bin/console doctrine:migrations:migrate` (escrita no banco).
3. Conferir: HTTP 200 dos domínios de cliente; nos sites cetec o botão do WhatsApp igual; no ajope (noticias) nenhum botão novo; a tela de edição do tenant 13 abre com o grupo novo.

Volta: `git checkout` do commit anterior + `./build.sh`; a coluna nova pode ficar (é ignorada pelo código antigo) ou sair com `doctrine:migrations:migrate prev`.
