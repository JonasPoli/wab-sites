# Proposal

## Why

O formulário público de contato recebe spam de robôs em sites que não têm chaves do reCAPTCHA: em imperiodainformatica.com.br chegaram, só entre 10 e 13/11/2026, mensagens de "RobertFlact", "Compare_nuKr" (e-mail em `toyota-remont.ru`) e "Luiza Barbjoireza", todas pelo formulário e todas encaminhadas por e-mail para suporte@wab.com.br. Hoje a única barreira para quem lê a página é o token CSRF, que qualquer robô copia do HTML, e a defesa que existe (reCAPTCHA v3 por tenant) só funciona em quem cadastrou as duas chaves do Google — nenhum dos sites em produção cadastrou.

## What Changes

- Todo formulário de contato dos temas passa a trazer, junto do bloco do reCAPTCHA que já existe, um campo invisível (honeypot) e um campo com a hora de abertura da página assinada pelo servidor.
- O contato ganha quatro conferências novas no servidor, aplicadas em todos os tenants e independentes de chave do Google: honeypot preenchido, envio em menos de 3 segundos (ou campo de hora ausente/adulterado), mais de 3 envios por hora do mesmo IP, e mensagem com 4 ou mais links, com BBCode `[url=`/`<a href` ou escrita em alfabeto cirílico.
- Envio bloqueado não gera e-mail e não vira mensagem nova no painel: ele é gravado em `contact_message` com o motivo do bloqueio, fica fora da lista e da contagem de não lidos do `/admin/contact` e aparece só por um filtro próprio na tela. O visitante continua vendo "Mensagem enviada com sucesso!", para o robô não descobrir qual regra o pegou.
- Cada contato passa a guardar o domínio de onde o envio saiu, que aparece no e-mail de contato e na tela do painel — hoje, com um e-mail de destino comum a vários sites (`EMAIL_CONTACT_TO`), não dá para saber de qual site veio a mensagem sem olhar o nome do tenant no assunto.
- A newsletter (`/newsletter/subscribe`) fica fora desta mudança: ela não envia e-mail e não tem relato de spam; segue sem CSRF e sem as conferências novas.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `contato-e-newsletter`: ganha as regras de antispam do contato sem chave do Google (honeypot, tempo mínimo, limite por IP, conteúdo da mensagem), a regra do envio bloqueado (gravado com motivo, sem e-mail, fora da lista e da contagem do painel, visível por filtro) e a mudança no registro, no e-mail e no painel para levar o domínio de origem.

## Impact

- `src/Controller/pub/WabPublicController.php`: `contactPost()` chama o antispam depois do CSRF, dos campos obrigatórios e do reCAPTCHA, e grava o domínio de origem.
- Serviço novo `src/Service/ContatoAntispam.php` com as quatro conferências e a assinatura do campo de hora (HMAC com `APP_SECRET`), usando `cache.app` (filesystem, já configurado) para o limite por IP.
- `src/Entity/ContactMessage.php` + migration: colunas `spam_reason` e `source_domain`, as duas nulas. `ContactMessageRepository::countUnread()` e `/admin/contact` passam a ignorar os bloqueados.
- `templates/pub/_recaptcha.html.twig` passa a ser o parcial de proteção do contato (campos novos + reCAPTCHA), incluído nos 7 formulários de contato (`pub/main/home`, temas `cetec`, `moderno` e `wab`, cada um em `home` e em `blocks/_contact`).
- `templates/email/contact.html.twig` e `templates/admin/contact/index.html.twig`: domínio de origem; a tela ganha o filtro de bloqueados.
- Sem dependência nova no composer e sem chave de terceiro. Deploy pede `build.sh` (muda template) e `doctrine:migrations:migrate`.
