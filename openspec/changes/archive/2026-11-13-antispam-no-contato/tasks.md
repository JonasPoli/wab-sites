# Tasks

## 1. Serviço de antispam

- [x] 1.1 Criar `src/Service/ContatoAntispam.php` com `verificar(Tenant $tenant, Request $request, string $nome, string $mensagem): ?string` (devolve `null`, `isca`, `tempo`, `limite_ip` ou `conteudo`), `assinarHora(): string` para o parcial e `registrarAceito(?string $ip): void` para o contador; injetar `CacheInterface`, `LoggerInterface` e o `APP_SECRET` por `#[Autowire('%kernel.secret%')]`, e verificar com `bin/console lint:container`
- [x] 1.2 Implementar a isca (`wab_website` com qualquer conteúdo, inclusive espaços) e a hora assinada (`wab_t` = `<timestamp>.<hmac sha256 truncado em 16>`; ausente, assinatura errada, hora no futuro ou menos de 3 s reprovam; hora antiga passa)
- [x] 1.3 Implementar o limite por IP no `cache.app` (`antispam_ip_<sha1(ip)>`, TTL 3600 no primeiro aceito, máximo 3; sem IP não limita) e as regras de conteúdo (4 ou mais links contando `http://`, `https://` e `www.`; qualquer `[url=`, `[link=` ou `<a href`; `\p{Cyrillic}` no nome ou na mensagem)
- [x] 1.4 Registrar no log cada bloqueio com domínio, motivo e IP e nada do que o visitante digitou, no mesmo estilo do `RecaptchaVerifier`
- [x] 1.5 Criar `tests/Service/ContatoAntispamTest.php` cobrindo os cenários da spec (isca com espaço, envio em 1 s, POST sem `wab_t`, hora adulterada, hora de 2 h atrás, 4º envio do IP, bloqueado não gasta limite, 5 links, BBCode, cirílico, um link só) e verificar com `vendor/bin/phpunit --filter ContatoAntispam` passando

## 2. Banco e entidade

- [x] 2.1 Adicionar em `src/Entity/ContactMessage.php` as propriedades `spamReason` (string 50, nula) e `sourceDomain` (string 255, nula) com get/set, e verificar com `bin/console doctrine:schema:validate --skip-sync` sem erro de mapeamento
- [x] 2.2 Gerar a migration com `bin/console make:migration`, conferir que ela só adiciona as duas colunas nulas em `contact_message` (e que o `down` as remove) e aplicar no banco local com `doctrine:migrations:migrate`
- [x] 2.3 Pôr `AND c.spamReason IS NULL` em `ContactMessageRepository::countUnread()` e acrescentar um método de listagem por situação (aceitos / bloqueados) e a contagem de bloqueados; verificar abrindo o painel local com um registro de cada tipo

## 3. Campos no formulário

- [x] 3.1 Renomear `templates/pub/_recaptcha.html.twig` para `templates/pub/_contato_protecao.html.twig` e trocar o include nos 7 formulários (`pub/main/home`, `themes/cetec/home`, `themes/cetec/blocks/_contact`, `themes/moderno/home`, `themes/moderno/blocks/_contact`, `themes/wab/home`, `themes/wab/blocks/_contact`); verificar com `grep -rn "_recaptcha.html.twig" templates` sem resultado
- [x] 3.2 No parcial, renderizar sempre (fora do `if` do reCAPTCHA) a isca `wab_website` e o `wab_t`: input de texto com rótulo escondido, `tabindex="-1"`, `aria-hidden="true"`, `autocomplete="off"` e CSS `position:absolute;left:-9999px;opacity:0` (sem `display:none` e sem `type="hidden"`), mantendo o bloco do reCAPTCHA como está; verificar no HTML de uma página local que os dois campos aparecem e não são vistos nem focados
- [x] 3.3 Conferir os 7 formulários com o servidor local: cada página de contato trazendo os dois campos (`curl` + `grep wab_t`)

## 4. Contato: controller, e-mail e painel

- [x] 4.1 Em `WabPublicController::contactPost()`, chamar o antispam depois do CSRF e dos obrigatórios e antes do reCAPTCHA; no bloqueio, gravar o `ContactMessage` com `spamReason`, `sourceDomain` e `isRead = true`, não enviar e-mail e devolver o mesmo flash de sucesso; no aceito, gravar `sourceDomain`, chamar `registrarAceito()` e seguir o fluxo atual
- [x] 4.2 Mostrar o domínio de origem no e-mail em `templates/email/contact.html.twig` e verificar com um envio local (MAILER_DSN de teste ou o `Mailer` do profiler) que o corpo traz o domínio
- [x] 4.3 Deixar o `/admin/contact` listando só os aceitos, com `?bloqueados=1` para ver os bloqueados (coluna de motivo), o aviso com a quantidade de bloqueados e o link entre as duas visões; a exclusão continua valendo nas duas
- [x] 4.4 Mostrar na tela do painel o domínio de origem de cada contato aceito
- [x] 4.5 Criar/estender teste funcional em `tests/Controller/` para o POST `/contato`: envio bloqueado pela isca não gera `ContactMessage` sem `spamReason` nem e-mail e devolve o flash de sucesso; envio válido grava com `sourceDomain`; verificar com `vendor/bin/phpunit` passando (cache:warmup --env=test com APP_DEBUG=0 antes, por causa do liip)

## 5. Verificação de ponta a ponta

- [x] 5.1 No ambiente local, enviar um contato de verdade (mais de 3 s) e conferir: mensagem nova no painel com o domínio, e-mail com o domínio, contador de não lidos subindo
- [x] 5.2 Simular os quatro bloqueios por `curl` (isca preenchida, POST sem `wab_t`, 4 envios do mesmo IP, mensagem com 5 links) e conferir que nenhum gerou e-mail, que todos aparecem só no filtro de bloqueados com o motivo certo e que a resposta foi a de sucesso
- [x] 5.3 Rodar `vendor/bin/phpunit` inteiro e `openspec validate antispam-no-contato --strict`

## Workflow follow-up

- Deploy em produção (só com aprovação do Jonas): `git pull`, `./build.sh`, `bin/console doctrine:migrations:migrate` no servidor wabsites; depois conferir no HTML de imperiodainformatica.com.br que os campos chegaram e enviar um contato de teste.
- Acompanhar por alguns dias o filtro de bloqueados do Império: confirmar que o spam está sendo pego e que não há mensagem de cliente de verdade na lista.
- Arquivar a mudança (sincronizando as specs principais) depois do deploy conferido.
