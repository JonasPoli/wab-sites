## ADDED Requirements

### Requirement: Aviso de cadastro em análise
Ao concluir o cadastro em `/jornalista/cadastro`, o candidato SHALL ficar pendente (sem acesso) e a página SHALL mostrar "Cadastro recebido! Ele está em análise pela administração do portal. Quando for aprovado, você receberá um e-mail com o link para entrar e publicar."

#### Scenario: Cadastro concluído
- **WHEN** uma jornalista envia o cadastro completo
- **THEN** ela fica pendente e a página mostra a mensagem de cadastro em análise

### Requirement: E-mail ao jornalista na mudança de situação
Quando o admin do site ou o SuperAdmin aprova, recusa, suspende ou reativa um jornalista no `/admin/jornalistas`, o sistema SHALL enviar um e-mail ao endereço do jornalista, com o nome do site no remetente e no assunto. Uma falha no envio MUST NOT impedir a mudança de situação, e o painel SHALL avisar que o e-mail não foi enviado.

#### Scenario: Recusa
- **WHEN** o admin recusa um candidato pendente
- **THEN** o candidato recebe o e-mail de que o cadastro não foi aprovado, sem link de login

#### Scenario: Falha no envio
- **WHEN** o servidor de e-mail não responde no momento da aprovação
- **THEN** o jornalista fica aprovado e o painel mostra "Jornalista aprovado, mas o e-mail de aviso não pôde ser enviado."

### Requirement: Link para publicar no e-mail de aprovação
O e-mail de aprovação e o de reativação SHALL trazer o link absoluto de login do site (`https://<domínio>/login`), o e-mail que serve de login e a informação de que, depois de entrar, o jornalista publica pela área do jornalista.

#### Scenario: Aprovação
- **WHEN** o admin da AJoPE aprova a candidata Ana
- **THEN** Ana recebe o e-mail "[Associação dos Jornalistas Espíritas] Seu cadastro de jornalista foi aprovado" com o link `https://ajope.wab.com.br/login` e o e-mail dela como login

### Requirement: Jornalistas no painel inicial
Nos sites do tema `noticias`, o painel inicial do `/admin` SHALL mostrar ao admin do site e ao SuperAdmin o quadro "Jornalistas" com os candidatos pendentes (até 10, os mais recentes primeiro, cada um com link para a ficha), o número de jornalistas aprovados e o link para `/admin/jornalistas`; sem pendentes, o quadro SHALL dizer "Nenhum cadastro aguardando aprovação". Editor e revisor MUST NOT ver o quadro.

#### Scenario: Dois pendentes
- **WHEN** o portal tem 2 candidatos pendentes e 3 aprovados e o admin abre o `/admin`
- **THEN** o quadro lista os 2 pendentes com link para a ficha e mostra "3 aprovados"

#### Scenario: Editor no painel
- **WHEN** um editor do portal abre o `/admin`
- **THEN** o quadro "Jornalistas" não aparece
