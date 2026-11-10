## MODIFIED Requirements

### Requirement: Tema do tenant
O sistema SHALL renderizar as páginas públicas a partir de `templates/themes/<Tenant.theme>/`, usando `moderno` quando não há tenant; o tema padrão de um tenant novo é `wab`, e os temas existentes são `cetec`, `moderno`, `wab` e `noticias`, todos oferecidos no `/admin/settings` e no cadastro e na edição do tenant no `/superadmin`.

#### Scenario: Troca de tema pelo admin
- **WHEN** o admin do site escolhe outro tema em `/admin/settings`
- **THEN** as páginas públicas passam a usar os templates da pasta desse tema

#### Scenario: Tema de notícias
- **WHEN** o SuperAdmin escolhe o tema "Notícias (portal)" para um site
- **THEN** as páginas públicas desse site passam a usar `templates/themes/noticias/`, e o menu do painel do site passa a mostrar "Notícias"

### Requirement: Logos claro e escuro
O cabeçalho SHALL mostrar o logo normal no modo claro e o logo escuro (`darkLogo`) no modo escuro, usando o logo normal quando não há logo escuro; o rodapé SHALL usar o logo escuro e, sem ele, o normal. No tema `noticias`, que não tem modo escuro e tem cabeçalho e rodapé na cor primária, ambos SHALL usar o logo escuro quando a cor primária é escura e o logo normal quando é clara; sem o logo escuro, SHALL mostrar o normal sobre um fundo branco arredondado.

#### Scenario: Tenant sem logo escuro
- **WHEN** o tenant só tem o logo normal e o visitante está no modo escuro
- **THEN** o cabeçalho mostra o logo normal

#### Scenario: Portal com cor primária escura e sem logo escuro
- **WHEN** um site com tema `noticias` tem a cor primária `#0b2559` e só o logo normal
- **THEN** o cabeçalho e o rodapé mostram o logo normal sobre um fundo branco arredondado
