# Spec Delta

## ADDED Requirements

### Requirement: Campos de proteção no formulário de contato
Todo formulário de contato SHALL trazer dois campos que o visitante não vê nem alcança: um campo isca (honeypot), de texto, vazio e escondido por CSS, e um campo com a hora em que a página foi montada, assinada pelo servidor. Os dois SHALL ficar fora da navegação por teclado e fora de leitores de tela, e MUST NOT carregar nada de terceiro.

#### Scenario: Formulário de um site sem chave do reCAPTCHA
- **WHEN** um visitante abre a home de um tenant sem as chaves do Google
- **THEN** o HTML do formulário tem o campo isca e o campo de hora assinada, nenhum dos dois aparece na tela e a página não chama `www.google.com/recaptcha`

#### Scenario: Visitante que navega pelo teclado
- **WHEN** o visitante percorre o formulário com a tecla Tab
- **THEN** o foco pula os dois campos de proteção e vai de nome para e-mail, telefone e mensagem

### Requirement: Contato bloqueado pelo antispam
As conferências de antispam SHALL rodar em todos os tenants, com ou sem chave do reCAPTCHA, depois do CSRF e dos campos obrigatórios e antes da conferência do reCAPTCHA. Quando alguma delas reprova o envio, o sistema MUST NOT enviar e-mail e SHALL responder ao visitante o mesmo "Mensagem enviada com sucesso!" de um envio aceito, para o robô não descobrir qual regra o pegou.

#### Scenario: Robô que preenche tudo
- **WHEN** um robô lê o token CSRF e envia o formulário com o campo isca preenchido
- **THEN** nenhum e-mail sai e o robô recebe a página de sucesso em `/#contato`

#### Scenario: Site com chave do reCAPTCHA
- **WHEN** o envio de um tenant com chaves é reprovado pelo antispam
- **THEN** o token não é conferido no Google e o envio é bloqueado

### Requirement: Campo isca preenchido
O sistema SHALL bloquear o contato cujo campo isca chegar com qualquer conteúdo, inclusive espaços.

#### Scenario: Campo isca com um espaço
- **WHEN** o envio traz o campo isca com um espaço em branco
- **THEN** o contato é bloqueado

#### Scenario: Visitante de verdade
- **WHEN** o envio traz o campo isca vazio e passa nas outras conferências
- **THEN** o contato é aceito, gravado e enviado por e-mail como antes

### Requirement: Tempo mínimo de preenchimento
O sistema SHALL bloquear o contato enviado menos de 3 segundos depois de a página ser montada, e também o contato cujo campo de hora esteja ausente, com assinatura que não confere ou com hora no futuro. Hora antiga MUST NOT bloquear o envio: quem deixa a página aberta e envia depois é aceito, e a validade do envio continua sendo decidida pelo token CSRF.

#### Scenario: Envio instantâneo
- **WHEN** o formulário é enviado 1 segundo depois de a página ser montada
- **THEN** o contato é bloqueado

#### Scenario: POST direto, sem abrir a página
- **WHEN** chega um POST com token CSRF válido e sem o campo de hora
- **THEN** o contato é bloqueado

#### Scenario: Hora adulterada
- **WHEN** o campo de hora chega com a data trocada e a assinatura do valor original
- **THEN** o contato é bloqueado

#### Scenario: Página aberta há duas horas
- **WHEN** o visitante abre a página, escreve com calma e envia duas horas depois, com a sessão ainda válida
- **THEN** o contato é aceito

### Requirement: Limite de envios por IP
O sistema SHALL aceitar no máximo 3 contatos por hora do mesmo endereço IP, contando só os aceitos, e bloquear os seguintes até a hora correr. O limite SHALL ser por IP, valendo para todos os domínios da instalação.

#### Scenario: Quarta mensagem na mesma hora
- **WHEN** o mesmo IP tem 3 contatos aceitos na última hora e envia o quarto
- **THEN** o quarto contato é bloqueado

#### Scenario: Envios bloqueados não gastam o limite
- **WHEN** um IP tem 10 envios bloqueados pelo campo isca e depois um visitante de verdade envia do mesmo IP
- **THEN** o contato do visitante é aceito

### Requirement: Mensagem com cara de spam
O sistema SHALL bloquear o contato cuja mensagem tenha 4 ou mais links, qualquer marcação de link (`[url=`, `[link=` ou `<a href`) ou qualquer letra do alfabeto cirílico no nome ou na mensagem.

