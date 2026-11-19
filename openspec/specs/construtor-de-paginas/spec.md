# construtor-de-paginas Specification

## Purpose
Monta o site público de cada tenant com páginas, categorias, seções e blocos cadastrados no `/admin`, renderizados pelo tema do tenant (`templates/themes/<tema>/`). Implementado em `App\Controller\pub\WabPublicController`, `App\Controller\pub\SitemapController`, `App\Controller\admin\AdminContentController`, `App\Service\DuplicatorService`, `App\Entity\Enum\BlockType` e nos templates `page.html.twig`, `category.html.twig` e `_block.html.twig` de cada tema. O `MainController` define rotas com os mesmos nomes, mas as do `WabPublicController` são as que valem.

## Requirements

### Requirement: Home do site
O sistema SHALL, em `/`, renderizar a página definida em `Tenant.homePage` com o template `page.html.twig` do tema; sem página definida, SHALL renderizar `home.html.twig` com os banners ativos ordenados por `position` e `id`.

#### Scenario: Site com página home
- **WHEN** o tenant tem `homePage` definida
- **THEN** `/` mostra as seções dessa página

#### Scenario: Site sem página home
- **WHEN** o tenant não tem `homePage`
- **THEN** `/` mostra o `home.html.twig` com o primeiro banner ativo como destaque e a lista de banners ativos

### Requirement: Página pública por slug
O sistema SHALL exibir em `/p/{slug}` a primeira página do tenant com aquele slug e responder 404 "Página não encontrada." quando não existe. Não há rascunho nem status de publicação: `publishedAt` só ordena listagens, e o slug não é único no banco.

#### Scenario: Página com data de publicação futura
- **WHEN** uma página tem `publishedAt` no futuro
- **THEN** `/p/{slug}` exibe a página normalmente

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

### Requirement: Fundo da seção
O sistema SHALL aplicar o fundo da seção pelo `bgType`: `color` usa `bgColor`; `gradient` usa `linear-gradient(bgGradient)`; `image` sobrepõe a imagem com `bgImagePosition` e opacidade `bgImageOpacity`/100; `video` toca o MP4 em loop, mudo, com uma camada de `bgColor` a 50% quando há cor.

#### Scenario: Fundo de vídeo com cor
- **WHEN** a seção tem `bgType` video, um vídeo e `bgColor` preenchida
- **THEN** o vídeo toca em autoplay, mudo e em loop, coberto pela cor com opacidade 0,5

### Requirement: Blocos por tipo
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 23 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact, hero_split, highlights, portfolio, statement, steps, cta, video, blog_posts); tipo desconhecido não renderiza nada.

#### Scenario: Bloco de newsletter com a newsletter desligada
- **WHEN** a seção tem um bloco `newsletter` e o tenant tem `newsletterEnabled` falso
- **THEN** o bloco não é renderizado

#### Scenario: Tipo que não existe
- **WHEN** a seção tem um bloco gravado com um tipo que não está em `BlockType`
- **THEN** a página responde 200 e nada é desenhado no lugar desse bloco

### Requirement: Chamada para notícias
O bloco `news_call` SHALL listar as páginas da categoria escolhida e das subcategorias dela, da mais recente para a mais antiga por `publishedAt` (e `id` no empate), limitadas a `itemCount` (3 quando vazio), com link para a categoria; sem categoria escolhida, não lista nada.

#### Scenario: Categoria com subcategorias
- **WHEN** o bloco aponta para uma categoria raiz com 5 páginas próprias e 4 em subcategorias, e `itemCount` é 6
- **THEN** aparecem as 6 páginas mais recentes entre as 9

### Requirement: Página de categoria
O sistema SHALL exibir em `/categoria/{slug}` a categoria do tenant com ícone, descrição, subcategorias, páginas e seções ativas, e responder 404 quando o slug não existe no tenant.

#### Scenario: Categoria inexistente
- **WHEN** alguém abre `/categoria/nao-existe`
- **THEN** a resposta é 404

### Requirement: Subcategoria de um nível
O `/admin` SHALL recusar a criação de subcategoria dentro de uma subcategoria, com a mensagem "Sub-categorias não podem ter sub-categorias.", e SHALL oferecer como categoria de bloco só categorias raiz.

#### Scenario: Subcategoria dentro de subcategoria
- **WHEN** o admin tenta criar uma subcategoria a partir de outra subcategoria
- **THEN** nada é gravado e ele volta para a lista de categorias com o aviso

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

### Requirement: Duplicação de página, seção e bloco
O sistema SHALL duplicar uma página com o título acrescido de " (cópia)", o slug acrescido de "-" e um `uniqid`, posição 999 e cópia de todas as seções e blocos com galeria, depoimentos, logos e membros.

#### Scenario: Duplicar página
- **WHEN** o admin duplica a página "Sobre" de slug `sobre`
- **THEN** surge a página "Sobre (cópia)" com slug `sobre-<id único>` e as mesmas seções e blocos

