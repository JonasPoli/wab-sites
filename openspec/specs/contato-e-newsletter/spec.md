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
O `/admin/newsletter/export.csv` SHALL gerar um CSV `newsletter.csv` com os inscritos do tenant do domínio: cabeçalho `id,email,data` e uma linha por inscrito com o id, o e-mail e a data de inscrição no formato `AAAA-MM-DD HH:MM`, com as células escapadas como CSV. O botão "Exportar CSV" do `/admin/newsletter` SHALL baixar esse arquivo.

#### Scenario: Exportar com inscritos
- **WHEN** o tenant tem 3 inscritos e o admin pede a exportação
- **THEN** o CSV vem com o cabeçalho e 3 linhas com id, e-mail e data de inscrição

#### Scenario: Exportar sem inscritos
- **WHEN** o tenant não tem inscritos
- **THEN** o CSV vem só com o cabeçalho

#### Scenario: Inscritos de outro site
- **WHEN** o admin do tenant 1 exporta a newsletter em `marapossi.com.br`
- **THEN** os inscritos do tenant 3 não aparecem no arquivo

#### Scenario: E-mail que começa com sinal de fórmula
- **WHEN** um inscrito tem o e-mail `=teste@exemplo.com`
- **THEN** a célula sai como `'=teste@exemplo.com`, para a planilha não executar como fórmula

#### Scenario: Botão do painel
- **WHEN** o admin clica em "Exportar CSV" no `/admin/newsletter`
- **THEN** o navegador baixa o `newsletter.csv` gerado por `/admin/newsletter/export.csv`

### Requirement: reCAPTCHA no contato
Quando o tenant do domínio tem as duas chaves do reCAPTCHA v3, o sistema SHALL conferir junto ao Google, depois do CSRF e dos campos obrigatórios e antes de gravar, o token enviado pelo formulário de contato. O contato SHALL ser aceito só com token válido, com a ação `contato` e com nota igual ou maior que a nota mínima configurada (0,5 por padrão).

#### Scenario: Visitante real
- **WHEN** um visitante de marapossi.com.br, que tem chaves, envia o contato e o Google devolve nota 0,9 para a ação `contato`
- **THEN** o contato é gravado no tenant, o e-mail sai como antes e o visitante vê a mensagem de sucesso

#### Scenario: Token de outro site
- **WHEN** o formulário de um tenant envia um token que o Google confirma, mas com ação diferente de `contato`
- **THEN** o contato é recusado como na regra de contato recusado

### Requirement: Contato recusado pelo reCAPTCHA
Quando o tenant tem chaves e o token falta ou é recusado pelo Google (inválido, vencido ou repetido, ação diferente ou nota abaixo da mínima), o sistema MUST NOT gravar o contato nem enviar e-mail, e SHALL voltar para `/#contato` com o aviso "Não foi possível confirmar o envio. Tente de novo em alguns segundos.". A recusa SHALL ir para o log com o domínio, a nota, a ação e o IP, sem nome, e-mail, telefone ou mensagem.

#### Scenario: Robô com token CSRF e sem token do reCAPTCHA
- **WHEN** um robô lê o token CSRF da página e envia nome, e-mail e mensagem a um site com chaves, sem o token do reCAPTCHA
- **THEN** nada é gravado em `contact_message`, nenhum e-mail sai e a resposta volta para `/#contato` com o aviso

#### Scenario: Nota baixa
- **WHEN** o Google devolve nota 0,1 para o token do contato
- **THEN** nada é gravado, nenhum e-mail sai e o log registra a recusa com a nota 0,1 e o domínio, sem os dados do visitante

### Requirement: Google fora do ar na verificação do contato
Quando a conferência do token não tiver resposta válida do Google (erro de rede, tempo esgotado, resposta que não é JSON ou secret key recusada pelo Google), o sistema SHALL aceitar o contato como se o site não tivesse reCAPTCHA e registrar um aviso no log com o domínio. Token recusado pelo Google não é falha do Google e segue a regra de contato recusado.

#### Scenario: Secret key errada no cadastro
- **WHEN** o tenant tem uma secret key que o Google responde como `invalid-input-secret` e um visitante envia o contato
- **THEN** o contato é gravado, o e-mail sai e o log avisa que a chave daquele domínio está errada

### Requirement: Site sem chave do reCAPTCHA
Quando o tenant do domínio não tem as duas chaves, o sistema SHALL aceitar o contato sem conferir token, como antes desta mudança, MUST NOT carregar o script do Google e SHALL registrar um aviso no log com o domínio a cada contato aceito assim.

#### Scenario: Site novo ainda sem chave
- **WHEN** um visitante envia o contato num tenant sem chaves
- **THEN** o contato é gravado como antes, a página não tem o script do reCAPTCHA e o log avisa que o domínio está sem chave

### Requirement: Script do reCAPTCHA sob demanda
O sistema MUST NOT carregar o script do reCAPTCHA na abertura das páginas. O script SHALL carregar só quando o visitante entrar num formulário de contato, e o token SHALL ser pedido no envio, para não vencer enquanto o visitante escreve. Se o script não carregar, o envio SHALL seguir sem token e o servidor decide pelas regras acima.

#### Scenario: Página aberta sem tocar no formulário
- **WHEN** um visitante abre a home de um site com chaves e não entra no formulário de contato
- **THEN** a página não faz nenhuma requisição a `www.google.com/recaptcha`

#### Scenario: Visitante demora para escrever
- **WHEN** o visitante entra no formulário, escreve por 5 minutos e envia
- **THEN** o token é gerado no envio e o contato é aceito

### Requirement: Aviso do reCAPTCHA no contato
Num tenant com chaves, o sistema SHALL mostrar abaixo de cada formulário de contato o aviso "Este site é protegido pelo reCAPTCHA, e se aplicam a Política de Privacidade e os Termos de Serviço do Google.", com os links do Google, e SHALL esconder o selo flutuante do reCAPTCHA.

#### Scenario: Bloco de contato do tema cetec
- **WHEN** um visitante abre uma página com o bloco de contato num site com chaves
- **THEN** o aviso com os dois links aparece abaixo do botão de envio e o selo flutuante não aparece
