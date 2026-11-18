# Spec Delta

## MODIFIED Requirements

### Requirement: Blocos por tipo
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 22 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact, hero_split, highlights, portfolio, statement, steps, cta, video); tipo desconhecido não renderiza nada.

#### Scenario: Bloco de newsletter com a newsletter desligada
- **WHEN** a seção tem um bloco `newsletter` e o tenant tem `newsletterEnabled` falso
- **THEN** o bloco não é renderizado

#### Scenario: Tipo que não existe
- **WHEN** a seção tem um bloco gravado com um tipo que não está em `BlockType`
- **THEN** a página responde 200 e nada é desenhado no lugar desse bloco

## ADDED Requirements

### Requirement: Blocos novos em todos os temas
Os tipos `hero_split`, `highlights`, `portfolio`, `statement`, `steps`, `cta` e `video` SHALL ser oferecidos na escolha de tipo do `/admin` de qualquer site e SHALL ser desenhados em todos os temas; nos temas `moderno`, `wab` e `noticias` o desenho é o do tema cetec.

#### Scenario: Bloco novo num site com tema moderno
- **WHEN** um site com tema `moderno` tem uma página com um bloco `steps` de três etapas
- **THEN** a página responde 200 e mostra as três etapas com a aparência do cetec, nas cores do site

#### Scenario: Páginas que já existiam
- **WHEN** uma página criada antes desta mudança, só com os 15 tipos antigos, é aberta
- **THEN** o HTML é o mesmo de antes, salvo o nome com hash dos arquivos de CSS e JavaScript

### Requirement: Destaque com foto
O bloco `hero_split` SHALL mostrar pré-título, título, texto e botão ao lado de uma foto, com o trecho do título informado em "destaque" na cor da marca; sobre a foto, SHALL mostrar o selo (número e legenda) e a legenda da foto quando preenchidos. Sem foto, o texto ocupa a largura toda; sem título, texto e foto, nada é desenhado.

#### Scenario: Título com trecho destacado
- **WHEN** o título é "Madeira, design e histórias feitas para durar." e o destaque é "histórias"
- **THEN** só a palavra "histórias" sai na cor da marca

#### Scenario: Destaque que não está no título
- **WHEN** o destaque informado não aparece no título
- **THEN** o título sai inteiro, sem destaque e sem erro

#### Scenario: Primeiro bloco da página
- **WHEN** o `hero_split` é o primeiro bloco da primeira seção de uma página sem banner
- **THEN** o título sai como `h1` quando a página não mostra título próprio, e a foto é carregada com prioridade alta, sem `loading="lazy"`

### Requirement: Faixa de destaques
O bloco `highlights` SHALL mostrar de 2 a 4 itens de título e texto curto lado a lado, em duas colunas no celular; item sem título é ignorado, e com menos de um item preenchido nada é desenhado.

#### Scenario: Três itens preenchidos
- **WHEN** o admin preenche três dos quatro itens
- **THEN** a faixa mostra os três, sem coluna vazia

### Requirement: Portfólio com filtro
O bloco `portfolio` SHALL mostrar as fotos do bloco em grade, com a primeira em destaque, e sobre cada uma a categoria e o título; acima da grade, SHALL mostrar um botão "Todos" e um botão por categoria distinta, que escondem as fotos das outras categorias sem recarregar a página. Com menos de duas categorias o filtro não é desenhado; sem fotos, o bloco não é desenhado.

#### Scenario: Filtrar por categoria
- **WHEN** o visitante clica em "Residencial" num portfólio com fotos de "Corporativo", "Residencial" e "Detalhes"
- **THEN** só as fotos de "Residencial" ficam visíveis, e o botão fica marcado com `aria-pressed="true"`

#### Scenario: Foto sem categoria
- **WHEN** uma foto não tem categoria
- **THEN** ela aparece em "Todos" e some ao escolher qualquer categoria

#### Scenario: Sem JavaScript
- **WHEN** a página é aberta com o JavaScript desligado
- **THEN** todas as fotos aparecem

