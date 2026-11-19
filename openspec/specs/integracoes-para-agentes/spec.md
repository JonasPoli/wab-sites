# integracoes-para-agentes Specification

## Purpose

Define os protocolos e documentos que deixam agentes de IA usar cada site: o servidor MCP só de leitura, as habilidades, o cartão do servidor, os catálogos de API e ARD, o poppy.json e o WebMCP nos temas.

## Requirements

### Requirement: Servidor MCP por domínio
O sistema SHALL servir em `/mcp` de cada tenant um servidor MCP (Streamable HTTP, JSON-RPC 2.0, sem sessão e só de leitura) com as ferramentas `dados_do_site`, `listar_paginas`, `ler_pagina` e `listar_noticias`, e com as páginas públicas como recursos. Nenhuma ferramenta SHALL gravar dado ou enviar mensagem.

#### Scenario: Ferramentas do domínio
- **WHEN** um cliente MCP chama `initialize` e `tools/call` com `listar_paginas` num tenant
- **THEN** recebe só as páginas daquele tenant

#### Scenario: Página em Markdown
- **WHEN** `ler_pagina` é chamada com o caminho de uma página publicada
- **THEN** recebe o mesmo Markdown de `<caminho>.md`

### Requirement: Documentos de descoberta
O sistema SHALL publicar por tenant, com CORS aberto: `/.well-known/agent-skills/index.json` (Agent Skills Discovery 0.2.0, com `digest` igual ao sha256 de cada `SKILL.md` servido), os `SKILL.md`, `/.well-known/mcp/server-card.json`, `/.well-known/api-catalog` (`application/linkset+json`), `/.well-known/ai-catalog.json` (cada entrada com exatamente um de `url` ou `data` e de 2 a 5 `representativeQueries`) e `/.well-known/poppy.json` (com o domínio do tenant sem `www.`).

#### Scenario: Catálogos coerentes
- **WHEN** os documentos de um tenant são lidos
- **THEN** todos os endereços apontam para o próprio domínio e respondem 200

### Requirement: WebMCP
O sistema SHALL registrar em toda página pública, quando o navegador oferecer `document.modelContext` (ou `navigator.modelContext`), a ferramenta `ler_pagina`, e SHALL descrever com `toolname` e `tooldescription` o formulário de contato dos temas e a busca do tema noticias.

#### Scenario: Navegador com WebMCP
- **WHEN** a home de qualquer tenant abre num navegador com WebMCP
- **THEN** a ferramenta `ler_pagina` fica registrada
