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
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 25 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact, hero_split, highlights, portfolio, statement, steps, cta, video, blog_posts, accordion, representatives); tipo desconhecido não renderiza nada.

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

### Requirement: Sanfona
O bloco `accordion` ("Sanfona") SHALL mostrar pré-título, título e texto quando preenchidos e, abaixo, os itens na ordem cadastrada, cada um com o título sempre visível e o conteúdo que abre e fecha ao clicar no título, sem recarregar a página. Item sem título não aparece; sem nenhum item com título, nada é desenhado.

#### Scenario: Abrir um item
- **WHEN** o visitante clica no título "Bahia" de uma sanfona fechada
- **THEN** o conteúdo de "Bahia" aparece logo abaixo do título, e um novo clique o fecha

#### Scenario: Item sem título
- **WHEN** a sanfona tem três itens e o segundo está sem título
- **THEN** só o primeiro e o terceiro aparecem

#### Scenario: Sem JavaScript
- **WHEN** a página é aberta com o JavaScript desligado
- **THEN** os itens continuam abrindo e fechando ao clicar no título

### Requirement: Itens abertos da sanfona
Por padrão, a sanfona SHALL deixar um item aberto de cada vez (abrir um fecha o que estava aberto) e começar com todos fechados; o admin SHALL poder marcar "abrir o primeiro item" e "permitir vários abertos ao mesmo tempo".

#### Scenario: Um de cada vez
- **WHEN** "Acre" está aberto e o visitante abre "Alagoas" numa sanfona sem "vários abertos"
- **THEN** "Acre" fecha e "Alagoas" fica aberto

#### Scenario: Vários abertos
- **WHEN** a opção "permitir vários abertos" está marcada e o visitante abre "Acre" e depois "Alagoas"
- **THEN** os dois ficam abertos

#### Scenario: Primeiro aberto
- **WHEN** a opção "abrir o primeiro item" está marcada
- **THEN** a página carrega com o primeiro item visível já aberto

### Requirement: Item de texto da sanfona
Um item do tipo texto SHALL mostrar o HTML do editor de texto rico (parágrafos, títulos, negrito, itálico, sublinhado, listas, citações e links); o HTML SHALL ser sanitizado ao gravar, removendo scripts, estilos, iframes, atributos de evento e links `javascript:`.

#### Scenario: Lista com link
- **WHEN** o admin grava um item de texto com uma lista de três tópicos e um link para `https://exemplo.com.br`
- **THEN** o item aberto mostra a lista com marcadores e o link clicável

#### Scenario: Script colado no texto
- **WHEN** o POST traz no texto de um item `<p onclick="x()">Oi</p><script>alert(1)</script>`
- **THEN** o bloco é gravado com `<p>Oi</p>` e a página não tem o script nem o atributo

### Requirement: Item de tabela da sanfona
Um item do tipo tabela SHALL mostrar uma tabela com as linhas e colunas cadastradas (até 12 colunas e 500 linhas por item), com a primeira linha como cabeçalho quando marcado; quebras de linha da célula são mantidas, e endereços de e-mail e links `http(s)://` ou `www.` dentro da célula viram links. O texto da célula é sempre exibido como texto, nunca como HTML.

#### Scenario: Célula com e-mail e telefone
- **WHEN** uma célula tem "J. G. PACHECO & CIA LTDA", uma quebra de linha e "E-mail: comercial@jgp.com.br"
- **THEN** a célula mostra as duas linhas, com o e-mail como link `mailto:comercial@jgp.com.br`

#### Scenario: HTML digitado na célula
- **WHEN** uma célula tem o texto `<b>X</b>`
- **THEN** a página mostra `<b>X</b>` como texto, sem negrito

#### Scenario: Linha vazia
- **WHEN** o admin grava uma tabela cuja terceira linha tem todas as células vazias
- **THEN** essa linha não é gravada

### Requirement: Tabela da sanfona em telas pequenas
Uma tabela mais larga que a tela SHALL rolar para o lado dentro do item, sem alargar a página; em telas de celular, uma tabela com cabeçalho SHALL mostrar cada linha como um cartão, com o nome da coluna antes de cada valor.

#### Scenario: Tabela de 7 colunas no celular
- **WHEN** um item com tabela de 7 colunas e cabeçalho é aberto numa tela de 390 px
- **THEN** cada linha aparece empilhada, com "Cidade", "Empresa" etc. antes dos valores, e a página não rola para o lado

### Requirement: Sanfona em todos os temas
O bloco `accordion` SHALL ser oferecido na escolha de tipo do `/admin` de qualquer site e desenhado em todos os temas com cores, fontes e bordas do tema e do site: nos temas `cetec`, `moderno`, `wab` e `noticias` com o desenho do cetec, legível no modo escuro e em seção de fundo escuro; no `atelie`, com desenho próprio.

