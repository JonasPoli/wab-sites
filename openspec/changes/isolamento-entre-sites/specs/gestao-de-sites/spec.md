## ADDED Requirements

### Requirement: Página home do tenant editado
Na edição do tenant, em qualquer domínio de tenant onde o `/superadmin` esteja aberto, o seletor de página home SHALL listar as páginas do tenant editado, e salvar SHALL gravar `homePage` nulo quando nada é escolhido ou quando o id enviado não é de uma página do tenant editado.

#### Scenario: Editar outro site pelo domínio de outro
- **WHEN** o SuperAdmin edita o tenant 5 com o `/superadmin` aberto em `marapossi.com.br` e salva sem mexer na home
- **THEN** o seletor veio com as páginas do tenant 5 e a home dele foi mantida

#### Scenario: Id de página de outro tenant
- **WHEN** o POST de edição do tenant 5 traz em `homePageId` o id de uma página do tenant 1
- **THEN** o tenant 5 fica sem página home e o tenant 1 não muda

## MODIFIED Requirements

### Requirement: O que a exclusão apaga
Ao excluir, em qualquer domínio de tenant onde o `/superadmin` esteja aberto, o sistema SHALL apagar os arquivos físicos (logos, favicon, imagem do sobre, banners, capas, fundos, imagens de bloco, galeria, avatares, logos de parceiros e fotos de equipe) e os registros do tenant, os usuários do tenant antes do tenant. Contatos e inscritos, que não têm arquivos, saem pelo `ON DELETE CASCADE` do banco.

#### Scenario: Excluir pelo domínio do próprio site
- **WHEN** o SuperAdmin exclui o tenant 4 com o `/superadmin` aberto em `cemporcentoexpresso.com.br`
- **THEN** registros, arquivos e usuários do tenant 4 são apagados

#### Scenario: Excluir pelo domínio de outro site
- **WHEN** o SuperAdmin exclui o tenant 4 com o `/superadmin` aberto em `marapossi.com.br`
- **THEN** registros, arquivos e usuários do tenant 4 são apagados, sem deixar em `public/uploads` arquivo que só o tenant 4 usava

### Requirement: Exportação em ZIP
O sistema SHALL exportar o tenant num ZIP `tenant_export_<domínio>_<data>.zip` com `metadata.json` (tenant, usuários com hash de senha, categorias, páginas, seções, blocos, banners, linhas de pesquisa, campos e mensagens de contato e inscritos) e os arquivos de mídia, montado em `var/tmp/export_*` e apagado depois do download.

#### Scenario: Exportar outro site pelo domínio errado
- **WHEN** o SuperAdmin exporta o tenant 5 com o `/superadmin` aberto em `marapossi.com.br`
- **THEN** o ZIP traz o tenant, os usuários, as categorias, as páginas, as seções, os blocos, os contatos, os inscritos e os arquivos de mídia do tenant 5

### Requirement: Entrar como outro usuário
O sistema SHALL permitir ao SuperAdmin entrar como qualquer usuário pelo `/superadmin/impersonate/{id}`, que redireciona para `/admin?_switch_user=<username>` no domínio do tenant desse usuário, com o esquema e a porta da requisição; usuário sem tenant, no mesmo domínio. A troca para usuário de outro tenant no domínio errado SHALL ser recusada com 403.

#### Scenario: Impersonar admin de outro site
- **WHEN** o SuperAdmin, em `marapossi.com.br`, entra como o admin do tenant 3
- **THEN** é redirecionado para `https://pkwy.com.br/admin?_switch_user=<username>`
- **AND** com a sessão de SuperAdmin em `pkwy.com.br`, o `/admin` mostra o conteúdo do tenant 3 com a identidade do admin do tenant 3

#### Scenario: Sem sessão no domínio do usuário
- **WHEN** o SuperAdmin entra como o admin do tenant 3 sem estar logado em `pkwy.com.br`
- **THEN** vê o login de `pkwy.com.br` e, depois de entrar com a conta de SuperAdmin, passa a agir como o admin do tenant 3

#### Scenario: Troca forçada no domínio errado
- **WHEN** o SuperAdmin abre `marapossi.com.br/admin?_switch_user=<username do admin do tenant 3>`
- **THEN** a resposta é 403 e a sessão continua a do SuperAdmin

### Requirement: Editores pelo admin do site
O `/admin/editors` SHALL abrir só para o admin (`workGroup` 0) do site do domínio e para o SuperAdmin, SHALL listar e criar usuários com `workGroup` 1 ou 2 no tenant do domínio (o formulário só oferece Editor; outro valor vira 1) e SHALL excluir só usuário com `workGroup` 1 ou 2 do tenant do domínio, com token CSRF válido.

#### Scenario: Excluir editor de outro site
- **WHEN** um admin tenta excluir um usuário de outro tenant
- **THEN** nada é apagado

#### Scenario: Excluir admin do mesmo site
- **WHEN** um admin envia a exclusão de outro usuário `workGroup` 0 do mesmo tenant
- **THEN** nada é apagado

#### Scenario: Criar admin pelo formulário de editor
- **WHEN** um admin envia o formulário de novo editor com `workGroup` 0
- **THEN** o usuário é criado com `workGroup` 1 no tenant do domínio

#### Scenario: Editor abre a gestão de editores
- **WHEN** um editor abre `/admin/editors` ou `/admin/editors/new` digitando o endereço
- **THEN** a resposta é 403

## REMOVED Requirements

### Requirement: Página home lida pelo filtro do domínio
**Reason**: com o `/superadmin` sem o filtro de tenant do domínio (decisão de 09/11/2026), a home deixa de depender do domínio aberto; substituído por "Página home do tenant editado".
**Migration**: nenhuma; editar o tenant em qualquer domínio de tenant passa a manter a home. Em 09/11/2026 os 8 tenants de produção têm página home do próprio tenant.