### Requirement: Rotas antigas de notícias, vídeos e estudos
O sistema SHALL responder 404 em `/videos/{slug}` e `/estudos/{slug}`, e em `/noticias/{slug}` quando o tema do site não é `noticias`. Em `/videos`, `/estudos` e, fora do tema `noticias`, em `/noticias`, SHALL renderizar com lista vazia `videos.html.twig`, `studies.html.twig` e `articles.html.twig` do tema quando o tema tem o template, e responder 404 "Página não encontrada." quando não tem. No tema `noticias`, `/noticias` e `/noticias/{slug}` mostram as notícias do site (capability `noticias`).

#### Scenario: Vídeos num site com tema cetec
- **WHEN** um tenant com tema cetec ou moderno recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque só o tema wab tem `videos.html.twig` e `studies.html.twig`

#### Scenario: Vídeos no tema wab
- **WHEN** um tenant com tema wab recebe `/videos` ou `/estudos`
- **THEN** a página do tema é renderizada com a lista vazia

#### Scenario: Notícias no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias`
- **THEN** o `articles.html.twig` do cetec é renderizado com a lista vazia

#### Scenario: Notícia no tema cetec
- **WHEN** um tenant com tema cetec recebe `/noticias/{slug}`, mesmo com uma notícia publicada com esse slug no banco
- **THEN** a resposta é 404

#### Scenario: Vídeos no tema noticias
- **WHEN** um tenant com tema `noticias` recebe `/videos` ou `/estudos`
- **THEN** a resposta é 404 "Página não encontrada.", porque o tema `noticias` não tem esses templates

### Requirement: Sitemap
O sistema SHALL responder em `/sitemap.xml` um XML com o cabeçalho `X-Robots-Tag: noindex` e, no domínio aberto: a home (prioridade 1.0), uma URL `/p/{slug}` por página do tenant com slug e com data de publicação no passado, menos a página usada como home (prioridade 0.8), e uma URL `/categoria/{slug}` por categoria do tenant que tem conteúdo publicado (prioridade 0.6).

#### Scenario: Sitemap do site
- **WHEN** um robô lê `https://pkwy.com.br/sitemap.xml`
- **THEN** recebe a home, as páginas publicadas e as categorias com conteúdo do tenant 3, com endereços no domínio pkwy.com.br

#### Scenario: Site institucional
- **WHEN** um site com tema cetec e o blog desligado recebe `/sitemap.xml`
- **THEN** o XML traz a home, as páginas e as categorias, sem URLs de notícia, de editoria, de autor nem do blog

#### Scenario: Página que serve de home
- **WHEN** o tenant tem `homePage` definida
- **THEN** essa página aparece só como `https://<domínio>/`, e não também como `/p/{slug}`

#### Scenario: Página em rascunho
- **WHEN** o tenant tem uma página com slug ainda não publicada
- **THEN** ela não aparece no sitemap

### Requirement: Sitemap do portal de notícias
No tema `noticias`, o `/sitemap.xml` SHALL trazer também uma URL por notícia visível que não é de exemplo (prioridade 0.7), uma por editoria com notícia visível e uma por autor aprovado com notícia visível (prioridade 0.5).

#### Scenario: Portal com rascunho
- **WHEN** um site com tema `noticias` tem 3 notícias publicadas e 1 em rascunho
- **THEN** o sitemap traz as 3 publicadas em `/noticias/{slug}` e não traz o rascunho

#### Scenario: Portal só com notícias de exemplo
- **WHEN** um site do tema `noticias` tem apenas notícias de exemplo
- **THEN** o sitemap traz a home e as páginas, e nenhuma URL de notícia, de editoria nem de autor

### Requirement: Data de alteração no sitemap
Todo `lastmod` do `/sitemap.xml` SHALL ser a data e hora reais da última alteração daquele conteúdo, em ISO 8601 com fuso; o da home SHALL ser a alteração mais recente entre as URLs listadas.

#### Scenario: Data de alteração
- **WHEN** uma página foi alterada às 14h32 de 10/11/2026 no horário de Brasília
- **THEN** o `lastmod` dela traz essa data e hora com o fuso, e não a data de hoje

### Requirement: Endereços fora do sitemap
Página com publicação futura, página de busca, cadastro de jornalista, login e painéis MUST NOT entrar no `/sitemap.xml`.

#### Scenario: Login e painéis
- **WHEN** um robô lê o `/sitemap.xml` de qualquer site
- **THEN** o XML não traz `/login`, `/admin`, `/superadmin`, `/busca` nem `/jornalista/cadastro`

### Requirement: Pré-título dos blocos
Nos blocos dos temas cetec e noticias, o pré-título com até 40 caracteres SHALL aparecer como rótulo curto (pílula com texto pequeno em caixa alta), e o pré-título com mais de 40 caracteres SHALL aparecer como destaque legível: sem caixa alta, com texto de pelo menos 14 px, entrelinha folgada, espaço interno de pelo menos 12 px na vertical e 20 px na horizontal e largura máxima de leitura.