#### Scenario: Modo escuro
- **WHEN** um site com tema cetec é visto no modo escuro e tem uma sanfona com tabela
- **THEN** títulos, texto e tabela aparecem claros sobre fundo escuro, com contraste de pelo menos 4,5:1

#### Scenario: Tema atelie
- **WHEN** um site com tema `atelie` tem uma página com uma sanfona
- **THEN** a página responde 200 e a sanfona usa as fontes e as linhas finas do tema

### Requirement: Edição da sanfona no painel
No formulário do bloco `accordion`, o admin SHALL poder acrescentar, remover e mudar a ordem dos itens, escolher em cada item entre texto e tabela sem perder o que já digitou no outro tipo, e, na tabela, acrescentar e remover linhas e colunas e marcar se a primeira linha é cabeçalho. Uma tabela de qualquer tamanho dentro dos limites SHALL ser gravada inteira.

#### Scenario: Reordenar itens
- **WHEN** o admin move o terceiro item para o primeiro lugar e salva
- **THEN** a página mostra esse item em primeiro

#### Scenario: Trocar de tipo e voltar
- **WHEN** o admin muda um item de tabela para texto e depois de volta para tabela, antes de salvar
- **THEN** a tabela digitada continua lá

#### Scenario: Tabela grande
- **WHEN** o admin grava uma sanfona com 27 itens e 400 linhas de 7 colunas no total
- **THEN** todas as linhas e colunas são gravadas e aparecem na página

### Requirement: Colar tabela de planilha
Na tabela de um item, o admin SHALL poder colar um trecho copiado do Excel, do Google Planilhas ou de uma tabela de página web, e a tabela do item SHALL passar a ter as linhas e colunas do trecho colado, mantendo as quebras de linha das células.

#### Scenario: Colar do Excel
- **WHEN** o admin copia 5 linhas e 4 colunas do Excel e cola na área de colar da tabela
- **THEN** a tabela do item fica com 5 linhas e 4 colunas com os mesmos valores

#### Scenario: Colar de uma página web
- **WHEN** o admin copia uma tabela de uma página web e cola na área de colar
- **THEN** a tabela do item recebe as células dessa tabela, uma linha por linha da tabela copiada

### Requirement: Sanfona na prévia do painel
Na prévia do bloco, uma sanfona sem item SHALL aparecer com itens de exemplo (um de texto e um de tabela); título de item vazio SHALL aparecer com o nome do campo, e células vazias da tabela SHALL continuar vazias.

#### Scenario: Sanfona nova
- **WHEN** o admin escolhe o tipo Sanfona e ainda não criou item
- **THEN** a prévia mostra dois itens de exemplo, um com texto e outro com tabela

#### Scenario: Célula vazia
- **WHEN** uma célula da tabela de um item está vazia
- **THEN** a célula aparece vazia na prévia

### Requirement: Imagem na célula da sanfona
Cada célula da tabela de um item da sanfona SHALL poder ter uma imagem, sozinha ou acima do texto da célula. No site, a imagem SHALL sair como miniatura de até 160 px de largura e 80 px de altura, sem distorcer, com o texto alternativo igual ao texto da célula ou, sem texto, ao cabeçalho da coluna.

#### Scenario: Logo na coluna Logo
- **WHEN** a célula da coluna "Logo" da linha de "J. G. PACHECO & CIA LTDA" tem uma imagem e nenhum texto
- **THEN** a célula mostra a miniatura do logo com o texto alternativo "Logo"

#### Scenario: Célula só com imagem no celular
- **WHEN** a tabela tem cabeçalho e é vista numa tela de 390 px
- **THEN** o cartão da linha mostra "Logo" seguido da imagem, e a célula não é escondida como vazia

#### Scenario: Linha só com imagem
- **WHEN** o admin grava uma linha em que todas as células estão vazias, menos uma com imagem
- **THEN** a linha é gravada

### Requirement: Escolher a imagem da célula no painel
No editor da tabela, cada célula SHALL ter um botão para escolher uma imagem do computador, mostrar a miniatura depois de enviada e permitir trocar ou remover a imagem; acrescentar, remover e mover linhas e colunas SHALL levar a imagem junto com a célula.

#### Scenario: Remover a coluna do logo
- **WHEN** o admin remove a segunda coluna de uma tabela cujas células dessa coluna têm imagem
- **THEN** as imagens saem junto com a coluna e as outras células continuam com as suas

#### Scenario: Colar planilha numa tabela com logos
- **WHEN** o admin cola uma planilha na área de colar de um item cuja tabela tinha logos
- **THEN** a tabela passa a ter só o texto colado, e "Desfazer colagem" traz de volta a tabela com os logos

