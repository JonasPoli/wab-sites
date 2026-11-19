# Spec Delta

## Purpose

Define como um agente de IA se registra e obtém acesso a dados de cada site (Auth.md e OAuth sem banco, com o emissor no domínio do tenant), a API para agentes e o agente A2A.

## ADDED Requirements

### Requirement: Auth.md e servidor de autorização por domínio
O sistema SHALL publicar por tenant `/auth.md` (H1 com "auth.md") e `/.well-known/oauth-authorization-server` com `issuer` igual a `https://<domínio>` e o bloco `agent_auth` do Auth.md (registro anônimo). O registro anônimo SHALL render um token Bearer de 1 hora com escopo `api.read`, válido só no domínio que o emitiu.

#### Scenario: Metadados do domínio
- **WHEN** os metadados do servidor de autorização de um tenant são lidos
- **THEN** `issuer`, os endereços de token, JWKS, registro e revogação e o `agent_auth.register_uri` usam o domínio do tenant
- **AND** `agent_auth.identity_types_supported` traz `anonymous`, com `credential_types_supported` e `claim_uri`

#### Scenario: Token de um domínio não vale em outro
- **WHEN** um token emitido em um tenant é usado na API de outro tenant
- **THEN** a resposta é 401

#### Scenario: Fluxo anônimo
- **WHEN** um agente se registra, troca a asserção e chama `/api/agentes/paginas` com o token
- **THEN** recebe as páginas do tenant

### Requirement: API para agentes
O sistema SHALL servir em `/api/agentes` (índice, `perfil`, `paginas` e `noticias`) uma API só de leitura que exige token Bearer com escopo `api.read`, responde 401 com `WWW-Authenticate` e `resource_metadata` sem token, e SHALL publicar `/.well-known/oauth-protected-resource` (RFC 9728) e a descrição OpenAPI 3.1.

#### Scenario: Sem token
- **WHEN** a API é chamada sem token
- **THEN** a resposta é 401 com `resource_metadata` apontando os metadados do recurso do mesmo domínio

### Requirement: Agente A2A por domínio
O sistema SHALL responder em `/a2a` de cada tenant a `SendMessage` (A2A 1.0) e `message/send` (0.3) com uma mensagem em português montada com os dados do site (contato, páginas, notícias), e SHALL publicar o cartão em `/.well-known/agent-card.json` com `supportedInterfaces`, `capabilities` e `skills`. Streaming, tarefas e notificações SHALL devolver os erros padrão do A2A.

#### Scenario: Pergunta de contato
- **WHEN** um agente pergunta como falar com a empresa
- **THEN** a resposta traz o telefone, o e-mail ou o WhatsApp cadastrados no tenant
