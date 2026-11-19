# dns-para-agentes Specification

## Purpose

Define os registros DNS-AID de cada site e o DNSSEC das zonas dos domínios dos sites no servidor de DNS da WAB, condição para a descoberta de agentes por DNS ser validada.

## Requirements

### Requirement: Registros DNS-AID por domínio
Cada domínio de site SHALL ter, numa zona `_agents.<domínio>` assinada com DNSSEC, os registros SVCB `_mcp`, `_a2a` e `_index` (alvo o próprio domínio, `port=443`, `alpn` e o caminho na chave experimental `key65400`) e o TXT `_index` com a URL do `ai-catalog.json`.

#### Scenario: Consulta validada
- **WHEN** `_a2a._agents.<domínio>` é consultado num resolvedor que valida DNSSEC, depois de o DS da zona do cliente estar no registro.br
- **THEN** a resposta traz o SVCB com a bandeira AD

### Requirement: DNSSEC das zonas dos sites
As zonas dos domínios dos sites no wab2 SHALL ser assinadas, com reassinatura semanal e vigia diário que avisa por e-mail sobre assinatura perto de vencer ou validação pública falhando, e SHALL ser testadas com âncora local antes de o DS ir para o registro.br.

#### Scenario: Antes do DS
- **WHEN** a zona de um cliente é assinada e o DS ainda não está no registro.br
- **THEN** o site e o e-mail do cliente continuam respondendo como antes, e a validação com âncora local aprova site, e-mail e registros para agentes
