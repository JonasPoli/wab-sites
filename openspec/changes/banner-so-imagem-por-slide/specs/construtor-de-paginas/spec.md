# Spec Delta

## MODIFIED Requirements

### Requirement: Banner só com a imagem
Cada slide do bloco `banner` SHALL ter a opção "Só a imagem", desligada por padrão. Um slide ativo com ela ligada SHALL mostrar só a sua imagem: sem camada escura, degradê, tom da cor do site, desfoque nem zoom ao passar o mouse, e sem pré-título, título, subtítulo e botões, inclusive os botões fixos do tema. Bloco gravado com a opção antiga, do bloco inteiro, SHALL tratar todos os slides como "só a imagem".

#### Scenario: Slide com título e botão gravados
- **WHEN** o banner tem um slide ativo com "Só a imagem" ligada, título "Novembro Azul", subtítulo e o botão "Conheça Nossos Produtos"
- **THEN** a página mostra a imagem do slide com as cores originais e não mostra "Novembro Azul", o subtítulo, "Conheça Nossos Produtos", "Ver Nossos Cursos" nem "Fale Conosco"

#### Scenario: Banner sem a opção
- **WHEN** um banner gravado antes desta mudança e sem a opção é exibido
- **THEN** ele sai exatamente como antes, com camada escura, título e botões

#### Scenario: Banner gravado com a opção do bloco
- **WHEN** um banner gravado com "Só a imagem" ligada para o bloco inteiro, antes desta mudança, é exibido
- **THEN** todos os slides saem só com a imagem, como antes

#### Scenario: Slide desativado
- **WHEN** o banner tem oito slides "só a imagem" e só o primeiro está ativo
- **THEN** a página mostra só a imagem do primeiro, sem carrossel

### Requirement: Imagem inteira no banner só com a imagem
Quando todos os slides ativos do banner estão com "Só a imagem" e "100% de altura" está desligada, o banner SHALL mostrar a imagem inteira, na largura do banner e na proporção da própria imagem, sem recorte e sem altura mínima. Com "100% de altura" ligada, o banner SHALL ocupar a altura da tela e a imagem SHALL preencher esse espaço, recortada nas bordas quando a proporção for outra.

#### Scenario: Arte larga num banner de largura inteira
- **WHEN** a imagem do slide tem 1884×835 px, o banner tem "100% de largura" e é visto numa tela de 1280 px
- **THEN** o banner tem a largura da tela e cerca de 567 px de altura, e a imagem aparece inteira

#### Scenario: Celular
- **WHEN** o mesmo banner é visto numa tela de 390 px
- **THEN** a imagem aparece inteira, com cerca de 173 px de altura, e o navegador baixa a versão de celular

#### Scenario: Banner com 100% de altura
- **WHEN** a opção "100% de altura" também está ligada
- **THEN** o banner ocupa a altura da tela e a imagem cobre todo o espaço, sem faixas vazias

#### Scenario: Espaço reservado antes do download
- **WHEN** a página com o banner é carregada
- **THEN** a `<img>` traz `width` e `height` na proporção da imagem servida e o conteúdo abaixo do banner não se desloca quando a imagem chega

### Requirement: Carrossel do banner só com a imagem
Quando todos os slides ativos estão com "Só a imagem" e há mais de um, o banner SHALL continuar como carrossel, com troca automática a cada 5 segundos, setas e bolinhas de navegação. A altura do carrossel SHALL seguir a proporção da imagem do primeiro slide ativo, e as imagens dos outros slides SHALL preencher a mesma área.

#### Scenario: Três slides ativos do mesmo tamanho
- **WHEN** o banner tem três slides ativos "só a imagem" com imagens de 1920×800 px
- **THEN** a página mostra a primeira imagem inteira, com setas e bolinhas, e troca para a segunda depois de 5 segundos

#### Scenario: Slide com proporção diferente
- **WHEN** o segundo slide tem uma imagem mais alta que a do primeiro
- **THEN** o carrossel mantém a altura do primeiro e a segunda imagem preenche a área, recortada nas bordas

### Requirement: Descrição da imagem do banner
Cada slide com "Só a imagem" ligada SHALL aceitar uma "Descrição da imagem" opcional, que não aparece na tela. Preenchida, ela SHALL ser o texto alternativo da imagem e SHALL aparecer na versão Markdown da página; vazia, a imagem SHALL ser tratada como decorativa, com texto alternativo vazio, e ficar fora da versão Markdown.

