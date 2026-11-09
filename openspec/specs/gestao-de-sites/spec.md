# gestao-de-sites Specification

## Purpose
Painel `/superadmin` da WAB para criar, configurar, excluir, exportar e importar sites (tenants), cadastrar usuários e entrar como eles, mais a gestão de editores pelo admin de cada site. Implementado em `App\Controller\superadmin\SuperAdminController`, `App\Service\TenantExporter`, `App\Service\TenantImporter` e na seção de editores do `AdminContentController`. O `/superadmin` só abre por um domínio de tenant, e o filtro de tenant desse domínio continua ligado (ver `isolamento-por-dominio`).

## Requirements
### Requirement: Painel com todos os sites e usuários
O `/superadmin` SHALL listar todos os tenants e todos os usuários, de todos os sites, porque Tenant e User não passam pelo filtro de tenant.

#### Scenario: Abrir o painel
- **WHEN** um SuperAdmin abre `/superadmin` em qualquer domínio de tenant
- **THEN** vê os 9 tenants e os usuários de todos eles

### Requirement: Cadastro e edição do tenant
O sistema SHALL gravar no tenant o domínio, o nome, as cores claras e escuras, os fundos, o tema, `showSectionTitles`, `landingPageMode`, `newsletterEnabled`, a página home, SEO, Google Analytics, contato, horários, redes sociais, barra superior e fontes de h1 a h5; logo, logo escuro e favicon só são gravados na edição, quando um arquivo novo é enviado.

#### Scenario: Horário vazio
- **WHEN** o SuperAdmin salva linhas de horário sem dias nem horas
- **THEN** essas linhas são descartadas, e sem nenhuma linha válida `openingHours` fica nulo

### Requirement: Página home lida pelo filtro do domínio
Na edição do tenant, o seletor de página home SHALL listar as páginas do tenant editado que também passam pelo filtro do domínio aberto, e salvar SHALL gravar `homePage` nulo quando nada é escolhido ou a página não passa pelo filtro.

#### Scenario: Editar outro site pelo domínio errado
- **WHEN** o SuperAdmin edita o tenant 5 com o `/superadmin` aberto em `marapossi.com.br` e salva
- **THEN** o seletor veio vazio e o tenant 5 fica sem página home

### Requirement: Exclusão confirmada pelo nome
O sistema SHALL excluir um tenant só com token CSRF válido e com o nome digitado igual ao nome do tenant; com nome diferente, SHALL cancelar com "A exclusão foi cancelada: o nome informado do tenant não confere.".

#### Scenario: Nome errado
- **WHEN** o SuperAdmin confirma a exclusão digitando um nome diferente
- **THEN** nada é apagado

### Requirement: O que a exclusão apaga
Ao excluir, o sistema SHALL apagar os arquivos físicos (logos, favicon, imagem do sobre, banners, capas, fundos, imagens de bloco, galeria, avatares, logos de parceiros e fotos de equipe) e os registros encontrados, os usuários do tenant antes do tenant. Contatos, inscritos e o que o filtro do domínio esconde saem pelo `ON DELETE CASCADE` do banco, sem apagar arquivos.

#### Scenario: Excluir pelo domínio do próprio site
- **WHEN** o SuperAdmin exclui o tenant 4 com o `/superadmin` aberto em `cemporcentoexpresso.com.br`
- **THEN** registros, arquivos e usuários do tenant 4 são apagados

### Requirement: Exportação em ZIP
O sistema SHALL exportar o tenant num ZIP `tenant_export_<domínio>_<data>.zip` com `metadata.json` (tenant, usuários com hash de senha, categorias, páginas, seções, blocos, banners, linhas de pesquisa, campos e mensagens de contato e inscritos) e os arquivos de mídia, montado em `var/tmp/export_*` e apagado depois do download.

#### Scenario: Exportar outro site pelo domínio errado
- **WHEN** o SuperAdmin exporta o tenant 5 com o `/superadmin` aberto em `marapossi.com.br`
- **THEN** o ZIP traz o tenant e os usuários, mas categorias, páginas, seções, blocos, contatos e inscritos vêm vazios

### Requirement: Importação com análise de conflitos
O sistema SHALL guardar o ZIP enviado em `var/tmp/upload_*.zip` e apontar conflito quando o domínio já existe ou um username ou e-mail de usuário já existe; sem esses conflitos, importa na hora; com eles, mostra o assistente para informar novos valores. Nome de tenant repetido não conta como conflito.

#### Scenario: Domínio já cadastrado
- **WHEN** o ZIP traz o domínio `pkwy.com.br`, que já existe
- **THEN** o assistente pede um domínio novo antes de importar

### Requirement: Importação transacional
A importação SHALL criar um tenant novo e todos os registros numa transação, recusar antes de gravar qualquer coisa quando um username ou e-mail resolvido ainda existe, e desfazer tudo se algo falhar; o ZIP enviado só é apagado quando a importação dá certo.

#### Scenario: Falha no meio da importação
- **WHEN** ocorre erro ao gravar os blocos
- **THEN** a transação é desfeita, nenhum registro do tenant fica no banco e o assistente reaparece com o erro

### Requirement: Usuários pelo SuperAdmin
O sistema SHALL cadastrar usuários com username, nome, e-mail, `workGroup` (0 admin, 1 editor, 2 revisor) e tenant; sem tenant e com `workGroup` 0, o usuário é SuperAdmin; a senha só muda quando um valor é digitado.

#### Scenario: Editar sem trocar a senha
- **WHEN** o SuperAdmin edita um usuário e deixa a senha em branco
- **THEN** a senha atual é mantida

### Requirement: Entrar como outro usuário
O sistema SHALL permitir ao SuperAdmin entrar como qualquer usuário pelo `/superadmin/impersonate/{id}`, que redireciona para `/admin?_switch_user=<username>` no mesmo domínio; o painel aberto é o do tenant desse domínio.

#### Scenario: Impersonar admin de outro site
- **WHEN** o SuperAdmin, em `marapossi.com.br`, entra como o admin do tenant 3
- **THEN** o `/admin` mostra o conteúdo do tenant 1, com a identidade do admin do tenant 3

### Requirement: Editores pelo admin do site
O `/admin/editors` SHALL listar e criar usuários com `workGroup` 1 ou 2 no tenant do usuário logado (o formulário só oferece Editor) e SHALL excluir um editor só quando ele é do mesmo tenant do usuário logado e o token CSRF confere.

#### Scenario: Excluir editor de outro site
- **WHEN** um admin tenta excluir um usuário de outro tenant
- **THEN** nada é apagado
