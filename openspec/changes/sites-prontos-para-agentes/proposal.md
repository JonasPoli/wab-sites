## Why

Em 19/11/2026 os 10 sites da plataforma (marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br, ajope.wab.com.br e pichoneri.wab.com.br) marcavam 20 de 100 no isitagentready.com (Cloudflare), nível 1. Assistentes e agentes de IA (ChatGPT, Claude, Gemini, Perplexity, navegadores com agente) passam a ser um canal de descoberta dos clientes, e o site da WAB já chegou a 100 com o mesmo conjunto de recursos (mudanças `site-pronto-para-agentes`, `descoberta-por-agentes` e `autenticacao-e-a2a-para-agentes` do wab2024). Um ticket por site (#13486 a #13495) acompanha o resultado.

## What Changes

Tudo por domínio (tenant), sem cadastro novo: os dados vêm do Tenant, das páginas publicadas e das notícias.

- **robots.txt** com grupos para robôs de busca e assistentes (liberados) e de treino de modelos (bloqueados), e `Content-Signal: search=yes, ai-input=yes, ai-train=no` (regra da casa para site de cliente).
- **Conteúdo para agentes**: versão Markdown de toda página pública (`/caminho.md` e a mesma URL com `Accept: text/markdown`), `/llms.txt` e `/llms-full.txt`, cabeçalhos `Link` (alternate, describedby, sitemap, api-catalog) e `Vary: Accept`.
- **Integrações**: servidor MCP só de leitura em `/mcp`, índice de habilidades em `/.well-known/agent-skills/`, cartão do servidor MCP, catálogo de API (RFC 9727), catálogo ARD (`ai-catalog.json`), `poppy.json` e WebMCP (ferramenta `ler_pagina` em toda página e formulários de contato e busca descritos).
- **Autenticação e A2A**: Auth.md com registro anônimo, OAuth sem banco (RFC 8414, 9728, 7591, 7523, 7009), API para agentes em `/api/agentes` e agente A2A em `/a2a` (1.0 e 0.3) com cartão.
- **Servidor e DNS (fora do código)**: blocos no Nginx do RunCloud para os caminhos em `/.well-known/`; registros DNS-AID em zonas `_agents.<domínio>` assinadas; DNSSEC nas 8 zonas de cliente (wab2) com DS no registro.br.
- **MODIFIED**: a requirement "Regras de rastreamento por domínio" de `seo-das-paginas`.

## Capabilities

### New Capabilities
- `conteudo-para-agentes`: Markdown das páginas, llms.txt, cabeçalhos Link.
- `integracoes-para-agentes`: MCP, habilidades, catálogos, poppy e WebMCP.
- `autenticacao-de-agentes`: Auth.md, OAuth, API para agentes e A2A.
- `dns-para-agentes`: DNS-AID e DNSSEC dos domínios dos sites.

### Modified Capabilities
- `seo-das-paginas`: robots.txt com regras para robôs de IA.

## Impact

- Código: `src/Agentes/` (novo), controladores públicos novos, um parcial comum de WebMCP nos temas cetec, noticias, atelie, moderno e wab, `RobotsController`, dependência `league/html-to-markdown` (o deploy passa por `build.sh`, que já roda `composer install`).
- Servidor wabsites (root): arquivo novo em `/etc/nginx-rc/extra.d/`. Servidor wab2 (root): DNSSEC e zonas `_agents`. Registro.br: DS de 8 domínios (5 pelo Jonas, 3 pelos clientes).
- Sem migration e sem dado novo no banco. A chave do OAuth fica em `var/oauth/`.