#### Scenario: Descrição preenchida
- **WHEN** o slide tem a descrição "Campanha Novembro Azul: cuidar de você também é prevenção"
- **THEN** a `<img>` tem esse texto como `alt` e a versão `.md` da página traz a imagem com essa descrição

#### Scenario: Descrição vazia
- **WHEN** o slide não tem descrição
- **THEN** a `<img>` tem `alt=""` e a versão `.md` da página não cita a imagem

#### Scenario: Slide só com a imagem num carrossel com texto
- **WHEN** o segundo slide de um carrossel com slides de texto está com "Só a imagem" e tem descrição
- **THEN** a imagem desse slide tem a descrição como `alt`

### Requirement: Primeira imagem do banner só com a imagem
Quando um banner com todos os slides "só a imagem" é o primeiro elemento da página, a imagem do primeiro slide ativo SHALL ser carregada com prioridade alta e sem adiamento, com `preload` no `<head>` pedindo os mesmos arquivos que a `<img>`; as imagens dos outros slides SHALL ser adiadas.

#### Scenario: Home com banner só com a imagem
- **WHEN** a home de um site tem um banner com todos os slides "só a imagem" na primeira seção
- **THEN** o `imagesrcset` do `preload` é igual ao `srcset` da `<img>` do primeiro slide, e essa `<img>` tem `fetchpriority="high"`

#### Scenario: Primeiro slide só com a imagem num carrossel com texto
- **WHEN** o primeiro slide ativo é "só a imagem" e o segundo tem texto
- **THEN** o `imagesrcset` do `preload` é igual ao `srcset` da `<img>` do primeiro slide, e ela tem `fetchpriority="high"`

### Requirement: Edição do banner só com a imagem no painel
No formulário do bloco `banner`, cada slide SHALL ter o interruptor "Só a imagem". Ligado num slide, título, subtítulo, texto e link do botão desse slide SHALL ficar desabilitados, com aviso de que não aparecem no site, o título dele SHALL deixar de ser obrigatório e ele SHALL mostrar o campo "Descrição da imagem"; os outros slides não mudam. Os textos já gravados SHALL ser mantidos e voltar a valer ao desligar a opção.

#### Scenario: Ligar a opção num banner existente
- **WHEN** o admin liga "Só a imagem" nos oito slides do bloco 147 e salva
- **THEN** o bloco é gravado sem erro, e os títulos, subtítulos e botões dos oito slides continuam gravados

#### Scenario: Ligar a opção num slide
- **WHEN** o admin liga "Só a imagem" no segundo de três slides e salva
- **THEN** só o segundo fica gravado como "só a imagem", e os títulos, subtítulos e botões dos três continuam gravados

#### Scenario: Desligar a opção
- **WHEN** o admin desliga "Só a imagem" desse slide e salva
- **THEN** o slide volta a mostrar no site o título, o subtítulo e o botão de antes

#### Scenario: Slide novo só com a imagem
- **WHEN** o admin acrescenta um slide, liga "Só a imagem", escolhe a imagem e salva sem preencher título
- **THEN** o slide é gravado com a imagem e o título vazio

#### Scenario: Campos desabilitados
- **WHEN** a opção está ligada num slide
- **THEN** os campos de título, subtítulo, texto e link do botão desse slide aparecem apagados e não aceitam digitação nem foco pelo teclado, e os dos outros slides continuam editáveis

#### Scenario: Bloco gravado com a opção do bloco
- **WHEN** o admin abre um banner gravado com a opção antiga, do bloco inteiro
- **THEN** todos os slides aparecem com "Só a imagem" ligada, e ao salvar a escolha fica gravada em cada slide

## ADDED Requirements

### Requirement: Banner com slides dos dois tipos
Num banner com slides ativos "só a imagem" e slides com texto, o banner SHALL seguir o desenho e a altura dos slides com texto do tema, como carrossel; cada slide "só a imagem" SHALL mostrar só a sua imagem preenchendo a área, recortada nas bordas quando a proporção for outra, sem camada, degradê, zoom, texto nem botões. O formulário do painel SHALL avisar isso.

#### Scenario: Arte entre slides com texto
- **WHEN** um carrossel tem o slide 1 com texto e botão e o slide 2 "só a imagem" com título guardado "Promoção"
- **THEN** o slide 1 sai com camada, título e botão, e o slide 2 só com a imagem, sem "Promoção", na mesma altura do slide 1

#### Scenario: Mesmo banner nos cinco temas
- **WHEN** a mesma página com esse carrossel é aberta em sites dos temas cetec, moderno, wab, noticias e atelie
- **THEN** em todos o slide "só a imagem" sai sem camada, texto e botões, e o slide com texto sai como antes desta mudança
