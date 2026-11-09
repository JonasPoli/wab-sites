# isolamento-por-dominio Specification

## Purpose
Atribui cada requisição ao site (Tenant) cujo domínio é igual ao host e separa o conteúdo de um site do outro, numa instalação e num banco compartilhados. Vale para o site público, o painel `/admin` e o `/superadmin`. Implementado em `App\EventSubscriber\TenantSubscriber`, `App\Doctrine\TenantFilter`, `App\Service\TenantContext`, `App\Entity\User::getRoles()` e `config/packages/security.yaml`.

## Requirements
### Requirement: Tenant resolvido pelo host exato
O sistema SHALL, em toda requisição principal cujo caminho não começa com `/_`, buscar antes dos controllers (evento `kernel.request`, prioridade 100) o Tenant cujo campo `domain` é igual ao host da requisição, sem remover `www.` nem tratar porta, guardá-lo no `TenantContext` e ligar o filtro `tenant_filter` com o id dele.

#### Scenario: Domínio cadastrado
- **WHEN** chega uma requisição para `marapossi.com.br`
- **THEN** o `TenantContext` passa a ter o tenant 1
- **AND** as consultas da requisição ficam filtradas por esse tenant

#### Scenario: Variante com www
- **WHEN** chega uma requisição para `www.marapossi.com.br` e só `marapossi.com.br` está cadastrado
- **THEN** o host é tratado como domínio não cadastrado

### Requirement: Domínio não cadastrado
Quando nenhum Tenant tem o domínio do host, o sistema SHALL responder HTTP 404 com o HTML "Domínio não configurado" e o host escapado, e MUST NOT executar a rota pedida, inclusive `/login`, `/admin` e `/superadmin`.

#### Scenario: site.wab.com.br
- **WHEN** alguém abre `https://site.wab.com.br/superadmin`
- **THEN** a resposta é 404 com "O domínio site.wab.com.br não está registrado nesta plataforma."

### Requirement: Filtro automático das entidades com tenant
O sistema SHALL acrescentar `tenant_id = <tenant do domínio>` a toda consulta do ORM das entidades que implementam `TenantAwareInterface`: Category, Page, HeroBanner, ContactMessage, NewsletterSubscriber, ContactFormField e ResearchLine, inclusive `find()`, `findBy()` e consultas com join.

#### Scenario: Página de outro site
- **WHEN** o domínio do tenant 3 recebe `/p/{slug}` com o slug de uma página que só existe no tenant 1
- **THEN** a resposta é 404

#### Scenario: Filtro ligado no superadmin
- **WHEN** o `/superadmin`, aberto no domínio do tenant 1, consulta páginas com `findBy(['tenant' => tenant 5])`
- **THEN** o resultado vem vazio, porque o filtro também exige `tenant_id = 1`

### Requirement: Entidades sem tenant não são filtradas
O sistema MUST NOT filtrar PageSection, PageBlock, PageBlockImage, PageBlockTestimonial, PageBlockPartnerLogo, PageBlockTeamMember, User e Tenant; essas entidades ficam isoladas só quando o acesso parte de uma Page ou Category já filtrada.

#### Scenario: Perfil de membro de outro site
- **WHEN** o domínio do tenant 3 recebe `/membro/{slug}` com o slug de um membro cadastrado num bloco do tenant 1, com perfil detalhado ativo
- **THEN** o perfil é exibido no tema do tenant 3

#### Scenario: Seção acessada pelo id no admin
- **WHEN** um usuário logado no `/admin` de um domínio abre `/admin/section/{id}/edit` ou `/admin/block/{id}/edit` com o id de uma seção ou bloco de outro tenant
- **THEN** o formulário abre e o salvamento altera o registro do outro tenant

### Requirement: Login global por username
O sistema SHALL autenticar pelo `username` entre todos os usuários da base, em qualquer domínio de tenant, e MUST NOT conferir se o tenant do usuário é o tenant do domínio.

#### Scenario: Admin de um site entra pelo domínio de outro
- **WHEN** o admin do tenant 3 faz login em `marapossi.com.br/login`
- **THEN** o login é aceito e o `/admin` mostra e grava o conteúdo do tenant 1

### Requirement: Papéis pelo workGroup
O sistema SHALL derivar os papéis do usuário: sem tenant e `workGroup` 0 recebe ROLE_SUPER_ADMIN e ROLE_ADMIN; com tenant e `workGroup` 0 recebe ROLE_ADMIN; `workGroup` 1 (editor) ou 2 (revisor) recebe ROLE_EDITOR e ROLE_ADMIN. O `/superadmin` exige ROLE_SUPER_ADMIN e o `/admin` exige ROLE_ADMIN.

#### Scenario: Editor no painel
- **WHEN** um editor (`workGroup` 1) abre `/admin/settings` ou `/admin/editors` digitando o endereço
- **THEN** a página abre, porque só o menu esconde esses itens de quem não tem `workGroup` 0

### Requirement: Painel grava no tenant do domínio
O sistema SHALL usar o tenant do domínio, e não o tenant do usuário logado, ao criar banners, categorias, subcategorias e páginas e ao salvar `/admin/settings`; a lista e a criação de editores em `/admin/editors` usam o tenant do usuário logado.

#### Scenario: Página criada
- **WHEN** um admin cria uma página no `/admin` aberto em `pkwy.com.br`
- **THEN** a página é gravada com o tenant 3
