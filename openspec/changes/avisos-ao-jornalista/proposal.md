# Proposta: avisos-ao-jornalista

## Why

O Jonas pediu em 10/11/2026, para o portal da AJoPE (https://ajope.wab.com.br):

- que o `/admin` mostre os jornalistas aguardando aprovação e os aprovados;
- que o jornalista receba um e-mail quando o administrador muda a situação dele; o e-mail de aprovação deve trazer o link para entrar no site e publicar;
- que todo cadastro novo nasça inativo e mostre, ao terminar, que está em análise e que a aprovação será avisada por e-mail.

Hoje o cadastro já nasce pendente, e a lista existe em `/admin/jornalistas`, com filtro por situação. Mas:

- ninguém recebe e-mail;
- a mensagem de cadastro recebido não fala em análise nem em e-mail;
- o painel inicial do `/admin` não mostra os jornalistas.

## What Changes

- **Mensagem ao terminar o cadastro:** "Cadastro recebido! Ele está em análise pela administração do portal. Quando for aprovado, você receberá um e-mail com o link para entrar e publicar." O cadastro continua nascendo pendente (inativo).
- **E-mail ao jornalista a cada mudança de situação feita no `/admin/jornalistas`:**
  - **aprovado:** o link de login do site, o e-mail que serve de login e a explicação de que, depois de entrar, ele publica pela área do jornalista;
  - **reativado** (de suspenso para aprovado): o mesmo link;
  - **recusado** e **suspenso:** só o aviso.

  O remetente é o `EMAIL_FROM` do servidor, com o nome do site. Se o envio falhar, a situação muda do mesmo jeito, e o painel avisa que o e-mail não saiu.
- **Painel inicial do `/admin` nos sites do tema `noticias`:** para o admin do site e o SuperAdmin, entra o quadro "Jornalistas", com os pendentes (mais recentes primeiro, até 10, com link para a ficha), o número de aprovados e o link para a lista completa.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `noticias`: entram requisitos novos sobre o aviso de cadastro em análise, os e-mails de mudança de situação e o quadro de jornalistas no painel. A mensagem que o `cadastro-de-jornalista-ampliado` tira do requisito do cadastro passa a ser esta.

## Impact

- **Código:** serviço novo `JournalistNotifier`; `AdminAuthorController::action()` chama o serviço; `JournalistSignupController::SUCCESS_MESSAGE`; `AdminContentController::dashboard()` e `templates/admin/dashboard.html.twig`; template de e-mail `templates/email/journalist_status.html.twig`.
- **Infra:** usa o Mailer já configurado em produção (WMailer, desde 09/11/2026). Não há fila, então o envio acontece na hora.
- **Sem migration.**
