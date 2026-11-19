# Design

## Context

Porte, para a plataforma multi-tenant, do que levou o www.wab.com.br a 100 no isitagentready.com (repositório wab2024, mudanças `site-pronto-para-agentes`, `descoberta-por-agentes` e `autenticacao-e-a2a-para-agentes`; roteiro na skill `agentready-100` do repositório servidores-wab-runcloud). Diferenças em relação ao wab2024: aqui tudo depende do domínio do pedido (TenantContext), não há cadastro de páginas em código (as páginas vêm do banco), há cinco temas e o servidor é outro (wabsites, RunCloud, com root).

## Goals / Non-Goals

**Goals:** os 10 sites a 100; nenhum documento aponta para algo que não funciona; nada novo no banco; isolamento entre tenants em tudo (Markdown, MCP, API, tokens).

**Non-Goals:** contas para agentes, claim do Auth.md, OpenID Connect, streaming e tarefas A2A, escrita por agentes (o contato continua sendo pelo formulário, por uma pessoa).

## Decisions

### D1. Uma fonte de páginas por tenant
O que o `SitemapController` calcula (home, páginas publicadas, categorias com conteúdo, notícias e posts visíveis, autores) vira o serviço `App\Agentes\PaginasDoSite`, usado pelo sitemap, pelo llms.txt, pelo MCP, pela API e pelo A2A. Título e descrição vêm do `PageSeo` já existente quando houver; senão, do nome da entidade.

### D2. Markdown a partir do HTML renderizado
Em vez de um template Markdown por tema, a versão Markdown sai do HTML que o próprio site renderiza: subpedido interno ao caminho, extração do `<main>` (ou do `<body>` sem `header`, `nav`, `footer`, `form`, `script`, `style`, `svg`, `iframe`, `[aria-hidden=true]`), conversão com `league/html-to-markdown`, frontmatter com título, descrição e URL, e um rodapé com os dados de contato do Tenant. Vale para qualquer tema e tipo de página. A negociação por `Accept` troca a resposta HTML pela Markdown no `kernel.response`, só para GET 200 de rota pública.

### D3. Robots da casa para sites de cliente
Busca e assistentes liberados; treino proibido; `Content-Signal: search=yes, ai-input=yes, ai-train=no`. Listas de robôs iguais às da `docs/bots-lista.md` do repositório de servidores.

### D4. OAuth sem banco, com o domínio dentro de tudo
Porte de `src/Oauth` do wab2024. O `issuer` é `https://<domínio do pedido>`; tokens e asserções levam `iss` e `aud` do domínio e são recusados em outro tenant. O `client_id` selado leva o domínio. Uma chave RSA só (em `var/oauth/<ambiente>/`) para todos os tenants; o JWKS é o mesmo, o que não vaza nada.

### D5. Agente A2A por palavras
Sem modelo de linguagem: a pergunta é normalizada e comparada com intenções (contato, endereço, horário, páginas, notícias) e com títulos de páginas do tenant; a resposta é texto com links e uma parte `data`.

### D6. WebMCP em toda página
Parcial `themes/_comum/_webmcp.html.twig`, incluído no `base.html.twig` dos cinco temas, que registra `ler_pagina` (busca o `.md` da página) quando há `document.modelContext` ou `navigator.modelContext`; formulários de contato e a busca do noticias ganham `toolname`/`tooldescription`.

### D7. Servidor e DNS
Nginx: arquivo `/etc/nginx-rc/extra.d/app-wab-sites.location.main-before.descoberta-agentes.conf` com os mesmos blocos exatos do wab2024. DNS: zonas `_agents.<domínio>` fora do DirectAdmin, assinadas pelo BIND (inline-signing), uma por site; DNSSEC das 8 zonas de cliente pelo DirectAdmin, com delegação das subzonas antes de assinar, teste com âncora local (delv), vigia e reassinatura semanal já existentes estendidos a elas; DS no registro.br (5 pelo Jonas; marapossi, imperiodainformatica e petvida pelos clientes).

## Risks / Trade-offs

- [Subpedido para gerar Markdown] → custo de uma renderização a mais por `.md`; cache HTTP curto (`max-age=300`) e o mesmo HTML que já é servido.
- [DNSSEC no domínio inteiro do cliente] → mesmo procedimento testado no wab.com.br; vigia diário por e-mail; volta atrás retirando o DS.
- [Conteúdo de exemplo da AJOPE] → as notícias de exemplo continuam fora do sitemap e passam a ficar fora do llms.txt e do MCP pela mesma regra (`withSamples: false`).

## Migration Plan

Local (127.0.0.1 e os tenants `*.localhost`) → deploy (git pull, build.sh) → Nginx → medir os 10 → DNS-AID → DNSSEC e DS → medir e responder cada ticket. Retorno: reverter o commit e apagar o arquivo do Nginx; no DNS, retirar o DS e só depois desassinar.
