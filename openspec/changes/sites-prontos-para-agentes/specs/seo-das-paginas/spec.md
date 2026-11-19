# Spec Delta

## MODIFIED Requirements

### Requirement: Regras de rastreamento por domínio
O sistema SHALL responder `/robots.txt` por domínio, com a linha `Sitemap:` apontando para `https://<domínio aberto>/sitemap.xml` e com `Disallow` para `/admin`, `/superadmin`, `/jornalista`, `/login` e `/busca` em todos os grupos. O sistema SHALL ter um grupo para robôs de busca e assistentes de IA, com o resto do site liberado; um grupo para robôs de treino de modelos, com o site inteiro proibido; e `Content-Signal: search=yes, ai-input=yes, ai-train=no` nos grupos que podem ler o site.

#### Scenario: Dois domínios diferentes
- **WHEN** o `/robots.txt` é aberto em dois tenants diferentes
- **THEN** cada resposta traz o `Sitemap:` do seu próprio domínio

#### Scenario: Robôs de IA
- **WHEN** o `/robots.txt` de um tenant é lido
- **THEN** OAI-SearchBot, ChatGPT-User, Claude-SearchBot, Claude-User e PerplexityBot podem ler o site
- **AND** GPTBot, ClaudeBot, Google-Extended, CCBot e Bytespider estão proibidos em `/`

#### Scenario: Domínio que não é tenant
- **WHEN** o `/robots.txt` é aberto num domínio que aponta para o servidor sem ser tenant
- **THEN** a resposta proíbe o rastreamento do site inteiro
