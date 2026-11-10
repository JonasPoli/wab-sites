# Spec Delta

## ADDED Requirements

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

## REMOVED Requirements

### Requirement: Contato sem anti-spam
**Reason**: Os sites com chave do reCAPTCHA passam a recusar contatos sem token válido. Só um tenant sem chaves continua sem anti-spam, como descrito em "Site sem chave do reCAPTCHA".
**Migration**: Cadastrar as chaves de cada tenant no `/superadmin`. Integrações que postam em `/contato` sem passar pela página deixam de gravar contato nos sites com chave.