### Requirement: Envio de imagem do painel
O painel SHALL oferecer um envio de imagem que grava o arquivo na hora em que o admin o escolhe e devolve o nome gravado, aceitando só JPG, PNG, WebP ou GIF de até 5 MB, só para usuário logado no painel do site e com o token do formulário; o arquivo recusado SHALL ter o motivo mostrado ao admin.

#### Scenario: Arquivo que não é imagem
- **WHEN** o admin escolhe um PDF para a célula
- **THEN** nada é gravado e o painel mostra que só são aceitas imagens JPG, PNG, WebP ou GIF

#### Scenario: Sem login
- **WHEN** o endereço de envio recebe um arquivo sem sessão do painel
- **THEN** nada é gravado e a resposta redireciona para o login

#### Scenario: Token inválido
- **WHEN** o envio chega com o token do formulário ausente ou errado
- **THEN** nada é gravado e a resposta é 403

### Requirement: Mapa de representantes
O bloco `representatives` SHALL mostrar pré-título, título e o mapa do Brasil com os 27 estados e, ao lado do mapa (abaixo, no celular), a chamada do bloco até o visitante escolher um estado. Sem registro e sem mensagem para estados sem representante, nada é desenhado.

#### Scenario: Página aberta
- **WHEN** a página com o mapa é aberta
- **THEN** aparecem o mapa com os 27 estados e, ao lado, a chamada "Clique no mapa e encontre o representante mais próximo da sua região"

### Requirement: Escolher o estado no mapa
Ao clicar num estado do mapa, ou escolhê-lo no seletor de estados do bloco, o cartão ao lado SHALL mostrar o nome do estado e os registros dele na ordem cadastrada, ou a mensagem para estados sem representante; o estado escolhido SHALL ficar na cor primária do site, sem recarregar a página. Um endereço terminado em `#` com o identificador do estado SHALL abrir com esse estado escolhido.

#### Scenario: Estado com representantes
- **WHEN** o visitante clica na Bahia, que tem dois registros
- **THEN** a Bahia fica na cor primária e o cartão mostra "Bahia" e os dois registros

#### Scenario: Estado sem representante
- **WHEN** o visitante clica no Amapá, que não tem registro
- **THEN** o cartão mostra "Amapá" e a mensagem para estados sem representante

#### Scenario: Teclado
- **WHEN** o visitante chega ao mapa pelo Tab e aperta Enter sobre um estado
- **THEN** o cartão mostra esse estado, como no clique

### Requirement: Registro do mapa de representantes
Cada registro SHALL ter estado (UF), imagem opcional e texto formatado; no cartão, a imagem aparece à esquerda do texto, em miniatura de até 160 × 80 px sem distorcer. O texto SHALL aceitar parágrafos, quebras de linha, negrito, itálico, sublinhado, listas, links `http(s)`, `mailto:` e `tel:`, e título só de nível `h3`; ao gravar, outros títulos viram `h3` e scripts, estilos e atributos de evento são removidos. Registro sem estado válido, ou sem texto e sem imagem, é descartado.

#### Scenario: Título de outro nível
- **WHEN** o admin grava um registro com `<h2>Big Handra Equipamentos</h2>`
- **THEN** o registro é gravado com `<h3>Big Handra Equipamentos</h3>`

#### Scenario: Telefone clicável
- **WHEN** o texto tem um link para `tel:+556799810726`
- **THEN** o link é mantido e abre a ligação

### Requirement: Alinhamento dos registros do estado
Quando pelo menos um registro do estado tem imagem, os registros sem imagem do mesmo cartão SHALL ganhar à esquerda um espaço vazio da largura do logo, para todos os textos começarem alinhados; sem nenhuma imagem no estado, não há recuo. No celular, onde o logo fica acima do texto, o espaço vazio não aparece.

#### Scenario: Um registro com logo e outro sem
- **WHEN** o Amazonas tem "Dani Comércio" sem logo e "Hospitalar Com. Rep." com logo
- **THEN** o texto de "Dani Comércio" começa na mesma posição do texto de "Hospitalar Com. Rep."

#### Scenario: Estado sem nenhum logo
- **WHEN** o Acre tem um registro sem logo
- **THEN** o texto do registro começa na borda do cartão, sem recuo

### Requirement: Cores do mapa
O mapa SHALL ser desenhado em SVG no próprio HTML, sem imagem externa: estados com registro num tom neutro, estados sem registro num tom mais claro, o estado sob o ponteiro e o escolhido na cor primária do site, e contornos na cor do fundo; no modo escuro e em seção de fundo escuro os tons SHALL se inverter, mantendo os estados distinguíveis.

