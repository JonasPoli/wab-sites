# contato-e-newsletter Specification

## Purpose
Recebe os formulários públicos de cada site: o "Fale conosco" (bloco `contact`) e a inscrição na newsletter (bloco `newsletter`), e mostra o que chegou no `/admin`. Implementado em `WabPublicController::contactPost()` e `newsletterSubscribe()`, nas entidades `ContactMessage` e `NewsletterSubscriber`, no template `email/contact.html.twig` e nas telas `/admin/contact` e `/admin/newsletter` do `AdminContentController`.

## Requirements
### Requirement: Contato exige token CSRF
O sistema SHALL recusar o POST em `/contato` sem token CSRF válido `contact_form`, com o aviso "Sessão expirada. Atualize a página e tente novamente." e retorno para `/#contato`, sem gravar nada.

#### Scenario: Página aberta há muito tempo
- **WHEN** o formulário é enviado com a sessão expirada
- **THEN** a mensagem não é gravada e o visitante vê o aviso de sessão expirada

### Requirement: Campos obrigatórios do contato
O sistema SHALL exigir nome, e-mail válido e mensagem, e responder "Preencha todos os campos corretamente." quando algum falta ou o e-mail é inválido; o telefone é opcional.

#### Scenario: E-mail inválido
- **WHEN** o visitante envia o contato com o e-mail `fulano@`
- **THEN** nada é gravado e o aviso de campos aparece em `/#contato`

### Requirement: Contato gravado no tenant
O sistema SHALL gravar cada contato válido em `ContactMessage` com o tenant do domínio, nome, e-mail, mensagem, data e `isRead` falso, antes de tentar o e-mail; o telefone digitado MUST NOT ser gravado, só vai no e-mail.

#### Scenario: Contato com telefone
- **WHEN** o visitante preenche nome, e-mail, telefone e mensagem
- **THEN** o registro em `contact_message` fica com `phone` vazio

### Requirement: E-mail do contato
O sistema SHALL enviar o contato no template `email/contact.html.twig` para `Tenant.contactEmail` ou, se vazio, para o parâmetro `emailContactTo`, com remetente `emailFrom` e nome do tenant, assunto "[<nome do tenant>] Novo contato: <nome>" e o telefone (ou "—") no corpo.

#### Scenario: Tenant sem e-mail de contato
- **WHEN** o tenant não tem `contactEmail`
- **THEN** o e-mail vai para o endereço de `EMAIL_CONTACT_TO`

### Requirement: Falha no e-mail não aparece para o visitante
Quando o envio do e-mail falha, o sistema SHALL registrar o erro no log e mesmo assim responder "Mensagem enviada com sucesso!", porque o contato já foi gravado.

#### Scenario: Transporte de e-mail fora do ar
- **WHEN** o envio lança exceção
- **THEN** o visitante vê a mensagem de sucesso e o contato fica só no `/admin/contact`

### Requirement: Contato sem anti-spam
O sistema MUST NOT aplicar honeypot, tempo mínimo de preenchimento, captcha ou limite por IP no contato; a única barreira é o token CSRF.

#### Scenario: Envio automático com token válido
- **WHEN** um robô carrega a página, lê o token e envia o formulário
- **THEN** o contato é gravado como qualquer outro

### Requirement: Inscrição na newsletter
O sistema SHALL, em POST `/newsletter/subscribe`, gravar o e-mail válido que ainda não está inscrito no tenant e avisar "Inscrição realizada com sucesso!"; e-mail inválido ou repetido recebe "E-mail inválido ou já inscrito."; o retorno é para a página de origem (referer) ou `/`.

#### Scenario: E-mail já inscrito em outro site
- **WHEN** um e-mail inscrito no tenant 1 se inscreve no tenant 3
- **THEN** a inscrição é aceita, porque a verificação de repetido é só dentro do tenant

### Requirement: Newsletter desligada
Com `Tenant.newsletterEnabled` falso, o sistema SHALL recusar a inscrição com "Newsletter não está disponível neste momento." e MUST NOT gravar o e-mail.

#### Scenario: POST direto com a newsletter desligada
- **WHEN** alguém envia POST para `/newsletter/subscribe` num tenant com a newsletter desligada
- **THEN** nada é gravado e aparece o aviso de indisponível

### Requirement: Newsletter sem conferência de CSRF
O sistema MUST NOT conferir o token CSRF `newsletter` que o formulário envia; o nome do inscrito fica vazio.

#### Scenario: POST sem token
- **WHEN** chega um POST com e-mail válido e sem `_token`
- **THEN** o e-mail é inscrito

### Requirement: Contatos no painel
O `/admin/contact` SHALL listar os contatos do tenant do domínio, do mais novo para o mais antigo, permitir marcar como lido e excluir (com token CSRF), e o painel inicial SHALL mostrar a contagem de contatos não lidos.

#### Scenario: Marcar como lido
- **WHEN** o admin marca um contato como lido
- **THEN** o contato deixa de contar nos não lidos do painel

### Requirement: Exportação da newsletter
O `/admin/newsletter/export.csv` SHALL gerar um CSV `newsletter.csv` com o cabeçalho `id,email,data`; cada linha chama `getCreatedAt()` do inscrito, método que a entidade `NewsletterSubscriber` não tem (ela tem `getSubscribedAt()`).

#### Scenario: Exportar com inscritos
- **WHEN** o tenant tem ao menos um inscrito e o admin pede a exportação
- **THEN** a geração falha com erro

#### Scenario: Exportar sem inscritos
- **WHEN** o tenant não tem inscritos
- **THEN** o CSV vem só com o cabeçalho