#### Scenario: Mensagem cheia de links
- **WHEN** a mensagem traz 5 endereços começando com `http://`
- **THEN** o contato é bloqueado

#### Scenario: BBCode de link
- **WHEN** a mensagem traz `[url=http://exemplo.ru]oferta[/url]`
- **THEN** o contato é bloqueado

#### Scenario: Mensagem em russo
- **WHEN** o nome ou a mensagem tem letras cirílicas
- **THEN** o contato é bloqueado

#### Scenario: Cliente que manda um link
- **WHEN** a mensagem tem um único link para o site do visitante
- **THEN** o contato é aceito

### Requirement: Registro do contato bloqueado
O contato bloqueado SHALL ser gravado em `ContactMessage` com o tenant, o domínio de origem, os dados enviados e o motivo do bloqueio (campo isca, tempo, limite por IP ou conteúdo), já marcado como lido, e MUST NOT contar como mensagem nova. O log SHALL registrar o bloqueio com o domínio, o motivo e o IP, sem nome, e-mail, telefone ou mensagem.

#### Scenario: Spam gravado com o motivo
- **WHEN** um envio é bloqueado pelo campo isca
- **THEN** o registro em `contact_message` fica com o motivo do bloqueio e não entra na contagem de não lidos do painel

#### Scenario: Log sem dado do visitante
- **WHEN** um envio é bloqueado
- **THEN** a linha do log tem domínio, motivo e IP e nenhum dado digitado no formulário

### Requirement: Bloqueados separados no painel
O `/admin/contact` SHALL listar por padrão só os contatos aceitos do tenant do domínio e SHALL oferecer um filtro que mostra os bloqueados, com o motivo de cada um, e a quantidade de bloqueados. O painel inicial MUST NOT contar bloqueados entre os contatos não lidos.

#### Scenario: Lista normal
- **WHEN** o tenant tem 2 contatos aceitos e 30 bloqueados e o admin abre `/admin/contact`
- **THEN** a tela mostra as 2 mensagens aceitas e informa que há 30 bloqueados

#### Scenario: Ver os bloqueados
- **WHEN** o admin usa o filtro de bloqueados
- **THEN** a tela lista os 30 bloqueados do tenant do domínio, cada um com o motivo, e permite excluí-los como qualquer contato

## MODIFIED Requirements

### Requirement: Contato gravado no tenant
O sistema SHALL gravar cada contato válido em `ContactMessage` com o tenant do domínio, nome, e-mail, mensagem, data, o domínio de onde o envio saiu e `isRead` falso, antes de tentar o e-mail; o telefone digitado MUST NOT ser gravado, só vai no e-mail.

#### Scenario: Contato com telefone
- **WHEN** o visitante preenche nome, e-mail, telefone e mensagem
- **THEN** o registro em `contact_message` fica com `phone` vazio

#### Scenario: Domínio de origem
- **WHEN** o visitante envia o contato em `imperiodainformatica.com.br`
- **THEN** o registro guarda esse domínio como origem do envio

### Requirement: E-mail do contato
O sistema SHALL enviar o contato no template `email/contact.html.twig` para `Tenant.contactEmail` ou, se vazio, para o parâmetro `emailContactTo`, com remetente `emailFrom` e nome do tenant, assunto "[<nome do tenant>] Novo contato: <nome>" e, no corpo, o telefone (ou "—") e o domínio de onde o envio saiu.

#### Scenario: Tenant sem e-mail de contato
- **WHEN** o tenant não tem `contactEmail`
- **THEN** o e-mail vai para o endereço de `EMAIL_CONTACT_TO`

#### Scenario: Domínio no corpo do e-mail
- **WHEN** o contato sai de `site.petvida.com.br` e o e-mail vai para o endereço comum da WAB
- **THEN** o corpo do e-mail mostra que a mensagem veio de `site.petvida.com.br`

### Requirement: Contatos no painel
O `/admin/contact` SHALL listar os contatos aceitos do tenant do domínio, do mais novo para o mais antigo, permitir marcar como lido e excluir (com token CSRF), e o painel inicial SHALL mostrar a contagem de contatos aceitos não lidos.

#### Scenario: Marcar como lido
- **WHEN** o admin marca um contato como lido
- **THEN** o contato deixa de contar nos não lidos do painel