#### Scenario: Cor do site
- **WHEN** o site tem a cor primária #e85d04 e o visitante escolhe Mato Grosso do Sul
- **THEN** Mato Grosso do Sul aparece em #e85d04 e o cartão do estado usa a mesma cor de fundo

### Requirement: Mapa de representantes sem JavaScript
Com o JavaScript desligado, o bloco SHALL mostrar um cartão por estado com registro e o cartão da mensagem para os demais estados, e clicar num estado do mapa SHALL levar ao cartão dele.

#### Scenario: Sem JavaScript
- **WHEN** a página é aberta sem JavaScript e o visitante clica no Acre
- **THEN** a página rola até o cartão do Acre

### Requirement: Mapa de representantes no celular
Em telas estreitas, o mapa SHALL ocupar a largura da tela com o cartão abaixo dele, sem rolagem lateral, e o seletor de estados SHALL aparecer acima do mapa para os estados pequenos demais para tocar.

#### Scenario: Distrito Federal no celular
- **WHEN** o visitante numa tela de 390 px escolhe "Distrito Federal" no seletor
- **THEN** o cartão do Distrito Federal aparece abaixo do mapa e o DF fica na cor primária

### Requirement: Mapa de representantes em todos os temas
O bloco `representatives` SHALL ser oferecido na escolha de tipo do `/admin` de qualquer site e desenhado em todos os temas: em `cetec`, `moderno`, `wab` e `noticias` com o desenho do cetec; no `atelie`, com desenho próprio.

#### Scenario: Tema atelie
- **WHEN** um site com tema `atelie` tem uma página com o mapa
- **THEN** a página responde 200 e o mapa usa as cores do site e as fontes do tema

### Requirement: Edição do mapa de representantes no painel
No formulário do bloco `representatives`, o admin SHALL poder preencher pré-título, título, chamada e mensagem para estados sem representante, filtrar a lista de registros por estado e acrescentar, editar, mover e remover registros, enviando o logo de cada um; uma lista de qualquer tamanho SHALL ser gravada inteira.

#### Scenario: Novo registro com o filtro ligado
- **WHEN** a lista está filtrada pela Bahia e o admin clica em "Novo representante"
- **THEN** o registro novo já vem com o estado Bahia

#### Scenario: Lista grande
- **WHEN** o admin grava 120 registros
- **THEN** os 120 são gravados e aparecem nos cartões dos estados

### Requirement: Mapa de representantes na prévia do painel
Na prévia do bloco, um mapa sem registro SHALL aparecer com registros de exemplo em três estados.

#### Scenario: Bloco novo
- **WHEN** o admin escolhe o tipo "Mapa de representantes" sem cadastrar registro
- **THEN** a prévia mostra o mapa e cartões de exemplo de três estados

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

### Requirement: Banner só com a imagem em todos os temas
A opção "Só a imagem" SHALL funcionar do mesmo jeito nos temas cetec, moderno, wab, noticias e atelie, respeitando as opções "100% de largura" e "100% de altura" e o espaçamento que cada tema já dá à seção de banner.

#### Scenario: Mesmo banner em dois temas
- **WHEN** a mesma página com um banner "só a imagem" é aberta num site do tema cetec e num do tema atelie
- **THEN** os dois mostram só a imagem inteira, sem camada escura, sem texto e sem botões

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

### Requirement: Banner só com a imagem na prévia do painel
A prévia do bloco SHALL refletir a opção "Só a imagem" assim que ela é ligada ou desligada, sem salvar, mostrando o banner como ele sairá no site.

#### Scenario: Ligar a opção com a prévia aberta
- **WHEN** o admin liga "Só a imagem" num banner com título e botão preenchidos
- **THEN** a prévia passa a mostrar só a imagem, sem o título, sem o botão e sem a camada escura

### Requirement: Banner com slides dos dois tipos
Num banner com slides ativos "só a imagem" e slides com texto, o banner SHALL seguir o desenho e a altura dos slides com texto do tema, como carrossel; cada slide "só a imagem" SHALL mostrar só a sua imagem preenchendo a área, recortada nas bordas quando a proporção for outra, sem camada, degradê, zoom, texto nem botões. O formulário do painel SHALL avisar isso.

#### Scenario: Arte entre slides com texto
- **WHEN** um carrossel tem o slide 1 com texto e botão e o slide 2 "só a imagem" com título guardado "Promoção"
- **THEN** o slide 1 sai com camada, título e botão, e o slide 2 só com a imagem, sem "Promoção", na mesma altura do slide 1

#### Scenario: Mesmo banner nos cinco temas
- **WHEN** a mesma página com esse carrossel é aberta em sites dos temas cetec, moderno, wab, noticias e atelie
- **THEN** em todos o slide "só a imagem" sai sem camada, texto e botões, e o slide com texto sai como antes desta mudança