### Requirement: Título e categoria da foto do portfólio
No formulário do bloco `portfolio`, o admin SHALL poder enviar várias fotos de uma vez e, em cada foto gravada, editar título, categoria, texto alternativo e ordem, e marcar para remover. O bloco `gallery` continua como está e não mostra esses campos.

#### Scenario: Editar a categoria
- **WHEN** o admin muda a categoria de uma foto de "Residencial" para "Corporativo" e salva
- **THEN** a foto passa a aparecer no filtro "Corporativo"

#### Scenario: Foto de outro bloco
- **WHEN** o POST traz o id de uma foto que não pertence ao bloco editado
- **THEN** essa foto não é alterada

### Requirement: Manifesto com imagem
O bloco `statement` SHALL mostrar, sobre fundo escuro, pré-título, título e texto, e abaixo deles o número em destaque com a legenda; a foto ocupa a outra metade até a borda da tela, à direita ou à esquerda conforme a posição escolhida. Sem número, a linha do número não é desenhada.

#### Scenario: Bloco numa seção com fundo claro
- **WHEN** o bloco está numa seção sem fundo definido
- **THEN** o bloco desenha o próprio fundo escuro e o texto fica claro, com contraste de pelo menos 4,5:1

### Requirement: Etapas
O bloco `steps` SHALL mostrar pré-título, título e texto e, abaixo, até 6 etapas na ordem cadastrada, cada uma com rótulo, título e texto; etapa sem título é ignorada, e o rótulo vazio vira o número da etapa com dois dígitos.

#### Scenario: Rótulo vazio
- **WHEN** a segunda etapa tem título "Projeto e 3D" e rótulo vazio
- **THEN** ela aparece com o rótulo "02"

### Requirement: Chamada para ação
O bloco `cta` SHALL mostrar uma faixa na cor da marca com pré-título, título, texto e um botão para o link informado, abrindo em nova aba com `rel="noopener noreferrer"` quando marcado; sem texto ou sem link do botão, o botão não é desenhado.

#### Scenario: Link do WhatsApp em nova aba
- **WHEN** o botão aponta para `https://wa.me/5516993502625` com "abrir em nova aba" marcado
- **THEN** o link sai com `target="_blank"` e `rel="noopener noreferrer"`

### Requirement: Vídeo
O bloco `video` SHALL mostrar um vídeo do YouTube (pelo link) ou um MP4 enviado, na orientação horizontal ou vertical escolhida, com pré-título, título, texto e botão ao lado; o YouTube usa o player de `youtube-nocookie.com` carregado só perto da tela, e o MP4 não é baixado antes de o visitante tocar, salvo com "tocar sozinho" ligado, quando toca mudo e em repetição. Havendo os dois, vale o MP4. Sem vídeo válido, só o texto é desenhado.

#### Scenario: Link de Shorts
- **WHEN** o admin cola `https://www.youtube.com/shorts/7-y0py3nPrQ`
- **THEN** o player mostra o vídeo `7-y0py3nPrQ` em formato vertical quando a orientação é vertical

#### Scenario: Link que não é do YouTube
- **WHEN** o link informado não é um endereço de vídeo do YouTube
- **THEN** nenhum player é desenhado e a página responde 200

#### Scenario: Arquivo que não é MP4
- **WHEN** o admin envia um arquivo que não é `video/mp4` ou tem mais de 30 MB
- **THEN** o bloco é salvo sem trocar o vídeo e o painel mostra o motivo

### Requirement: Duplicação dos blocos novos
Ao duplicar página, seção ou bloco, o sistema SHALL copiar a configuração dos blocos novos e, nas fotos do portfólio, o título e a categoria; os arquivos de imagem e de vídeo são compartilhados entre o original e a cópia.

#### Scenario: Duplicar um portfólio
- **WHEN** o admin duplica um bloco `portfolio` com 5 fotos em 3 categorias
- **THEN** a cópia tem as 5 fotos com os mesmos títulos e categorias e o mesmo filtro
