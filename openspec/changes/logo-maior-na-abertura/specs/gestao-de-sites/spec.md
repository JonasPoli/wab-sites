## ADDED Requirements

### Requirement: Logo na abertura na edição do tenant
A edição do tenant no `/superadmin` SHALL ter, junto do logo, o grupo "Logo na abertura" com a opção "Logo maior na abertura da página" e a altura do logo na abertura (60 a 200 px, padrão 110), com uma prévia do logo nessa altura. Ao salvar, a altura fora da faixa SHALL ser trazida para o limite mais próximo; tenant novo começa com a opção desligada.

#### Scenario: Ligar o logo maior
- **WHEN** o SuperAdmin marca "Logo maior na abertura da página", escolhe 130 px e salva
- **THEN** o tenant fica com a opção ligada e 130 px, e a tela de edição volta com esses valores

#### Scenario: Altura fora da faixa
- **WHEN** o POST traz a altura 500
- **THEN** a altura gravada é 200

#### Scenario: Envio sem o grupo
- **WHEN** o POST de edição do tenant não traz os campos do grupo
- **THEN** a escolha gravada continua a mesma

### Requirement: Logo na abertura na exportação e na importação
A exportação do tenant SHALL levar a opção e a altura do logo na abertura, e a importação SHALL gravá-las no tenant novo.

#### Scenario: Exportar e importar
- **WHEN** um site com o logo maior ligado em 130 px é exportado e importado com outro domínio
- **THEN** o site importado tem a opção ligada com 130 px