#### Scenario: Pré-título longo na página Quem somos
- **WHEN** o bloco tem o pré-título "O AJoPE é o portal de notícias da globo e líder de audiência no jornalismo digital no Brasil. Conheça mais sobre a história do g1."
- **THEN** o texto aparece sem caixa alta, com pelo menos 14 px, e com pelo menos 12 px de espaço acima e abaixo dentro do destaque

#### Scenario: Rótulo curto
- **WHEN** o bloco tem o pré-título "QUEM SOMOS"
- **THEN** ele aparece como hoje, na pílula em caixa alta

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

### Requirement: Últimas do blog
O bloco `blog_posts` SHALL mostrar os posts visíveis mais recentes do site (3 quando a quantidade não é informada, no máximo 12), de todas as categorias ou só da categoria escolhida, com link para a lista do blog; num site sem blog e sem portal de notícias, ou sem post visível, o bloco não é desenhado. No portal de notícias os links apontam para `/noticias`.

#### Scenario: Blog desligado depois
- **WHEN** a home tem um bloco `blog_posts` e o blog do site é desligado
- **THEN** a home responde 200 sem o bloco

#### Scenario: Categoria escolhida
- **WHEN** o bloco aponta para a categoria "Tendências" com quantidade 3
- **THEN** aparecem os 3 posts mais recentes dessa categoria e o link vai para `/blog/categoria/tendencias`

### Requirement: Home sem página montada no tema atelie
Num site com tema `atelie` sem página home definida, `/` SHALL mostrar o primeiro banner ativo do site como destaque (título, subtítulo, botão e imagem), o texto "sobre" do site quando houver e, com o blog ligado, as três publicações mais recentes; sem banner, mostra o nome do site como título.

#### Scenario: Site recém-criado
- **WHEN** um site novo com tema `atelie`, sem banners e sem páginas, recebe `/`
- **THEN** a resposta é 200 com o nome do site entre o cabeçalho e o rodapé do tema

### Requirement: Largura dos blocos no tema atelie
No tema `atelie`, os blocos `banner`, `statement` e `cta` SHALL ocupar a largura inteira da tela, e os demais SHALL ficar na coluna central de até 1200 px; uma seção só com blocos de largura inteira não tem respiro vertical próprio.

#### Scenario: Faixa de chamada no fim da página
- **WHEN** a última seção da página tem só um bloco `cta`
- **THEN** a faixa encosta nas laterais da tela e no rodapé, sem faixa clara entre eles

### Requirement: Prévia do bloco no painel
A criação e a edição de qualquer tipo de bloco no `/admin` SHALL mostrar, ao lado do formulário, uma prévia do bloco desenhada pelos templates do tema do site, dentro de uma seção com o mesmo fundo da seção do bloco, atualizada enquanto o formulário é preenchido, sem gravar nada.

#### Scenario: Texto digitado
- **WHEN** o admin digita "Versatilidade para diferentes espaços" no título de um bloco blurbs4 novo
- **THEN** a prévia mostra esse título no lugar e no estilo em que ele sairá no site, sem que o bloco seja criado

#### Scenario: Mudança no template do tema
- **WHEN** o parcial do bloco no tema do site é alterado
- **THEN** a prévia passa a mostrar o desenho novo, sem nenhuma alteração na prévia

#### Scenario: Edição sem salvar
- **WHEN** o admin muda o texto de um bloco existente e sai da tela sem salvar
- **THEN** o site continua com o texto antigo

### Requirement: Campo vazio na prévia
Na prévia, campo de texto vazio SHALL aparecer com o nome público do campo (o rótulo do formulário, precedido do nome do grupo quando o campo se repete, como "Item 2 — Título"); imagem vazia SHALL aparecer como uma imagem de exemplo; lista sem itens (depoimentos, logos, equipe, fotos) SHALL aparecer com itens de exemplo.

#### Scenario: Pré-título vazio
- **WHEN** o pré-título do bloco está vazio
- **THEN** a prévia mostra "Pré-título" onde o pré-título sairá

#### Scenario: Depoimentos sem nenhum item
- **WHEN** um bloco de depoimentos ainda não tem depoimento
- **THEN** a prévia mostra um depoimento de exemplo com "Nome", "Cargo / Função" e "Depoimento"

### Requirement: Arquivo escolhido na prévia
Uma imagem escolhida no formulário e ainda não salva SHALL aparecer na prévia pelo arquivo do computador do admin, sem ser enviada ao servidor antes de salvar.

#### Scenario: Foto nova do destaque
- **WHEN** o admin escolhe a foto de um bloco "Destaque com foto"
- **THEN** a prévia mostra essa foto no lugar da imagem de exemplo

### Requirement: Prévia restrita ao site
A prévia SHALL só aceitar seção e bloco do site do domínio e só para quem acessa o `/admin` desse site.

#### Scenario: Seção de outro site
- **WHEN** o pedido de prévia traz o id de uma seção de outro site
- **THEN** a resposta é 404
