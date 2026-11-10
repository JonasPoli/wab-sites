## ADDED Requirements

### Requirement: Modo claro e escuro no tema noticias
Todas as páginas públicas do tema `noticias` SHALL oferecer os modos Claro, Escuro e Automático. Na primeira visita, SHALL valer o Claro. A escolha SHALL ficar guardada no navegador do visitante, valer nas páginas seguintes do mesmo site e ser aplicada antes de a página aparecer. O Automático SHALL seguir a preferência de aparência do sistema do visitante e acompanhar a mudança dela com a página aberta.

#### Scenario: Primeira visita
- **WHEN** um visitante sem escolha guardada abre a home do portal, com o sistema em modo escuro
- **THEN** a página aparece no modo Claro

#### Scenario: Escolha mantida entre páginas
- **WHEN** o visitante escolhe Escuro na home e depois abre uma notícia
- **THEN** a notícia já aparece no modo Escuro, sem mostrar antes a versão clara

#### Scenario: Automático com o sistema mudando
- **WHEN** o visitante está no Automático e muda o sistema de claro para escuro com a página aberta
- **THEN** a página passa para o modo Escuro sem recarregar

### Requirement: Seletor de aparência no cabeçalho do tema noticias
O cabeçalho do tema `noticias` SHALL ter, no canto direito e em todas as larguras de tela, um botão que mostra o modo atual por ícone e, a partir de 768 px de largura, também pelo nome. Cada acionamento SHALL passar para o modo seguinte, na ordem Claro, Escuro, Automático e de volta a Claro. O botão SHALL funcionar pelo teclado e ter nome acessível com o modo atual.

#### Scenario: Troca pelo teclado
- **WHEN** um visitante no modo Claro chega ao botão com Tab e aperta Enter
- **THEN** a página passa para o modo Escuro, e o nome acessível do botão passa a dizer Escuro

#### Scenario: Celular
- **WHEN** o portal é aberto numa tela de 390 px
- **THEN** o botão aparece no cabeçalho só com o ícone, sem empurrar o logo nem o botão MENU

### Requirement: Cores do modo escuro no tema noticias
No modo Escuro do tema `noticias`, o fundo da página SHALL usar a cor de fundo escuro 1 do site, e cartões, quadros da barra lateral, menu lateral e campos de formulário SHALL usar a cor de fundo escuro 2; texto, texto secundário e linhas divisórias SHALL ser claros. Títulos, links e números de destaque SHALL usar a cor primária do modo escuro quando ela tem contraste de pelo menos 4,5:1 com as duas cores de fundo escuro, e um texto claro neutro quando não tem.

#### Scenario: AJoPE com as cores escuras padrão
- **WHEN** o portal da AJoPE (cor primária `#00275c`, cor primária do modo escuro `#3b82f6`, fundos escuros `#0d0f1a` e `#131625`) é aberto no modo Escuro
- **THEN** o fundo da página é `#0d0f1a`, os quadros da barra lateral são `#131625` e os títulos das notícias são `#3b82f6`

#### Scenario: Cor primária do modo escuro sem contraste
- **WHEN** o admin do site define `#00275c` como cor primária do modo escuro e o visitante está no modo Escuro
- **THEN** os títulos e links aparecem em texto claro neutro, e não em `#00275c`

### Requirement: Cabeçalho e rodapé do tema noticias nos dois modos
No tema `noticias`, o cabeçalho, o rodapé e o botão "Seja um autor" do menu lateral SHALL usar a cor primária do site (a do modo claro) nos dois modos, com o texto em branco quando essa cor é escura e em cinza-escuro quando é clara.

#### Scenario: Cabeçalho da AJoPE no modo Escuro
- **WHEN** o portal da AJoPE é aberto no modo Escuro
- **THEN** o cabeçalho e o rodapé continuam em `#00275c`, com o texto em branco

### Requirement: Modo claro do tema noticias sem mudança
No modo Claro, as páginas do tema `noticias` SHALL ter as mesmas cores, fontes e disposição que tinham antes do modo escuro; a única diferença SHALL ser o botão de aparência no cabeçalho e o espaço que ele ocupa.

#### Scenario: Comparação antes e depois
- **WHEN** a home, uma notícia e a página do autor do portal de exemplo são capturadas no modo Claro antes e depois deste change, a 1280 px e a 390 px
- **THEN** as capturas só diferem pelo botão de aparência no canto direito do cabeçalho e, na página da notícia, pela posição do nome da editoria, que passa a se centralizar no espaço entre o logo e o botão

### Requirement: Contraste do texto no modo escuro do tema noticias
No modo Escuro do tema `noticias`, o texto do corpo e o texto secundário SHALL ter contraste de pelo menos 4,5:1 com o fundo em que aparecem (página, cartão ou campo).

#### Scenario: Data da notícia no modo Escuro
- **WHEN** a lista de notícias é aberta no modo Escuro
- **THEN** a linha "Há 3 horas — Em Institucional" tem contraste de pelo menos 4,5:1 com o fundo da página

### Requirement: Blocos institucionais no modo escuro do tema noticias
Nas páginas institucionais e de membro do tema `noticias`, os blocos do tema cetec SHALL acompanhar o modo escolhido, como no próprio tema cetec; seções com fundo próprio (cor, degradê, imagem ou vídeo) SHALL manter esse fundo nos dois modos.

#### Scenario: Página com todos os blocos
- **WHEN** a página "Todos os blocos" do portal de exemplo é aberta no modo Escuro
- **THEN** os 15 tipos de bloco aparecem com fundo escuro e texto claro, sem texto escuro sobre fundo escuro

## MODIFIED Requirements

### Requirement: Logos claro e escuro
O cabeçalho SHALL mostrar o logo normal no modo claro e o logo escuro (`darkLogo`) no modo escuro, usando o logo normal quando não há logo escuro; o rodapé SHALL usar o logo escuro e, sem ele, o normal. No tema `noticias`, cujo cabeçalho e rodapé ficam na cor primária do site nos dois modos, ambos SHALL usar, em qualquer modo, o logo escuro quando a cor primária é escura e o logo normal quando é clara; sem o logo escuro, SHALL mostrar o normal sobre um fundo branco arredondado.

#### Scenario: Tenant sem logo escuro
- **WHEN** o tenant só tem o logo normal e o visitante está no modo escuro
- **THEN** o cabeçalho mostra o logo normal

#### Scenario: Portal com cor primária escura e sem logo escuro
- **WHEN** um site com tema `noticias` tem a cor primária `#0b2559` e só o logo normal
- **THEN** o cabeçalho e o rodapé mostram o logo normal sobre um fundo branco arredondado

#### Scenario: Portal no modo Escuro
- **WHEN** um site com tema `noticias`, cor primária escura e logo escuro é aberto no modo Escuro
- **THEN** o cabeçalho e o rodapé mostram o logo escuro, como no modo Claro
