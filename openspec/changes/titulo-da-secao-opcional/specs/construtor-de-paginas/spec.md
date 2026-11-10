## MODIFIED Requirements

### Requirement: Seções da página
O sistema SHALL renderizar só as seções ativas da página ou categoria, na ordem de `position`, cada uma num `<section id="section-{id}">`, e SHALL mostrar o título em duas cores (`titlePart1` e `titlePart2`) quando `Tenant.showSectionTitles` está ligado, a seção tem "Exibir o título da seção na página" marcado e uma das partes está preenchida.

#### Scenario: Seção desativada
- **WHEN** uma seção tem `active` falso
- **THEN** ela não aparece na página nem no menu do Modo Landing Page

#### Scenario: Título da seção oculto
- **WHEN** o admin desmarca "Exibir o título da seção na página" na seção "Quem | Somos" e salva
- **THEN** a página mostra os blocos da seção sem o título em duas cores, e o título continua gravado e no menu do Modo Landing Page

#### Scenario: Seção nova
- **WHEN** o admin cria uma seção sem mexer na caixa
- **THEN** o título da seção aparece na página
