# Tasks

## 1. Pré-requisitos

- [x] 1.1 Conferir que `isolamento-entre-sites`, `newsletter-csv-e-rotas-de-tema` e `portal-de-noticias` estão na `main` e publicados, e rodar `git pull`. Verificar: `git log --oneline -10` mostra os commits dos três, e o HEAD do servidor é igual ao da `main`. (10/11/2026: os três changes estão na `main` e no ar desde o deploy de 10/11 (servidor em cc18e2b, que os contém))

## 2. Verificação no servidor

- [x] 2.1 Rodar `bin/console debug:router pub_contact_post --show-controllers` e anotar aqui qual controller atende o `POST /contato`. Verificar: a anotação está nesta linha. (10/11/2026: `App\Controller\pub\WabPublicController::contactPost()` atende o `POST /contato`; a duplicata do `MainController` ficou como está)
- [x] 2.2 Acrescentar `recaptchaSiteKey`, `recaptchaSecretKey` e `hasRecaptcha()` em `Tenant`, gerar a migration com `doctrine:migrations:diff` e aplicá-la localmente. Verificar: a migration só cria as duas colunas anuláveis em `tenant`, e `doctrine:migrations:status` não tem nada pendente. (10/11/2026: `Version20261110210740` só com as duas colunas anuláveis em `tenant`; migrations locais sem pendência)
- [x] 2.3 Acrescentar `RECAPTCHA_SCORE_MIN=0.5` ao `.env` e criar `App\Service\RecaptchaVerifier` com os resultados `ACCEPTED`, `REJECTED` e `UNAVAILABLE` (design, decisão 2). Verificar com um script descartável de `MockHttpClient`, fora de `tests/`, com os casos: (10/11/2026, `php -r` com `MockHttpClient`: nota 0,9 → accepted; nota 0,1, ação errada, `timeout-or-duplicate` e token vazio → rejected; `invalid-input-secret`, exceção de transporte, corpo HTML e HTTP 500 → unavailable)
  - nota 0,9 → `ACCEPTED`;
  - nota 0,1 → `REJECTED`;
  - ação errada → `REJECTED`;
  - `timeout-or-duplicate` → `REJECTED`;
  - `invalid-input-secret` → `UNAVAILABLE`;
  - exceção de transporte → `UNAVAILABLE`;
  - corpo HTML → `UNAVAILABLE`.
- [x] 2.4 No controller da 2.1, depois do CSRF e dos campos e antes do `persist`: (10/11/2026: conferido pela 4.2)
  - conferir `g-recaptcha-response` com a ação `contato`, usando o tenant do `TenantContext`;
  - com `REJECTED`, flash "Não foi possível confirmar o envio. Tente de novo em alguns segundos." e redirect para `/#contato`;
  - com `UNAVAILABLE` ou sem chaves, seguir como hoje, com aviso no log com o domínio.
  - Verificar pela 4.2.

## 3. Superadmin, exportação e importação

- [x] 3.1 Em `superadmin/tenant/new` e `edit` e em `populateTenantFromRequest()`, criar a seção do reCAPTCHA: secret key nunca exibida, campo vazio mantém a atual, caixa para remover as duas. Verificar em localhost:8000 como `superadmin` com três salvamentos: (10/11/2026, `superadmin` local no tenant 11: gravou as duas chaves; salvar outra mudança com a secret em branco manteve a secret, e o HTML não tem o valor (só a etiqueta "configurada"); "Remover as chaves do reCAPTCHA" deixou as duas nulas, também no tenant 10)
  - gravar as chaves;
  - salvar outra mudança com a secret em branco: a chave continua, e a página não mostra o valor no HTML;
  - marcar "Remover": as duas ficam nulas.
- [x] 3.2 No painel do `/superadmin`, pôr a etiqueta "sem reCAPTCHA" nos tenants sem as duas chaves. Verificar com dois tenants locais, um com chaves e outro sem. (10/11/2026: no painel, "sem reCAPTCHA" em 127.0.0.1, noticias.localhost, noticias2 e noticias3, e não em localhost, que tinha as chaves)
- [x] 3.3 No `TenantExporter`, exportar só a site key; no `TenantImporter`, ler só a site key. Verificar: exportar um tenant local com chaves → `unzip -p <zip> metadata.json | grep -c recaptchaSecretKey` dá 0 → importar com outro domínio → o tenant novo tem a site key e não tem secret key. (10/11/2026: o ZIP do tenant 11 não tem `recaptchaSecretKey` e tem a site key, em `serializeTenant()` e em `Tenant::toArray()`; importado como `recaptcha.localhost` (tenant 16), ficou com a site key e sem a secret)
- [x] 3.4 Conferir que o `/admin/settings` do cliente não mostra nem grava as chaves. Verificar: `grep -n recaptcha templates/admin -r` não encontra nada. (10/11/2026: `grep -rln recaptcha templates/admin` não acha nada)

## 4. Front

