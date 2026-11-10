# Design

## Context

- **Rota de contato:** o `POST /contato` (`pub_contact_post`) é declarado duas vezes, no `pub\MainController` e no `pub\WabPublicController`. O spec atual descreve o `WabPublicController::contactPost()`.
  - Ele confere o CSRF `contact_form`, os campos, grava em `ContactMessage` com o tenant e manda o e-mail pelo WMailer.
  - Os erros voltam por flash para `/#contato`.
- **Formulários:** sete templates postam em `pub_contact_post`:
  - `pub/main/home.html.twig`;
  - `themes/{cetec,moderno,wab}/home.html.twig`;
  - `themes/{cetec,moderno,wab}/blocks/_contact.html.twig`.
  - Todos os tenants reais usam o tema cetec (ver o `context`).
- **Cadastro de tenant:** o `/superadmin` grava os campos à mão em `populateTenantFromRequest()`, sem Symfony Form. A exportação e a importação listam os campos um a um em `TenantExporter` e `TenantImporter`.
- **Chaves por site:** o Jonas escolheu chaves por site (10/11/2026). Uma chave v3 do Google aceita uma lista de domínios, então a mesma chave pode ser colada em mais de um tenant se a WAB preferir.
- **HTTP externo:** o `symfony/http-client` já está instalado.
- **Testes:** os testes em `tests/` estão desatualizados (ver as `rules`). A conferência é local, com as fixtures, em localhost e 127.0.0.1.
- **Modelo da WAB:** Mac Lub e Siatec já usam o reCAPTCHA v3 sob demanda (`maclub-2024`: `RecaptchaVerifier.php` e `recaptcha_lazy.html.twig`). O `procordis-site` tem um change irmão (`recaptcha-no-contato`) com as mesmas regras de recusa e indisponibilidade.

## Goals / Non-Goals

**Goals:**
- Barrar o spam automático nos sites que têm chave, sem custo no carregamento das páginas.
- Isolamento por tenant: a chave conferida é sempre a do tenant do domínio (`TenantContext`), nunca a de outro site.

**Non-Goals:**
- Newsletter, limite por IP, campo-isca e chave global de reserva.
- Limpar os contatos de spam já gravados. Pode ser feito à parte pelo `/admin/contact` de cada site.

## Decisions

### 1. Chaves no tenant, sem chave global
- `Tenant` ganha `recaptchaSiteKey` (string 100, anulável) e `recaptchaSecretKey` (string 100, anulável), mais `hasRecaptcha()`, que só é verdadeiro com as duas preenchidas. A migration sai por `doctrine:migrations:diff`.
- Não há chave global no `.env`. Uma chave que não lista o domínio faz o `grecaptcha.execute` falhar no navegador, e o contato seria recusado por falta de token. Uma reserva global errada bloquearia os contatos reais em silêncio.
- Sem chave no tenant, o site fica como hoje e aparece marcado no painel (decisão 6).
- **Isolamento:** `Tenant` não passa pelo filtro de tenant. A chave usada é a do `TenantContext::requireTenant()` do pedido, nunca uma vinda do formulário.

### 2. Serviço `App\Service\RecaptchaVerifier`
- `verify(Tenant $tenant, ?string $token, string $expectedAction, ?string $ip): RecaptchaResult`, com três resultados: `ACCEPTED`, `REJECTED` e `UNAVAILABLE`. A nota mínima vem de `RECAPTCHA_SCORE_MIN` (`.env`, padrão 0.5).
- **`REJECTED`:**
  - token vazio;
  - `success: false` com `invalid-input-response` ou `timeout-or-duplicate`;
  - ação diferente;
  - nota abaixo da mínima.
- **`UNAVAILABLE`:**
  - exceção de transporte ou timeout (5 s);
  - HTTP diferente de 200;
  - corpo que não é JSON;
  - `invalid-input-secret`, `missing-input-secret` ou `bad-request`.
- **Log:** `warning` com o domínio do tenant, a ação, a nota, os códigos de erro e o IP. Nunca nome, e-mail, telefone ou mensagem.
- **Alternativa descartada:** o `karser/recaptcha3-bundle` (nepe-search). Ele lê a chave da configuração global, não do tenant, e não separa recusa de indisponibilidade.

### 3. Controller
- Antes de mexer, rodar `bin/console debug:router pub_contact_post --show-controllers` para confirmar qual controller atende.
  - A conferência entra no que atende (esperado: `WabPublicController`).
  - O outro fica como está. Remover a rota duplicada não faz parte deste change, e a tarefa anota o que encontrou.
- No `contactPost()`, depois do CSRF e dos campos e antes do `persist`, ler `g-recaptcha-response` do POST.
  - Com o tenant com chaves e `REJECTED`: flash `error` com o texto da spec e redirect para `/#contato`, como nos outros erros desse formulário.
  - Com `UNAVAILABLE` ou tenant sem chaves: seguir como hoje, com aviso no log.

