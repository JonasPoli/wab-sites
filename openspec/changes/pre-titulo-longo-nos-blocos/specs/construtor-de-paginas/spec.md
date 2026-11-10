## ADDED Requirements

### Requirement: Pré-título dos blocos
Nos blocos dos temas cetec e noticias, o pré-título com até 40 caracteres SHALL aparecer como rótulo curto (pílula com texto pequeno em caixa alta), e o pré-título com mais de 40 caracteres SHALL aparecer como destaque legível: sem caixa alta, com texto de pelo menos 14 px, entrelinha folgada, espaço interno de pelo menos 12 px na vertical e 20 px na horizontal e largura máxima de leitura.

#### Scenario: Pré-título longo na página Quem somos
- **WHEN** o bloco tem o pré-título "O AJoPE é o portal de notícias da globo e líder de audiência no jornalismo digital no Brasil. Conheça mais sobre a história do g1."
- **THEN** o texto aparece sem caixa alta, com pelo menos 14 px, e com pelo menos 12 px de espaço acima e abaixo dentro do destaque

#### Scenario: Rótulo curto
- **WHEN** o bloco tem o pré-título "QUEM SOMOS"
- **THEN** ele aparece como hoje, na pílula em caixa alta