- [x] 4.1 Criar `templates/pub/_recaptcha.html.twig`, com o campo escondido, o aviso com os dois links, o CSS do selo e o script de execução única (design, decisão 4), e incluí-lo nos sete formulários que postam em `pub_contact_post`, só com `tenant.hasRecaptcha()`. Verificar: `grep -rn "pub_contact_post" templates | wc -l` e `grep -rln "_recaptcha.html.twig" templates | wc -l` dão 7 e 7. (10/11/2026: 7 formulários com `pub_contact_post` e 7 arquivos incluindo `pub/_recaptcha.html.twig`)
- [x] 4.2 Conferência local com a chave de teste de `localhost`, num tenant local com o tema cetec: (10/11/2026, tenant local `localhost` no tema cetec, com chaves de teste (a site key falsa não gera token): (1) a home sem tocar no formulário não pede nada a `google.com/recaptcha`, e o foco no formulário carrega `api.js?render=…`; (2) contato normal com token aceito: conferido só pelo `MockHttpClient` (2.3), porque não há chave v3 real para `localhost`; (3) o formulário aberto por 3 minutos depende do token real e fica para a 6.2; (4) o envio sem token (pelo navegador, depois da falha do `execute`, e por HTTP com o CSRF da página) é recusado com o aviso, e nada é gravado; (5) com token falso, o Google respondeu `invalid-input-response`, e o contato foi recusado; o caso `invalid-input-secret` → aceito foi conferido pelo `MockHttpClient` e fica para a 6.2 com a chave real; (6) o site sem chaves (127.0.0.1) gravou o contato, com "aceito sem conferência" no log e nenhum `recaptcha` no HTML)
  - abrir a home sem tocar no formulário: nenhuma requisição a `google.com/recaptcha` na aba Rede;
  - contato normal: gravado;
  - formulário aberto por 3 minutos antes de enviar: gravado;
  - `curl` com o CSRF lido da página e sem token: nada gravado, com o aviso;
  - secret key trocada por um valor inválido: gravado, com aviso no log;
  - tenant sem chaves: gravado e sem `recaptcha` no HTML.
  - Anotar aqui os seis resultados.
- [x] 4.3 Repetir o contato normal e o sem token num tenant local com o tema moderno e noutro com o tema wab. Verificar: os dois tenants dão os mesmos resultados da 4.2. (10/11/2026: o moderno (localhost) e o wab (127.0.0.1) carregam o script só no foco e recusam o envio sem token sem gravar nada. No wab, o aviso não aparece na tela porque o tema mostra só os avisos `contact_s` e `contact_f`, e o controller usa `error`; isso já acontecia antes, e nenhum site em produção usa o wab)

## 5. Publicação

- [x] 5.1 Commit e `git push origin main`. Verificar: `git log --oneline origin/main -1` mostra o commit. (10/11/2026: 1ec2bc7 em origin/main)
- [x] 5.2 Backup antes (leitura, depois cópia no servidor, como runcloud): (10/11/2026 21:22 UTC: HEAD cc18e2b em `~/backups/wab-sites-HEAD-antes-20261110-2122.txt` e dump `~/backups/wab-sites-antes-deploy-20261110-2122.sql.gz` (chmod 600, com nice, 20 tabelas))
  - anotar o `git rev-parse HEAD` de `~/webapps/app-wab-sites`;
  - fazer o dump do banco fora do docroot (`~/backups`, chmod 600), com `nice`.
  - Verificar: o arquivo existe e tem tamanho maior que zero.
- [x] 5.3 Deploy (escrita, com aprovação do Jonas): `git pull --ff-only`, `/RunCloud/Packages/php83rc/bin/php bin/console doctrine:migrations:migrate --no-interaction` e `cache:clear`. Verificar: `doctrine:migrations:status` sem pendência e o HEAD igual ao da `main`. (10/11/2026 21:22 UTC, com a autorização do Jonas ("pode fazer"): HEAD cc18e2b em `~/backups/wab-sites-HEAD-antes-20261110-2122.txt` e dump `wab-sites-antes-deploy-20261110-2122.sql.gz` (20 tabelas); `git pull` até 1ec2bc7, migrations Version20261110205446 e Version20261110210740 e `./build.sh`; HTTP 200 nos 8 domínios de cliente e no ajope; foi usado o `./build.sh` em vez do `cache:clear`, porque mudaram templates de outros changes no mesmo deploy)
- [ ] 5.4 Conferência depois: (10/11/2026: HTTP 200 nos 8 domínios e nenhum `recaptcha` no HTML das 8 homes. Pendente: o Jonas ver no `/superadmin` os 8 marcados "sem reCAPTCHA", porque não há sessão de SuperAdmin de produção nesta sessão)
  - HTTP 200 nos 8 domínios de cliente;
  - painel do `/superadmin` com os 8 marcados "sem reCAPTCHA";
  - nenhum `recaptcha` no HTML das homes.

## 6. Chaves dos sites

- [ ] 6.1 O Jonas cria as chaves v3 no console do reCAPTCHA. A ordem sugerida é imperiodainformatica.com.br, cemporcentoexpresso.com.br e marapossi.com.br, depois os outros cinco. Verificar: o Jonas confirma no chat, sem colar os valores.
- [ ] 6.2 Cadastrar as chaves pelo `/superadmin` (escrita pela tela) e, em cada site, conferir três coisas:
  - um contato com "[Teste WAB]" no nome é gravado;
  - um POST sem token não grava (contagem de `contact_message` igual antes e depois);
  - o aviso aparece abaixo do formulário.
  - Anotar aqui os sites conferidos.
- [ ] 6.3 Depois de 7 dias, comparar a contagem de contatos por site com a de 10/11/2026: imperiodainformatica 55, cemporcentoexpresso 34 e marapossi 30 em 30 dias. Anotar o resultado aqui.
