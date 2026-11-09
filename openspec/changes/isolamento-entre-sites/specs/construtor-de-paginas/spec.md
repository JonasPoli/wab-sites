## MODIFIED Requirements

### Requirement: Perfil de membro da equipe
O sistema SHALL exibir em `/membro/{slug}` o membro de bloco `team` com `detailEnabled` ligado, cadastrado numa seção de página ou categoria do tenant do domínio, no template `team_member_<detailLayout>.html.twig` do tema (`classic` quando vazio), e responder 404 "Perfil não encontrado." quando não há membro com perfil ativo nesse tenant.

#### Scenario: Perfil desligado
- **WHEN** o membro existe mas `detailEnabled` é falso
- **THEN** `/membro/{slug}` responde 404

#### Scenario: Membro de outro site
- **WHEN** `pkwy.com.br` recebe `/membro/{slug}` com o slug de um membro com perfil ativo cadastrado num bloco do tenant 1
- **THEN** a resposta é 404 "Perfil não encontrado."

#### Scenario: Mesmo slug em dois sites
- **WHEN** os tenants 1 e 3 têm membros com perfil ativo e o mesmo slug, e `pkwy.com.br` recebe `/membro/{slug}`
- **THEN** aparece o membro do tenant 3, no tema do tenant 3
