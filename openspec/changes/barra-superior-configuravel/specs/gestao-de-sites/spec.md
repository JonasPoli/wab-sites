## ADDED Requirements

### Requirement: Barra superior na edição do tenant
A edição do tenant no `/superadmin` SHALL ter o mesmo editor da barra superior do `/admin/settings`, com as mesmas regras de gravação, e salvar por uma das duas telas SHALL manter o que a outra gravou nos demais campos do menu.

#### Scenario: Montar a barra pelo SuperAdmin
- **WHEN** o SuperAdmin liga a barra do tenant 13, acrescenta à esquerda WhatsApp e E-mail e salva
- **THEN** o site do tenant 13 mostra a barra com WhatsApp e e-mail, e o `/admin/settings` do site abre com os mesmos itens

### Requirement: Ícones da barra superior na sincronização
O comando `app:icones:sincronizar` SHALL incluir os ícones dos itens personalizados da barra superior de todos os tenants.

#### Scenario: Ícone novo na barra
- **WHEN** um site usa o ícone `fa-solid fa-user-lock` num item personalizado da barra
- **THEN** `app:icones:sincronizar --listar` mostra `fa6-solid:user-lock`
