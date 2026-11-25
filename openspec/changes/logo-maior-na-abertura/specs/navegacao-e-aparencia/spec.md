## ADDED Requirements

### Requirement: Logo maior na abertura da página
Com a opção "Logo maior na abertura" ligada e uma altura escolhida maior que a do logo normal do tema, o logo do cabeçalho SHALL aparecer nessa altura com a página no topo, e o cabeçalho SHALL crescer para caber nele. Com a opção desligada, ou com altura menor ou igual à normal, o cabeçalho SHALL continuar como antes.

#### Scenario: Página aberta no topo
- **WHEN** um site do tema cetec (logo normal de 56 px) tem a opção ligada com 120 px e o visitante abre a home numa tela de 1280 px
- **THEN** o logo do cabeçalho aparece com 120 px de altura, inteiro e sem cortes

#### Scenario: Opção desligada
- **WHEN** o site não tem a opção ligada
- **THEN** o logo e o cabeçalho têm o tamanho de antes da mudança

### Requirement: Logo diminui com a rolagem
Com a opção ligada, o logo SHALL diminuir na mesma medida da rolagem, até chegar à altura normal do tema depois de uma rolagem igual à diferença entre as duas alturas; dali em diante SHALL ficar na altura normal. Ao voltar ao topo, SHALL voltar à altura escolhida.

#### Scenario: Meio do caminho
- **WHEN** o logo normal tem 56 px, a altura escolhida é 120 px e o visitante desceu 30 px
- **THEN** o logo tem 90 px de altura

#### Scenario: Depois da redução
- **WHEN** o visitante desceu 300 px na mesma página
- **THEN** o logo tem 56 px e o cabeçalho tem a altura normal do tema

#### Scenario: Volta ao topo
- **WHEN** o visitante volta ao topo da página
- **THEN** o logo volta a ter 120 px

### Requirement: Logo maior no celular
Em telas com menos de 768 px de largura, o aumento do logo na abertura SHALL ser metade da diferença entre a altura escolhida e a normal do tema.

#### Scenario: Celular
- **WHEN** o logo normal tem 48 px no celular, a altura escolhida é 120 px e a página é aberta em 390 px de largura
- **THEN** o logo aparece com 84 px no topo e diminui até 48 px com a rolagem

### Requirement: Logo maior nos cinco temas
A opção SHALL valer no cabeçalho dos temas cetec, moderno, wab, noticias e atelie, inclusive nos modos claro e escuro e com a barra superior ligada, e SHALL deixar o logo do rodapé como está.

#### Scenario: Tema moderno com a barra superior
- **WHEN** um site do tema moderno tem a barra superior e a opção ligadas e é aberto no topo
- **THEN** a barra fica acima do cabeçalho, o logo aparece na altura escolhida e o conteúdo começa abaixo do cabeçalho, sem ficar escondido por ele

#### Scenario: Rodapé
- **WHEN** a opção está ligada
- **THEN** o logo do rodapé tem o mesmo tamanho de antes

### Requirement: Logo nítido na abertura
Com a opção ligada, o logo do cabeçalho SHALL ser servido numa versão com pelo menos o dobro da altura escolhida, quando o arquivo enviado tiver esse tamanho, para não ficar borrado em telas de alta densidade.

#### Scenario: Logo grande enviado
- **WHEN** o logo enviado tem 600 px de altura e a altura escolhida é 120 px
- **THEN** a imagem servida no cabeçalho tem pelo menos 240 px de altura
