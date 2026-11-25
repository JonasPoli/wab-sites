## ADDED Requirements

### Requirement: Itens da barra superior por lado
Com a barra superior ligada, cada página pública SHALL mostrar acima do cabeçalho uma faixa com os itens do lado esquerdo à esquerda e os do lado direito à direita, na ordem gravada. Item cujo dado do site está vazio (por exemplo, Telefone sem telefone cadastrado) SHALL ser omitido sem deixar espaço.

#### Scenario: Itens em ordem
- **WHEN** o lado esquerdo tem Telefone, WhatsApp e E-mail, nessa ordem, e o direito tem um item personalizado "Portal do Aluno"
- **THEN** a barra mostra telefone, WhatsApp e e-mail à esquerda, nessa ordem, e "Portal do Aluno" à direita

#### Scenario: Dado vazio
- **WHEN** o lado esquerdo tem o item E-mail e o site não tem e-mail de contato
- **THEN** a barra não mostra o item de e-mail

#### Scenario: Barra desligada
- **WHEN** a barra superior está desligada
- **THEN** nenhuma página mostra a barra, mesmo com itens gravados

### Requirement: Itens com os dados do site
Os itens Telefone, WhatsApp, E-mail, Endereço, Horário e Redes sociais SHALL usar os dados cadastrados no site, cada um com seu ícone: Telefone liga (`tel:`), WhatsApp abre o link do WhatsApp em nova aba mostrando o número no formato (DD) NNNNN-NNNN, E-mail abre `mailto:`, Endereço abre o endereço no Google Maps em nova aba, Horário só mostra o texto e Redes sociais mostra um ícone por rede cadastrada.

#### Scenario: WhatsApp
- **WHEN** o site tem o link de WhatsApp `https://wa.me/5516996494545` e o item WhatsApp não tem texto
- **THEN** a barra mostra o ícone do WhatsApp e "(16) 99649-4545", com link para `https://wa.me/5516996494545` em nova aba

### Requirement: Texto próprio nos itens do site
Nos itens Telefone, WhatsApp, E-mail e Endereço, um texto opcional SHALL substituir o texto mostrado, sem mudar o link.

#### Scenario: Endereço com texto curto
- **WHEN** o site tem o endereço "Av. Brasil, 782 - Centro, Araraquara - SP" e o item Endereço tem o texto "Araraquara"
- **THEN** a barra mostra o ícone de local e "Araraquara", com link para o Google Maps buscando o endereço completo

### Requirement: Item personalizado da barra superior
O item Personalizado SHALL ter ícone, texto e link opcionais, com ao menos ícone ou texto, e a opção de abrir o link em nova aba. Sem link, o item SHALL aparecer como texto, sem ser clicável. Link de item só com ícone SHALL ter um rótulo acessível.

#### Scenario: Ícone, texto e link
- **WHEN** o lado direito tem um item personalizado com o ícone `fa-solid fa-user-lock`, o texto "Portal do Aluno", o link `play.google.com/store/apps/details?id=br.com.sponte.sponteapp` e nova aba
- **THEN** a barra mostra o ícone e "Portal do Aluno", com link para `https://play.google.com/store/apps/details?id=br.com.sponte.sponteapp` em nova aba

#### Scenario: Só ícone com link
- **WHEN** um item personalizado tem só o ícone `fa-brands fa-tiktok` e um link
- **THEN** a barra mostra o ícone com o link e um rótulo acessível

### Requirement: Cor da barra superior
A barra superior SHALL ter como fundo a cor escolhida para ela ou, sem escolha, a cor primária do site (a primária escura no modo escuro), e o texto e os ícones SHALL ficar brancos sobre fundo que o sistema considera escuro e em quase preto sobre fundo claro.

#### Scenario: Cor escolhida
- **WHEN** a barra tem a cor `#3d3e90`
- **THEN** o fundo da barra é `#3d3e90` nos modos claro e escuro, com texto branco

#### Scenario: Sem cor escolhida
- **WHEN** a barra não tem cor e a cor primária do site é `#ffcc00`
- **THEN** o fundo da barra é `#ffcc00`, com texto escuro

### Requirement: Barra superior não fixa
A barra superior SHALL rolar com a página e sair da tela quando o visitante desce; o cabeçalho de cada tema SHALL manter o comportamento dele e, quando fica preso no topo, encostar no topo da tela depois que a barra sai, sem vão.

#### Scenario: Rolagem no tema moderno
- **WHEN** o visitante de um site do tema moderno com a barra ligada desce 300 px
- **THEN** a barra não está mais visível e o cabeçalho fica encostado no topo da tela

#### Scenario: Rolagem no tema cetec
- **WHEN** o visitante de um site do tema cetec com a barra ligada desce 300 px
- **THEN** a barra não está mais visível e o cabeçalho continua preso no topo, como antes

### Requirement: Barra superior nos cinco temas
Os temas cetec, moderno, wab, noticias e atelie SHALL desenhar a barra superior acima do cabeçalho em todas as páginas públicas, fora da prévia do bloco no painel.

#### Scenario: Tema noticias
- **WHEN** um site do tema noticias liga a barra com o item Telefone
- **THEN** as páginas do portal mostram a barra com o telefone acima do cabeçalho

### Requirement: Barra superior no celular
Em telas menores que 768 px, itens com ícone SHALL mostrar só o ícone, e a barra SHALL caber na largura da tela sem rolagem lateral.

#### Scenario: Celular
- **WHEN** a barra com Endereço, Telefone, WhatsApp e Redes sociais é vista em 390 px de largura
- **THEN** aparecem só os ícones, e a página não tem rolagem lateral

### Requirement: Configuração antiga da barra superior
A barra gravada no formato anterior (lista de nomes `social`, `phone`, `email`, `address` e `hours` em cada lado) SHALL continuar sendo desenhada com os mesmos itens, na mesma ordem, e aberta no editor como itens desses tipos.

#### Scenario: Site com a barra antiga
- **WHEN** o site tem `topBarLeft` igual a `["hours"]` e `topBarRight` igual a `["social", "phone"]`
- **THEN** a barra mostra o horário à esquerda e as redes sociais e o telefone à direita, e o editor abre com esses três itens

### Requirement: Editor da barra superior no painel do site
O `/admin/settings` SHALL ter o editor da barra superior: ligar e desligar, cor de fundo opcional e, em cada lado, acrescentar item de qualquer tipo, preencher seus campos (texto; no personalizado, ícone pelo seletor de ícones, texto, link e nova aba), remover, subir, descer e passar para o outro lado.

#### Scenario: Montar a barra
- **WHEN** o admin liga a barra, acrescenta à esquerda Endereço com o texto "Araraquara" e Telefone, à direita um personalizado "Portal do Aluno" com ícone e link, e salva
- **THEN** o site passa a mostrar esses itens, e o editor reabre com eles na mesma ordem

### Requirement: Gravação da barra superior
Ao salvar, o sistema SHALL descartar itens de tipo desconhecido e personalizados sem ícone e sem texto, limitar a 10 itens por lado, cortar o texto em 80 caracteres, aceitar como cor só `#` seguido de 6 dígitos hexadecimais e acrescentar `https://` ao link que não começa com `http://`, `https://`, `//`, `mailto:`, `tel:`, `/` ou `#`.

#### Scenario: Link sem protocolo
- **WHEN** o admin salva um personalizado com o link `portal.escola.com.br`
- **THEN** o link é gravado como `https://portal.escola.com.br`

#### Scenario: Personalizado vazio
- **WHEN** o admin salva um item personalizado sem ícone e sem texto
- **THEN** o item é descartado