### 4. Front: componente sob demanda
- Um componente `templates/pub/_recaptcha.html.twig` (fora dos temas, para valer nos três) é incluído no fim de cada um dos sete formulários, só com `tenant.hasRecaptcha()`. Ele traz:
  - o `<input type="hidden" name="g-recaptcha-response">`;
  - o aviso com os links `https://policies.google.com/privacy` e `https://policies.google.com/terms`;
  - o CSS que esconde o `.grecaptcha-badge`;
  - um `<script>` que só roda uma vez por página, mesmo com dois formulários.
- **O script:**
  - No primeiro `focusin` de qualquer `form[action$="/contato"]`, carrega `https://www.google.com/recaptcha/api.js?render=<site key>`.
  - No `submit`, intercepta, pede `grecaptcha.execute(siteKey, {action: 'contato'})`, grava o token no campo e reenvia uma vez com `form.submit()`.
  - Se o script falhar ou passar de 8 s, reenvia sem token.
- O token é pedido no envio, e não no `focusin` como na Mac Lub, porque vence em 2 minutos.
- **Temas:** o componente vale nos três (cetec, moderno e wab) e no `pub/main/home`. A conferência visual é no cetec, o único em uso, e nos outros dois com um tenant local de teste.

### 5. Superadmin, exportação e importação
- **`new` e `edit`:** uma seção "reCAPTCHA (formulário de contato)" com:
  - a site key em texto;
  - a secret key em `type="password"`, sempre vazia na tela, com o rótulo "configurada" quando existe;
  - uma caixa "Remover as chaves do reCAPTCHA".
- **`populateTenantFromRequest()`:**
  - grava a site key;
  - troca a secret key só quando o campo vem preenchido;
  - limpa as duas com a caixa marcada.
- **Exportação e importação:** o `TenantExporter` exporta `recaptchaSiteKey` e nunca `recaptchaSecretKey`. O `TenantImporter` lê só a site key.
- O `/admin/settings` do cliente não muda.

### 6. Indicação no painel
- Na lista de tenants do `/superadmin`, uma etiqueta "sem reCAPTCHA" nos tenants com `hasRecaptcha()` falso.

### 7. Chaves dos 8 sites
- A WAB cria no console do reCAPTCHA (conta Google da WAB) uma chave v3 por domínio de tenant. Para os `site.*`, o domínio-raiz cobre os subdomínios.
- **Ordem sugerida pelo volume de spam de 10/11/2026:** imperiodainformatica.com.br, cemporcentoexpresso.com.br e marapossi.com.br primeiro; depois os outros cinco.
- As chaves são coladas pelo `/superadmin` depois do deploy. É escrita no banco pela tela, sem SQL.

## Risks / Trade-offs

- [Visitante real com nota baixa ou bloqueador que barra o Google] → Ele vê o aviso para tentar de novo e perde o texto digitado, porque o redirect é o comportamento atual dos erros. Se aparecerem recusas de gente real no log, a nota mínima baixa pelo `RECAPTCHA_SCORE_MIN` sem deploy de código.
- [Secret key errada colada no `/superadmin`] → Vira `UNAVAILABLE`: o contato passa e o log avisa com o domínio. A conferência pós-cadastro envia um contato de teste por site.
- [Site key de outro domínio colada no tenant] → O `execute` falha no navegador, sai sem token e todo contato é recusado. A conferência pós-cadastro (tarefa 6.2) pega isso no primeiro teste. A volta é a caixa "Remover as chaves".
- [Rota duplicada `pub_contact_post`] → Se o controller ativo mudar com a ordem de carregamento das rotas, a conferência some. A tarefa 2.1 registra qual atende, e um change futuro pode remover a duplicata.
- [Logs no stderr do FPM, ilegíveis para o runcloud] → A conferência usa o resultado visível (gravou ou não gravou, contagem em `contact_message`).

## Migration Plan

1. **Deploy** (escrita, com aprovação do Jonas):
   - backup do banco (dump fora do docroot) e HEAD anotado;
   - no servidor, como runcloud, `git pull`, `doctrine:migrations:migrate --no-interaction` e `cache:clear`.
   - Sem chaves cadastradas, nada muda para os visitantes.
2. **Conferência:**
   - os 8 domínios com HTTP 200;
   - o painel com os 8 tenants marcados "sem reCAPTCHA";
   - um contato de teste num site sem chave gravado como antes.
3. **Chaves:**
   - o Jonas cria as chaves e as cola site a site pelo `/superadmin`;
   - em cada site, um contato de teste com "[Teste WAB]" no nome é gravado;
   - um POST sem token não grava (contagem de `contact_message` sem mudança).
4. **Volta:**
   - por site, a caixa "Remover as chaves";
   - para todos, `git checkout <HEAD anterior>` e `cache:clear`. As colunas novas são anuláveis e podem ficar.
