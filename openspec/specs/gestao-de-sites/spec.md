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

### Requirement: Exclusão confirmada pelo nome
O sistema SHALL excluir um tenant só com token CSRF válido e com o nome digitado igual ao nome do tenant; com nome diferente, SHALL cancelar com "A exclusão foi cancelada: o nome informado do tenant não confere.".

#### Scenario: Nome errado
- **WHEN** o SuperAdmin confirma a exclusão digitando um nome diferente
- **THEN** nada é apagado

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
O sistema SHALL cadastrar usuários com username, nome, e-mail, `workGroup` (0 admin, 1 editor, 2 revisor, 3 jornalista) e tenant; sem tenant e com `workGroup` 0, o usuário é SuperAdmin; a senha só muda quando um valor é digitado. O formulário SHALL vir com o `workGroup` atual do usuário marcado e recusar valores fora de 0 a 3; criar um jornalista por ali não cria o perfil de autor.

#### Scenario: Editar sem trocar a senha
- **WHEN** o SuperAdmin edita um usuário e deixa a senha em branco
- **THEN** a senha atual é mantida

#### Scenario: Trocar a senha de um jornalista
- **WHEN** o SuperAdmin edita um jornalista (`workGroup` 3), digita uma senha nova e salva
- **THEN** a senha muda e o usuário continua com `workGroup` 3, sem ganhar acesso ao `/admin`

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

### Requirement: Página home do tenant editado
Na edição do tenant, em qualquer domínio de tenant onde o `/superadmin` esteja aberto, o seletor de página home SHALL listar as páginas do tenant editado, e salvar SHALL gravar `homePage` nulo quando nada é escolhido ou quando o id enviado não é de uma página do tenant editado.

#### Scenario: Editar outro site pelo domínio de outro
- **WHEN** o SuperAdmin edita o tenant 5 com o `/superadmin` aberto em `marapossi.com.br` e salva sem mexer na home
- **THEN** o seletor veio com as páginas do tenant 5 e a home dele foi mantida

#### Scenario: Id de página de outro tenant
- **WHEN** o POST de edição do tenant 5 traz em `homePageId` o id de uma página do tenant 1
- **THEN** o tenant 5 fica sem página home e o tenant 1 não muda

### Requirement: Fontes dos títulos na edição do tenant
A edição do tenant no `/superadmin` SHALL oferecer, no campo de fonte de cada título de H1 a H5, as famílias Inter, Roboto, Playfair Display, Montserrat, Open Sans e Outfit, com a família gravada já marcada, ou Outfit quando não há nada gravado. Salvar SHALL gravar em `fontSettings` a família escolhida; uma família que não está nessa lista MUST ser gravada como Outfit.

#### Scenario: Escolher a Open Sans
- **WHEN** o SuperAdmin abre a edição de um tenant, escolhe Open Sans na fonte do H1 e salva
- **THEN** `fontSettings.h1.font` fica "Open Sans" e, ao reabrir a edição, o H1 aparece com a Open Sans marcada

#### Scenario: Lista das famílias
- **WHEN** o SuperAdmin abre a edição de qualquer tenant
- **THEN** cada um dos cinco campos de fonte, de H1 a H5, oferece as seis famílias: Inter, Roboto, Playfair Display, Montserrat, Open Sans e Outfit

#### Scenario: Família fora da lista
- **WHEN** a edição do tenant recebe um envio com uma família que não está na lista na fonte do H2
- **THEN** `fontSettings.h2.font` é gravado como Outfit

### Requirement: Chaves do reCAPTCHA do tenant
O cadastro e a edição do tenant no `/superadmin` SHALL ter os campos site key e secret key do reCAPTCHA v3. A secret key MUST NOT ser exibida depois de gravada: o campo mostra só se há chave, campo em branco mantém a chave atual, e uma opção explícita remove as duas chaves. O `/admin` do site MUST NOT mostrar nem editar essas chaves.

#### Scenario: Editar o tenant sem mexer na secret key
- **WHEN** o SuperAdmin abre a edição de um tenant com chaves, muda só a cor primária e salva
- **THEN** a secret key continua a mesma e a página de edição não mostra o valor dela

#### Scenario: Remover as chaves
- **WHEN** o SuperAdmin marca a opção de remover as chaves do reCAPTCHA e salva
- **THEN** o tenant fica sem as duas chaves e o contato do site volta a funcionar sem reCAPTCHA

### Requirement: Secret key fora da exportação
A exportação do tenant em ZIP MUST NOT incluir a secret key do reCAPTCHA; a site key SHALL ir no `metadata.json`. A importação SHALL criar o tenant com a site key do arquivo e sem secret key.

#### Scenario: Exportar e importar um site com chaves
- **WHEN** o SuperAdmin exporta um tenant com as duas chaves e importa o ZIP como um tenant novo
- **THEN** o `metadata.json` não tem a secret key e o tenant importado fica sem secret key, ou seja, sem reCAPTCHA até cadastrar a chave

### Requirement: Sites sem reCAPTCHA no painel
O painel do `/superadmin` SHALL indicar, na lista de tenants, quais sites estão sem as duas chaves do reCAPTCHA.

#### Scenario: Depois do deploy
- **WHEN** o SuperAdmin abre o `/superadmin` antes de cadastrar qualquer chave
- **THEN** todos os tenants aparecem marcados como sem reCAPTCHA
