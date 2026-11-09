## ADDED Requirements

### Requirement: Seções e blocos pelo tenant da página ou categoria
O `/admin` SHALL localizar a seção pelo tenant da página ou categoria a que ela pertence, e o bloco pelo tenant da seção dele, ao editar, excluir e duplicar seções e blocos, ao listar os blocos de uma seção e ao criar bloco nela. Id de outro tenant, ou de seção sem página nem categoria, SHALL responder 404 sem gravar nada.

#### Scenario: Excluir seção de outro site
- **WHEN** um usuário logado no `/admin` de `marapossi.com.br` envia POST para `/admin/section/{id}/delete` com o id de uma seção de página do tenant 3 e um token válido
- **THEN** a resposta é 404 e a seção continua no tenant 3

#### Scenario: Criar bloco em seção de outro site
- **WHEN** um usuário logado no `/admin` de `marapossi.com.br` abre `/admin/section/{id}/block/new` com o id de uma seção do tenant 3
- **THEN** a resposta é 404 e nenhum bloco é criado

#### Scenario: Seção de categoria do próprio site
- **WHEN** o admin do tenant 1 abre `/admin/section/{id}/edit` em `marapossi.com.br` com o id de uma seção de categoria do tenant 1
- **THEN** o formulário abre e o salvamento altera a seção

### Requirement: Reordenação só no próprio site
Na reordenação de seções (`/admin/section/reorder`) e de blocos (`/admin/block/reorder`), o sistema SHALL alterar a posição só dos ids que pertencem ao tenant do domínio, pelo tenant da página ou categoria, ignorar os demais e responder `{"ok": true}`.

#### Scenario: Lista com ids de dois sites
- **WHEN** o `/admin` de `marapossi.com.br` recebe a reordenação de blocos com dois ids do tenant 1 e um id do tenant 3
- **THEN** só os dois blocos do tenant 1 mudam de posição
- **AND** o bloco do tenant 3 fica como estava

### Requirement: Login restrito ao site do usuário
O sistema SHALL autenticar pelo `username` e aceitar o login de usuário com tenant só no domínio desse tenant, recusando-o nos demais com a mensagem "Este usuário não tem acesso a este site." e sem abrir sessão. O SuperAdmin (sem tenant e `workGroup` 0) SHALL entrar em qualquer domínio de tenant; usuário sem tenant com outro `workGroup` SHALL ser recusado em todos.

#### Scenario: Admin de um site entra pelo domínio de outro
- **WHEN** o admin do tenant 3 faz login em `marapossi.com.br/login` com a senha certa
- **THEN** o login é recusado com "Este usuário não tem acesso a este site."
- **AND** o `/admin` de `marapossi.com.br` continua pedindo login

#### Scenario: Editor no próprio site
- **WHEN** o editor do tenant 3 faz login em `pkwy.com.br/login`
- **THEN** o login é aceito e o `/admin` mostra o conteúdo do tenant 3

#### Scenario: SuperAdmin em qualquer site
- **WHEN** um SuperAdmin faz login em `site.topdrim.com.br/login`
- **THEN** o login é aceito e o `/admin` mostra o conteúdo do tenant 8

### Requirement: Sessão vale só no site do usuário
Em toda requisição ao `/admin`, o sistema SHALL responder 403 quando o usuário da sessão não é SuperAdmin e o tenant dele não é o tenant do domínio, inclusive em sessão aberta antes da regra de login restrito.

#### Scenario: Sessão aberta antes da mudança
- **WHEN** o admin do tenant 3 tem uma sessão em `marapossi.com.br` aberta antes do deploy e abre `/admin/page`
- **THEN** a resposta é 403 e nenhuma página do tenant 1 é listada

### Requirement: Filtro automático no site e no painel
No site público e no `/admin`, o sistema SHALL acrescentar `tenant_id = <tenant do domínio>` a toda consulta do ORM das entidades que implementam `TenantAwareInterface`: Category, Page, HeroBanner, ContactMessage, NewsletterSubscriber, ContactFormField e ResearchLine, inclusive `find()`, `findBy()` e consultas com join. No `/superadmin` o filtro MUST NOT ser aplicado.

#### Scenario: Página de outro site
- **WHEN** o domínio do tenant 3 recebe `/p/{slug}` com o slug de uma página que só existe no tenant 1
- **THEN** a resposta é 404

#### Scenario: Filtro desligado no superadmin
- **WHEN** o `/superadmin`, aberto no domínio do tenant 1, consulta páginas com `findBy(['tenant' => tenant 5])`
- **THEN** o resultado traz as páginas do tenant 5

## MODIFIED Requirements

