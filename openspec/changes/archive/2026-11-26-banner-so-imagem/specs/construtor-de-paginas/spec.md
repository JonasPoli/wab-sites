# Spec Delta

## ADDED Requirements

### Requirement: Banner só com a imagem
O bloco `banner` SHALL ter a opção "Só a imagem", desligada por padrão e válida para todos os slides do bloco. Com ela ligada, cada slide ativo SHALL mostrar só a sua imagem: sem camada escura, degradê, tom da cor do site, desfoque nem zoom ao passar o mouse, e sem pré-título, título, subtítulo e botões, inclusive os botões fixos do tema.

#### Scenario: Slide com título e botão gravados
- **WHEN** o banner com a opção ligada tem um slide ativo com título "Novembro Azul", subtítulo e o botão "Conheça Nossos Produtos"
- **THEN** a página mostra a imagem do slide com as cores originais e não mostra "Novembro Azul", o subtítulo, "Conheça Nossos Produtos", "Ver Nossos Cursos" nem "Fale Conosco"

#### Scenario: Banner sem a opção
- **WHEN** um banner gravado antes desta mudança é exibido
- **THEN** ele sai exatamente como antes, com camada escura, título e botões

#### Scenario: Slide desativado
- **WHEN** o banner com a opção ligada tem oito slides e só o primeiro está ativo
- **THEN** a página mostra só a imagem do primeiro, sem carrossel

### Requirement: Imagem inteira no banner só com a imagem
Com "Só a imagem" ligada e "100% de altura" desligada, o banner SHALL mostrar a imagem inteira, na largura do banner e na proporção da própria imagem, sem recorte e sem altura mínima. Com "100% de altura" ligada, o banner SHALL ocupar a altura da tela e a imagem SHALL preencher esse espaço, recortada nas bordas quando a proporção for outra.

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
Com "Só a imagem" ligada e mais de um slide ativo, o banner SHALL continuar como carrossel, com troca automática a cada 5 segundos, setas e bolinhas de navegação. A altura do carrossel SHALL seguir a proporção da imagem do primeiro slide ativo, e as imagens dos outros slides SHALL preencher a mesma área.

#### Scenario: Três slides ativos do mesmo tamanho
- **WHEN** o banner tem três slides ativos com imagens de 1920×800 px
- **THEN** a página mostra a primeira imagem inteira, com setas e bolinhas, e troca para a segunda depois de 5 segundos

#### Scenario: Slide com proporção diferente
- **WHEN** o segundo slide tem uma imagem mais alta que a do primeiro
- **THEN** o carrossel mantém a altura do primeiro e a segunda imagem preenche a área, recortada nas bordas

### Requirement: Descrição da imagem do banner
Com "Só a imagem" ligada, cada slide SHALL aceitar uma "Descrição da imagem" opcional, que não aparece na tela. Preenchida, ela SHALL ser o texto alternativo da imagem e SHALL aparecer na versão Markdown da página; vazia, a imagem SHALL ser tratada como decorativa, com texto alternativo vazio, e ficar fora da versão Markdown.

#### Scenario: Descrição preenchida
- **WHEN** o slide tem a descrição "Campanha Novembro Azul: cuidar de você também é prevenção"
- **THEN** a `<img>` tem esse texto como `alt` e a versão `.md` da página traz a imagem com essa descrição

#### Scenario: Descrição vazia
- **WHEN** o slide não tem descrição
- **THEN** a `<img>` tem `alt=""` e a versão `.md` da página não cita a imagem

### Requirement: Banner só com a imagem em todos os temas
A opção "Só a imagem" SHALL funcionar do mesmo jeito nos temas cetec, moderno, wab, noticias e atelie, respeitando as opções "100% de largura" e "100% de altura" e o espaçamento que cada tema já dá à seção de banner.

#### Scenario: Mesmo banner em dois temas
- **WHEN** a mesma página com um banner "só a imagem" é aberta num site do tema cetec e num do tema atelie
- **THEN** os dois mostram só a imagem inteira, sem camada escura, sem texto e sem botões

### Requirement: Primeira imagem do banner só com a imagem
Quando o banner "só a imagem" é o primeiro elemento da página, a imagem do primeiro slide ativo SHALL ser carregada com prioridade alta e sem adiamento, com `preload` no `<head>` pedindo os mesmos arquivos que a `<img>`; as imagens dos outros slides SHALL ser adiadas.

#### Scenario: Home com banner só com a imagem
- **WHEN** a home de um site tem um banner "só a imagem" na primeira seção
- **THEN** o `imagesrcset` do `preload` é igual ao `srcset` da `<img>` do primeiro slide, e essa `<img>` tem `fetchpriority="high"`

### Requirement: Edição do banner só com a imagem no painel
No formulário do bloco `banner`, a opção "Só a imagem" SHALL ficar acima dos slides. Ligada, título, subtítulo, texto e link do botão de cada slide SHALL ficar desabilitados, com aviso de que não aparecem no site, o título SHALL deixar de ser obrigatório e cada slide SHALL mostrar o campo "Descrição da imagem". Os textos já gravados SHALL ser mantidos e voltar a valer ao desligar a opção.

#### Scenario: Ligar a opção num banner existente
- **WHEN** o admin liga "Só a imagem" no bloco 147 e salva
- **THEN** o bloco é gravado sem erro, e os títulos, subtítulos e botões dos oito slides continuam gravados

#### Scenario: Desligar a opção
- **WHEN** o admin desliga "Só a imagem" desse bloco e salva
- **THEN** o banner volta a mostrar no site os títulos, subtítulos e botões de antes

#### Scenario: Slide novo só com a imagem
- **WHEN** com a opção ligada o admin acrescenta um slide, escolhe a imagem e salva sem preencher título
- **THEN** o slide é gravado com a imagem e o título vazio

#### Scenario: Campos desabilitados
- **WHEN** a opção está ligada
- **THEN** os campos de título, subtítulo, texto e link do botão aparecem apagados e não aceitam digitação nem foco pelo teclado

### Requirement: Banner só com a imagem na prévia do painel
A prévia do bloco SHALL refletir a opção "Só a imagem" assim que ela é ligada ou desligada, sem salvar, mostrando o banner como ele sairá no site.

#### Scenario: Ligar a opção com a prévia aberta
- **WHEN** o admin liga "Só a imagem" num banner com título e botão preenchidos
- **THEN** a prévia passa a mostrar só a imagem, sem o título, sem o botão e sem a camada escura
