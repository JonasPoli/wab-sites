# Tarefas: avisos-ao-jornalista

## 1. Mensagem e e-mails

- [x] 1.1 Trocar o `SUCCESS_MESSAGE` do cadastro pela mensagem de cadastro em análise. Conferir em `noticias.localhost/jornalista/cadastro` com um envio completo: a mensagem aparece e o autor fica pendente. (10/11/2026: envio completo pelo navegador mostra "Cadastro recebido! Ele está em análise pela administração do portal. Quando for aprovado, você receberá um e-mail com o link para entrar e publicar." e o autor fica pendente)
- [x] 1.2 Criar o `JournalistNotifier` e o `email/journalist_status.html.twig` (decisões 1 e 3) e chamar o serviço no `AdminAuthorController::action()`, com o aviso de falha (decisão 4). Conferir com um SMTP de teste local, pela tela do `/admin`: (10/11/2026, SMTP de teste local (`python -m smtpd`), ações pela tela da ficha como `noticias.admin`: aprovado → "[AJoPE — Local] Seu cadastro de jornalista foi aprovado", com o botão e o endereço `http://noticias.localhost:8101/login` e o e-mail de login; suspenso e reativado → dois e-mails, o de reativação com o link; recusado → sem link; remetente com o nome do site. Com o SMTP desligado: o jornalista ficou suspenso e o painel mostrou "Jornalista suspenso, mas o e-mail de aviso não pôde ser enviado." A candidata aprovada entrou e foi para `/jornalista`. Ajuste feito no caminho: o `EMAIL_FROM` local vem como "Nome <endereço>", então o remetente passou a usar só o endereço, lido com `Address::create()`)
  - aprovar o pendente da 1.1 → e-mail com assunto `[AJoPE — Local] Seu cadastro de jornalista foi aprovado`, link `http://noticias.localhost:8101/login` e o e-mail de login;
  - suspender e reativar → dois e-mails, e o de reativação tem o link;
  - recusar outro pendente → e-mail sem link;
  - com o SMTP desligado, aprovar → situação aprovada e o aviso de que o e-mail não saiu.

## 2. Painel

- [x] 2.1 Quadro "Jornalistas" no painel inicial (decisão 5). Conferir em `noticias.localhost/admin`: (10/11/2026: `noticias.admin` vê o quadro com "1 aguardando", "6 aprovados", o pendente com "Ver ficha e aprovar" e o "Ver todos"; `noticias.editor` e o admin de `127.0.0.1` (tema wab) não veem o quadro)
  - como `noticias.admin`, os pendentes com link para a ficha e o número de aprovados;
  - como `noticias.editor`, sem o quadro;
  - em `127.0.0.1/admin` (tema wab), sem o quadro.

## 3. Produção

- [x] 3.1 Deploy junto com os outros changes (ver `cadastro-de-jornalista-ampliado` 3.1). (10/11/2026 19:53 UTC, com a autorização do Jonas ("crie… implemente… publique"): e2e2593 no ar; backup `~/backups/wab-sites-HEAD-antes-20261110-1952.txt` (HEAD 2cdddf5) e `wab-sites-antes-deploy-20261110-1952.sql.gz` (20 tabelas); `git pull`, migration Version20261110194600 e `./build.sh` com nice; HTTP 200 nos 8 domínios de cliente e no ajope)
- [ ] 3.2 Conferência no ajope (leitura): o `/admin` mostra o quadro (a conferir com o Jonas logado), e `/jornalista/cadastro` responde 200. (10/11/2026: `/jornalista/cadastro` e `/login` do ajope 200; o quadro do `/admin` pede login de admin do site, e o Jonas confere)
- [ ] 3.3 Teste de ponta a ponta em produção, com o OK do Jonas, porque manda e-mail de verdade: cadastro de teste com um e-mail da WAB, aprovação pelo `/admin`, chegada do e-mail com o link, login e exclusão do cadastro de teste. (pendente: manda e-mail de verdade; depende do OK do Jonas e de um e-mail da WAB para o teste)

## Workflow follow-up

- Arquivar depois do `portal-de-noticias` e do `cadastro-de-jornalista-ampliado`.