### Requirement: Tenant resolvido pelo host exato
O sistema SHALL, em toda requisição principal cujo caminho não começa com `/_`, buscar antes dos controllers (evento `kernel.request`, prioridade 100) o Tenant cujo campo `domain` é igual ao host da requisição, sem remover `www.` nem tratar porta, guardá-lo no `TenantContext` e, fora dos caminhos do `/superadmin`, ligar o filtro `tenant_filter` com o id dele.

#### Scenario: Domínio cadastrado
- **WHEN** chega uma requisição para `marapossi.com.br`
- **THEN** o `TenantContext` passa a ter o tenant 1
- **AND** as consultas da requisição ficam filtradas por esse tenant

#### Scenario: Variante com www
- **WHEN** chega uma requisição para `www.marapossi.com.br` e só `marapossi.com.br` está cadastrado
- **THEN** o host é tratado como domínio não cadastrado

#### Scenario: Superadmin no domínio de um tenant
- **WHEN** chega uma requisição para `marapossi.com.br/superadmin`
- **THEN** o `TenantContext` passa a ter o tenant 1
- **AND** o filtro `tenant_filter` fica desligado nessa requisição

### Requirement: Entidades sem tenant não são filtradas
O sistema MUST NOT filtrar PageSection, PageBlock, PageBlockImage, PageBlockTestimonial, PageBlockPartnerLogo, PageBlockTeamMember, User e Tenant; essas entidades SHALL ser lidas e gravadas pelo site público e pelo `/admin` só a partir de uma Page ou Category do tenant do domínio (User, conferindo o tenant do usuário).

#### Scenario: Perfil de membro de outro site
- **WHEN** o domínio do tenant 3 recebe `/membro/{slug}` com o slug de um membro cadastrado num bloco do tenant 1, com perfil detalhado ativo
- **THEN** a resposta é 404 "Perfil não encontrado."

#### Scenario: Seção acessada pelo id no admin
- **WHEN** um usuário logado no `/admin` de um domínio abre `/admin/section/{id}/edit` ou `/admin/block/{id}/edit` com o id de uma seção ou bloco de outro tenant
- **THEN** a resposta é 404 e nada é gravado

### Requirement: Papéis pelo workGroup
O sistema SHALL derivar os papéis do usuário: sem tenant e `workGroup` 0 recebe ROLE_SUPER_ADMIN e ROLE_ADMIN; com tenant e `workGroup` 0 recebe ROLE_ADMIN; `workGroup` 1 (editor) ou 2 (revisor) recebe ROLE_EDITOR e ROLE_ADMIN. O `/superadmin` exige ROLE_SUPER_ADMIN, o `/admin` exige ROLE_ADMIN e `/admin/settings` e `/admin/editors` exigem `workGroup` 0 (admin do site do domínio ou SuperAdmin).

#### Scenario: Editor no painel
- **WHEN** um editor (`workGroup` 1) ou revisor (`workGroup` 2) abre `/admin/settings` ou `/admin/editors` digitando o endereço
- **THEN** a resposta é 403 e nada é gravado

#### Scenario: Admin do site nos editores
- **WHEN** o admin (`workGroup` 0) do tenant 1 abre `/admin/editors` em `marapossi.com.br`
- **THEN** a página abre com os editores e revisores do tenant 1

### Requirement: Painel grava no tenant do domínio
O sistema SHALL usar o tenant do domínio ao criar banners, categorias, subcategorias e páginas, ao salvar `/admin/settings` e ao listar, criar e excluir editores em `/admin/editors`; para quem não é SuperAdmin, ele é sempre o tenant do usuário logado.

#### Scenario: Página criada
- **WHEN** um admin cria uma página no `/admin` aberto em `pkwy.com.br`
- **THEN** a página é gravada com o tenant 3

#### Scenario: Editor criado pelo SuperAdmin
- **WHEN** um SuperAdmin cria um editor no `/admin/editors` aberto em `pkwy.com.br`
- **THEN** o editor é gravado com o tenant 3, e não sem tenant

## REMOVED Requirements

### Requirement: Login global por username
**Reason**: decisão do Jonas em 09/11/2026: o login do painel não pode valer para todos os sites; substituído por "Login restrito ao site do usuário" e "Sessão vale só no site do usuário".
**Migration**: cada usuário de site entra pelo domínio do próprio tenant (por exemplo, o editor do tenant 3 em `pkwy.com.br/login`); o SuperAdmin continua entrando por qualquer domínio de tenant.

### Requirement: Filtro automático das entidades com tenant
**Reason**: o filtro passa a não valer no `/superadmin` (decisão do Jonas em 09/11/2026); substituído por "Filtro automático no site e no painel", que mantém o comportamento no site público e no `/admin`.
**Migration**: nenhuma no site público e no `/admin`. No `/superadmin`, consultas a entidades com tenant precisam dizer o tenant explicitamente.
