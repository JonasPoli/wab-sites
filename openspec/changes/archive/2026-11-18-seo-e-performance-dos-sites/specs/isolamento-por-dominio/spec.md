# Spec Delta

## MODIFIED Requirements

### Requirement: Domínio não cadastrado
Quando nenhum Tenant tem o domínio do host, o sistema SHALL responder HTTP 404 com o HTML "Domínio não configurado" e o host escapado, e MUST NOT executar a rota pedida, inclusive `/login`, `/admin` e `/superadmin`. A única exceção é `/robots.txt`, que SHALL seguir para o controller e responder proibindo o rastreamento do domínio inteiro: um 404 aí seria lido pelo buscador como "não há robots.txt, pode rastrear".

#### Scenario: site.wab.com.br
- **WHEN** alguém abre `https://site.wab.com.br/superadmin`
- **THEN** a resposta é 404 com "O domínio site.wab.com.br não está registrado nesta plataforma."

#### Scenario: robots.txt de domínio que não é site
- **WHEN** um robô abre `/robots.txt` num domínio que aponta para o servidor sem ser tenant
- **THEN** a resposta é 200 com `User-agent: *` e `Disallow: /`, e nenhum dado de tenant é lido
