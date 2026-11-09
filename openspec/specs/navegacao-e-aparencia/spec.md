# navegacao-e-aparencia Specification

## Purpose
Define como cada site se apresenta: tema, cores claras e escuras, fontes, logos, cabeçalho com menu (normal ou Modo Landing Page), itens de menu personalizados e Google Analytics, a partir dos dados do Tenant. Implementado em `App\Twig\TenantExtension` (`tenant_css_vars()`, `is_dark_color()`, globais `currentTenant`, `headerCategories`, `footerPages`, `footerCategories`), em `WabPublicController::theme()`, em `AdminContentController::settings()` e nos templates `base`, `_header` e `_footer` de cada tema. As regras abaixo foram lidas no tema cetec, o único usado pelos tenants reais.

## Requirements
### Requirement: Tema do tenant
O sistema SHALL renderizar as páginas públicas a partir de `templates/themes/<Tenant.theme>/`, usando `moderno` quando não há tenant; o tema padrão de um tenant novo é `wab`, e os temas existentes são `cetec`, `moderno` e `wab`.

#### Scenario: Troca de tema pelo admin
- **WHEN** o admin do site escolhe outro tema em `/admin/settings`
- **THEN** as páginas públicas passam a usar os templates da pasta desse tema

### Requirement: Variáveis de cor e fontes
A função `tenant_css_vars()` SHALL gerar um `<style>` com `--color-primary`, `--color-secondary` e as versões RGB a partir das cores do tenant, trocadas pelas versões escuras em `[data-theme="dark"]`, `.dark`, `.wab-section-dark` e `.mp-section-dark`, mais o `@import` do Google Fonts e as regras de fonte, peso e tamanho de h1 a h5 de `fontSettings`.

#### Scenario: Barra superior ligada
- **WHEN** `navigationSettings.topBarEnabled` está ligado
- **THEN** `--topbar-height` vale 40px; desligado, vale 0px

### Requirement: Cor considerada escura
A função `is_dark_color()` SHALL considerar escura a cor cuja luminância 0,299·R + 0,587·G + 0,114·B é menor que 170, e também a cor vazia ou que não tem 3 ou 6 dígitos hexadecimais.

#### Scenario: Cor clara
- **WHEN** a cor é `#f8fafc`
- **THEN** a função devolve falso

### Requirement: Seção escura e fundos alternados
O sistema SHALL marcar como escura (classe `mp-section-dark` e texto branco) a seção com fundo de imagem, vídeo ou gradiente, ou com cor que `is_dark_color()` considera escura; seções sem fundo definido SHALL alternar `auto-bg-a` e `auto-bg-b`, com `bgColorLight1`/`bgColorLight2` no modo claro e `bgColorDark1`/`bgColorDark2` no escuro.

#### Scenario: Três seções sem fundo
- **WHEN** uma página tem três seções seguidas sem fundo definido
- **THEN** elas recebem `auto-bg-a`, `auto-bg-b` e `auto-bg-a`

### Requirement: Menu do cabeçalho
Fora do Modo Landing Page, o cabeçalho SHALL mostrar "Início" e as categorias raiz com `showInHeader`, em ordem alfabética, só quando a categoria tem páginas ou subcategorias com páginas; subcategorias com páginas aparecem num menu suspenso.

#### Scenario: Categoria vazia
- **WHEN** uma categoria raiz marcada para o cabeçalho não tem páginas nem subcategorias com páginas
- **THEN** ela não aparece no menu

### Requirement: Menu do Modo Landing Page
Com `landingPageMode` ligado e `homePage` definida, o cabeçalho SHALL listar as seções ativas da página home com `showInMenu`, com link `#section-{id}` (precedido do caminho da home fora dela), e rótulo `titlePart1` + `titlePart2`; sem título, o rótulo vem do primeiro bloco (banner: Início; blurbs4: Recursos; team: Equipe; contact: Contato; gallery: Portfólio) ou é "Seção N".

#### Scenario: Seção de contato sem título
- **WHEN** a terceira seção da home não tem título e o primeiro bloco é do tipo `contact`
- **THEN** o menu mostra "Contato" com link para `#section-<id da seção>`

#### Scenario: Landing page sem home
- **WHEN** `landingPageMode` está ligado mas o tenant não tem `homePage`
- **THEN** o cabeçalho usa o menu normal de categorias

### Requirement: Itens de menu personalizados
Ao salvar `/admin/settings`, o sistema SHALL descartar itens sem rótulo ou sem URL, acrescentar `https://` à URL que não começa com `http://`, `https://`, `//`, `mailto:`, `tel:`, `/` ou `#`, e gravar o alvo como `_blank` ou `_self`; o cabeçalho SHALL mostrar só os itens ativos. Salvar o tenant no `/superadmin` mantém os itens existentes.

#### Scenario: URL sem protocolo
- **WHEN** o admin cadastra o item "Blog" com URL `blog.cliente.com.br`
- **THEN** o item é gravado com `https://blog.cliente.com.br`

### Requirement: Links externos nos itens de bloco
Ao salvar um bloco, o sistema SHALL apagar `externalUrl` dos itens cujo `link` não é `external` e, nos externos, acrescentar `https://` à URL que não tem esquema, não começa com `//`, `/`, `#`, `mailto:` ou `tel:`.

#### Scenario: Item apontando para página interna
- **WHEN** um item de bloco é salvo com `link` diferente de `external` e uma URL externa preenchida
- **THEN** a URL externa é descartada

### Requirement: Logos claro e escuro
O cabeçalho SHALL mostrar o logo normal no modo claro e o logo escuro (`darkLogo`) no modo escuro, usando o logo normal quando não há logo escuro; o rodapé SHALL usar o logo escuro e, sem ele, o normal.

#### Scenario: Tenant sem logo escuro
- **WHEN** o tenant só tem o logo normal e o visitante está no modo escuro
- **THEN** o cabeçalho mostra o logo normal

### Requirement: Google Analytics
O sistema SHALL incluir o `gtag.js` com o id do tenant só quando `googleAnalyticsId` está preenchido.

#### Scenario: Sem id do Analytics
- **WHEN** o tenant não tem `googleAnalyticsId`
- **THEN** a página não carrega o script do Google Analytics
