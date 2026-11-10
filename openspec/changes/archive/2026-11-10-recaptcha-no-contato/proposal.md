# Proposal

## Why

O "Fale conosco" dos sites tem só o token CSRF como barreira. O spec `contato-e-newsletter` registra isso de propósito ("Contato sem anti-spam"), e um robô que lê o token da página grava o contato normalmente.

A leitura de produção de 10/11/2026 mostra o efeito. Nos 30 dias anteriores, três sites receberam quase todo o volume:
- imperiodainformatica.com.br: 55 contatos;
- cemporcentoexpresso.com.br: 34;
- marapossi.com.br: 30.

Ao todo, 112 dos 267 contatos gravados têm link na mensagem. Desde 09/11/2026 o contato também vira e-mail pelo WMailer, e o spam chega à caixa dos clientes. O Jonas pediu o reCAPTCHA em 10/11/2026: "recebemos muito spam".

## What Changes

- **reCAPTCHA v3 invisível em todos os formulários** que postam em `/contato`, nos temas cetec, moderno e wab e no `pub/main/home`:
  - o script do Google só carrega quando o visitante entra no formulário;
  - o token é pedido no envio;
  - o servidor confere o token, a ação `contato` e a nota mínima (0,5 por padrão) antes de gravar.
- **Chaves por site:** cada tenant tem a própria chave do reCAPTCHA (site key e secret key), cadastrada pela WAB no `/superadmin`. Os clientes não a veem no `/admin`.
  - A secret key nunca aparece na tela depois de gravada e não vai na exportação do tenant.
  - Um site sem chaves funciona como hoje, sem reCAPTCHA, e o log avisa.
  - O painel do `/superadmin` mostra quais sites estão sem reCAPTCHA.
- **Contato recusado:** não é gravado nem gera e-mail. O visitante volta para `/#contato` com um aviso para tentar de novo. A recusa vai para o log com o tenant, a nota e o IP, sem os dados do visitante.
- **Google fora do ar** (erro de rede, resposta inválida ou secret key errada): o contato é aceito como hoje, com aviso no log.
- **Aviso do Google:** abaixo de cada formulário aparece "Este site é protegido pelo reCAPTCHA..." com os links do Google, e o selo flutuante fica escondido.
- **BREAKING:** sai o requisito "Contato sem anti-spam". Robôs que enviam o formulário com o token CSRF deixam de gravar contato nos sites com chave.

## Capabilities

### New Capabilities
<!-- nenhuma -->

### Modified Capabilities
- `contato-e-newsletter`:
  - sai o requisito "Contato sem anti-spam";
  - entram as regras do reCAPTCHA no contato: aceite, recusa, falha do Google, site sem chave, script sob demanda e aviso.
- `gestao-de-sites`: entram o cadastro das chaves do reCAPTCHA no tenant, com a secret key fora da tela e da exportação, e a indicação no painel dos sites sem reCAPTCHA.

## Impact

- **Banco:** a entidade `Tenant` ganha duas colunas anuláveis, `recaptcha_site_key` e `recaptcha_secret_key`, com migration por `doctrine:migrations:diff`.
- **Código:**
  - `WabPublicController::contactPost()`, que é a rota ativa: a implementação confere com `debug:router`, porque o `MainController` também declara `pub_contact_post`;
  - um serviço novo `App\Service\RecaptchaVerifier`, que usa o `symfony/http-client` já instalado;
  - `SuperAdminController` (`populateTenantFromRequest` e painel);
  - `TenantExporter` e `TenantImporter`;
  - `templates/superadmin/tenant/{new,edit}.html.twig` e `templates/superadmin/dashboard.html.twig`;
  - os 7 formulários de contato dos temas e um componente Twig do carregamento sob demanda.
- **Operação:** a WAB cria uma chave v3 por domínio no console do reCAPTCHA (ou uma chave com vários domínios, colada em vários tenants) e cadastra cada uma no `/superadmin` depois do deploy.
- **Dependência:** este change vem depois do `isolamento-entre-sites`, do `newsletter-csv-e-rotas-de-tema` e do `portal-de-noticias`, que mexem no `SuperAdminController` e nos temas. A implementação parte da `main` com esses três publicados.
- **Fora do escopo:** a newsletter, o limite por IP, o campo-isca e a limpeza dos contatos de spam já gravados.
