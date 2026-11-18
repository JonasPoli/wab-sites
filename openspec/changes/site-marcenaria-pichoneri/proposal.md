# Proposal

## Why

Com os blocos, o blog e o tema `atelie` prontos, falta pôr no ar o novo site da Marcenaria Pichoneri no wab-sites, com todo o conteúdo do site atual (WordPress em `www.marcenariapichoneri.com.br`: uma home e 44 posts) e sem perder o que o Google já indexou. Faltam duas peças de sistema para isso: redirecionar os endereços antigos e trazer os posts do WordPress para o cadastro de notícias.

Mudança 4 de 4. Depende das três anteriores arquivadas e em produção. Inventário do site atual em `referencia/inventario.md`.

## What Changes

- **Redirecionamentos por site**: cadastro de "endereço antigo → endereço novo" no `/admin`, aplicado com 301 somente quando o endereço pedido não existe no site.
- **Importação de posts do WordPress**: comando que lê a API pública de um site WordPress e cria categorias, notícias (com capa, data e resumo) e os redirecionamentos dos endereços antigos, com modo de simulação.
- **Montagem do site da Pichoneri** (conteúdo, não código): tenant novo com tema `atelie`, home no layout aprovado com o conteúdo real, blog com os 44 posts, redirecionamentos, SEO do site.
- **Publicação**: site em endereço provisório para aprovação e, depois, troca do domínio `www.marcenariapichoneri.com.br` para o servidor do wab-sites.

## Capabilities

### New Capabilities

- `redirecionamentos`: endereços antigos de um site respondendo 301 para os novos.
- `importacao-de-wordpress`: trazer posts e categorias de um WordPress para as notícias de um site.

### Modified Capabilities

- `gestao-de-sites`: redirecionamentos na exportação, na importação e na exclusão do site.

## Impact

- Código novo: `src/Entity/Redirect.php` (+ repositório e migration), `src/EventSubscriber/RedirecionamentoSubscriber.php`, rotas e telas `/admin/redirecionamentos`, `src/Command/ImportarWordpressCommand.php` e `src/Service/Wordpress/*`.
- Código alterado: `templates/admin/wab_base.html.twig` (item de menu), `TenantExporter`/`TenantImporter`, exclusão do tenant no `SuperAdminController`.
- Banco: tabela nova `redirect`.
- Produção: tenant novo (o 11º), domínios novos no webapp `app-wab-sites` do RunCloud, SSL, e mudança de DNS do domínio do cliente. Tudo escrita, com aprovação do Jonas a cada passo.
- Cliente: aprovação do site provisório e confirmação dos pontos marcados como "a confirmar" no layout (88 anos, categorias do portfólio, etapas do processo).
