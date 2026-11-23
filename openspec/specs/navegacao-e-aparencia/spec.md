# navegacao-e-aparencia Specification

## Purpose
Define como cada site se apresenta: tema, cores claras e escuras, fontes, logos, cabeçalho com menu (normal ou Modo Landing Page), itens de menu personalizados e Google Analytics, a partir dos dados do Tenant. Implementado em `App\Twig\TenantExtension` (`tenant_css_vars()`, `is_dark_color()`, globais `currentTenant`, `headerCategories`, `footerPages`, `footerCategories`), em `WabPublicController::theme()`, em `AdminContentController::settings()` e nos templates `base`, `_header` e `_footer` de cada tema. As regras abaixo foram lidas no tema cetec, o único usado pelos tenants reais.

## Requirements

### Requirement: Tema do tenant
O sistema SHALL renderizar as páginas públicas a partir de `templates/themes/<Tenant.theme>/`, usando `moderno` quando não há tenant; o tema padrão de um tenant novo é `wab`, e os temas existentes são `cetec`, `moderno`, `wab`, `noticias` e `atelie`, todos oferecidos no `/admin/settings` e no cadastro e na edição do tenant no `/superadmin`.

#### Scenario: Troca de tema pelo admin
- **WHEN** o admin do site escolhe outro tema em `/admin/settings`
- **THEN** as páginas públicas passam a usar os templates da pasta desse tema

#### Scenario: Tema de notícias
- **WHEN** o SuperAdmin escolhe o tema "Notícias (portal)" para um site
- **THEN** as páginas públicas desse site passam a usar `templates/themes/noticias/`, e o menu do painel do site passa a mostrar "Notícias"

#### Scenario: Tema Ateliê
- **WHEN** o SuperAdmin escolhe o tema "Ateliê" para um site que já tem páginas montadas
- **THEN** todas as páginas, categorias e perfis de equipe do site respondem 200 com a aparência do tema, sem perder conteúdo

### Requirement: Variáveis de cor e fontes
A função `tenant_css_vars()` SHALL gerar um `<style>` com `--color-primary`, `--color-secondary` e as versões RGB a partir das cores do tenant, trocadas pelas versões escuras em `[data-theme="dark"]`, `.dark`, `.wab-section-dark` e `.mp-section-dark`, mais o `@import` do Google Fonts com as famílias e os pesos usados e as regras de fonte, peso e tamanho de h1 a h5 de `fontSettings`, com a reserva `serif` na Playfair Display e `sans-serif` nas demais.

#### Scenario: Barra superior ligada
- **WHEN** `navigationSettings.topBarEnabled` está ligado
- **THEN** `--topbar-height` vale 40px; desligado, vale 0px

#### Scenario: Título em Open Sans
- **WHEN** o tenant tem `fontSettings.h1` com a família "Open Sans" e o peso 700
- **THEN** o `@import` do Google Fonts pede a Open Sans com o peso 700, e o h1 do site sai com `font-family: 'Open Sans', sans-serif` e `font-weight: 700`

### Requirement: Cor considerada escura
A função `is_dark_color()` SHALL considerar escura a cor cuja luminância 0,299·R + 0,587·G + 0,114·B é menor que 170, e também a cor vazia ou que não tem 3 ou 6 dígitos hexadecimais.

#### Scenario: Cor clara
- **WHEN** a cor é `#f8fafc`
- **THEN** a função devolve falso

### Requirement: Seção escura e fundos alternados
O sistema SHALL marcar como escura (classe `mp-section-dark` e texto branco) a seção com fundo de imagem, vídeo ou gradiente, ou com cor que `is_dark_color()` considera escura; seções sem fundo definido SHALL alternar `auto-bg-a` e `auto-bg-b`, com `bgColorLight1`/`bgColorLight2` no modo claro e `bgColorDark1`/`bgColorDark2` no escuro.

#### Scenario: Três seções sem fundo
- **WHEN** uma página tem três seções seguidas sem fundo definido
- **THEN** elas recebem `auto-bg-a`, `auto-bg-b` e `auto-bg-a`

### Requirement: Menu do cabeçalho
Fora do Modo Landing Page, o cabeçalho SHALL mostrar "Início" e as categorias raiz com `showInHeader`, em ordem alfabética, só quando a categoria tem páginas ou subcategorias com páginas; subcategorias com páginas aparecem num menu suspenso.

#### Scenario: Categoria vazia
- **WHEN** uma categoria raiz marcada para o cabeçalho não tem páginas nem subcategorias com páginas
- **THEN** ela não aparece no menu

### Requirement: Menu do Modo Landing Page
Com `landingPageMode` ligado e `homePage` definida, o cabeçalho SHALL listar as seções ativas da home com `showInMenu`, com link `#section-{id}` (precedido do caminho da home fora dela) e rótulo `titlePart1` + `titlePart2`; sem título, o rótulo vem do 1º bloco (banner e hero_split: Início; blurbs4: Recursos; team: Equipe; contact e cta: Contato; gallery e portfolio: Portfólio; statement: Sobre; steps: Como trabalhamos; highlights: Diferenciais; video: Vídeo; accordion: Informações) ou "Seção N".

#### Scenario: Seção de contato sem título
- **WHEN** a terceira seção da home não tem título e o primeiro bloco é do tipo `contact`
- **THEN** o menu mostra "Contato" com link para `#section-<id da seção>`

#### Scenario: Landing page sem home
- **WHEN** `landingPageMode` está ligado mas o tenant não tem `homePage`
- **THEN** o cabeçalho usa o menu normal de categorias

#### Scenario: Seção de portfólio sem título
- **WHEN** uma seção da home não tem título e o primeiro bloco é do tipo `portfolio`
- **THEN** o menu mostra "Portfólio"

#### Scenario: Seção de sanfona sem título
- **WHEN** uma seção da home não tem título e o primeiro bloco é do tipo `accordion`
- **THEN** o menu mostra "Informações"

### Requirement: Itens de menu personalizados
Ao salvar `/admin/settings`, o sistema SHALL descartar itens sem rótulo ou sem URL, acrescentar `https://` à URL que não começa com `http://`, `https://`, `//`, `mailto:`, `tel:`, `/` ou `#`, e gravar o alvo como `_blank` ou `_self`; o cabeçalho SHALL mostrar só os itens ativos. Salvar o tenant no `/superadmin` mantém os itens existentes.

#### Scenario: URL sem protocolo
- **WHEN** o admin cadastra o item "Blog" com URL `blog.cliente.com.br`
- **THEN** o item é gravado com `https://blog.cliente.com.br`

### Requirement: Links externos nos itens de bloco
Ao salvar um bloco, o sistema SHALL apagar `externalUrl` dos itens cujo `link` não é `external` e, nos externos, acrescentar `https://` à URL que não tem esquema, não começa com `//`, `/`, `#`, `mailto:` ou `tel:`.

#### Scenario: Item apontando para página interna
- **WHEN** um item de bloco é salvo com `link` diferente de `external` e uma URL externa preenchida
- **THEN** a URL externa é descartada

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

### Requirement: Google Analytics
O sistema SHALL incluir o `gtag.js` com o id do tenant só quando `googleAnalyticsId` está preenchido.

#### Scenario: Sem id do Analytics
- **WHEN** o tenant não tem `googleAnalyticsId`
- **THEN** a página não carrega o script do Google Analytics

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

### Requirement: Peso dos títulos dentro da faixa da família
O peso de cada título SHALL ficar dentro da faixa que a família tem no Google Fonts: de 300 a 800 na Open Sans, de 400 a 900 na Playfair Display, de 200 a 800 na Manrope e de 100 a 900 nas demais. Um peso gravado fora da faixa SHALL ser trocado pelo mais próximo dentro dela, no `@import` e na regra do título, sem mudar o valor gravado em `fontSettings`.

#### Scenario: Open Sans com peso 900
- **WHEN** o tenant tem `fontSettings.h1` com a família "Open Sans" e o peso 900
- **THEN** o `@import` pede a Open Sans com o peso 800, o h1 sai com `font-weight: 800` e a edição do tenant continua mostrando o peso 900

#### Scenario: Playfair Display com peso 300
- **WHEN** o tenant tem `fontSettings.h2` com a família "Playfair Display" e o peso 300
- **THEN** o `@import` pede a Playfair Display com o peso 400 e o h2 sai com `font-weight: 400`

#### Scenario: Manrope com peso 900
- **WHEN** o tenant tem `fontSettings.h1` com a família "Manrope" e o peso 900
- **THEN** o h1 sai com `font-weight: 800`

### Requirement: Família dos títulos fora da lista
Uma família gravada em `fontSettings` que não está entre as oferecidas na edição do tenant SHALL ser tratada como Outfit no `@import` e na regra do título, e o texto gravado MUST NOT entrar no CSS da página.

#### Scenario: Família desconhecida vinda de importação
- **WHEN** um tenant importado de um ZIP tem `fontSettings.h3` com a família "Comic Sans MS"
- **THEN** o `@import` pede a Outfit, o h3 sai com `font-family: 'Outfit', sans-serif` e o nome "Comic Sans MS" não aparece no HTML da página

### Requirement: Cabeçalho da notícia no tema noticias
No tema `noticias`, a partir de 768 px de largura, a página da notícia SHALL usar a Open Sans e: título com o tamanho e o peso do H1 do tenant, entrelinha 1,2 e espaço entre letras de −0,045 em; linha fina com 15 px, peso 400 e entrelinha de 22 px; autor com 14 px e peso 600; data com 12 px; legenda da foto com 12 px; coluna com até 648 px. As cores SHALL continuar as do tema.

#### Scenario: Notícia do portal da AJoPE no computador
- **WHEN** o tenant tem H1 em Open Sans, 2.5rem e peso 700 e alguém abre uma notícia a 1280 px
- **THEN** o título sai com 40 px e peso 700, a linha fina com 15 px e o autor com 14 px e peso 600

### Requirement: Texto da notícia no tema noticias
No tema `noticias`, a partir de 768 px, o texto da notícia SHALL ter 18 px, entrelinha de 28 px, espaço entre letras de −0,015 em e 32 px entre parágrafos; o intertítulo (h2 do texto) SHALL ter o tamanho e o peso do H2 do tenant e entrelinha de 28 px; os itens de lista, 20 px e entrelinha de 28 px; a citação, 20 px, peso 700, entrelinha de 28 px e sem itálico; os links, peso 700 e sem sublinhado.

#### Scenario: Texto no computador
- **WHEN** alguém abre uma notícia a 1280 px
- **THEN** os parágrafos saem com 18 px e entrelinha de 28 px, separados por 32 px

#### Scenario: Citação no texto
- **WHEN** a notícia tem uma citação
- **THEN** a citação sai em negrito, sem itálico, com 20 px

### Requirement: Tipografia da lista de notícias no tema noticias
No tema `noticias`, na home, em `/noticias`, na página da editoria e na página do autor, cada item SHALL ter, a partir de 768 px: chapéu com 16 px e peso 600; título com o tamanho e o peso do H2 do tenant, entrelinha 1,1 e espaço entre letras de −0,035 em, igual na primeira notícia e nas outras; linha fina com 16 px e entrelinha de 24 px; links relacionados com 14 px; data e editoria com 12 px e entrelinha de 18 px.

#### Scenario: Home do portal no computador
- **WHEN** o tenant tem H2 em Open Sans, 1.5rem e peso 700 e alguém abre a home a 1280 px
- **THEN** os títulos da lista saem com 24 px e peso 700, os chapéus com 16 px e peso 600 e as datas com 12 px

### Requirement: Títulos nos quadros laterais do tema noticias
No tema `noticias`, os títulos das notícias dentro dos quadros "Leia também" e "Mais lidas" SHALL ter 16 px e peso 700.

#### Scenario: Quadro Mais lidas
- **WHEN** a home mostra o quadro "Mais lidas"
- **THEN** os títulos das notícias do quadro saem com 16 px e peso 700

### Requirement: Títulos do tema noticias no celular
Abaixo de 768 px de largura, no tema `noticias`, o título da página (h1) SHALL ter 70% do tamanho do H1 do tenant, e o título dos itens da lista, 83,3% do tamanho do H2. O intertítulo do texto SHALL manter o tamanho do H2. Os itens de lista do texto e as citações SHALL ter 18 px.

#### Scenario: Notícia no celular
- **WHEN** o tenant tem H1 com 2.5rem e alguém abre uma notícia a 390 px
- **THEN** o título sai com 28 px

#### Scenario: Lista no celular
- **WHEN** o tenant tem H2 com 1.5rem e alguém abre a home a 390 px
- **THEN** os títulos da lista saem com 20 px

### Requirement: Tamanho padrão dos títulos no tema noticias
No tema `noticias`, quando o tenant não tem tamanho gravado para H1, H2 ou H3 ("Padrão do Tema"), o tema SHALL usar 40 px, 24 px e 20 px, respectivamente, nas regras de tipografia acima. Um tamanho gravado que não seja um número seguido de `px`, `rem` ou `em` SHALL ser tratado como não gravado.

#### Scenario: Portal sem tamanhos gravados
- **WHEN** o tenant do tema `noticias` tem "Padrão do Tema" no tamanho de H1 e H2
- **THEN** a notícia sai com o título em 40 px a 1280 px e em 28 px a 390 px, e a lista com títulos de 24 px

### Requirement: Rótulos dos quadros laterais fora da tipografia do H2
No tema `noticias`, os cabeçalhos dos quadros "Leia também", "Mais lidas" e de páginas da editoria SHALL manter 14 px e peso 600, qualquer que seja o tamanho ou o peso do H2 gravado no tenant.

#### Scenario: H2 grande no tenant
- **WHEN** o tenant tem H2 com 1.5rem e peso 700
- **THEN** o cabeçalho "Mais lidas" continua com 14 px e peso 600

### Requirement: Tema atelie completo
No tema `atelie`, todas as rotas públicas que os outros temas institucionais atendem (home, `/p/{slug}`, `/categoria/{slug}`, `/membro/{slug}`, contato, newsletter e, com o blog ligado, `/blog`, `/blog/categoria/{slug}` e `/blog/{slug}`) SHALL responder com o cabeçalho e o rodapé do tema, e todos os tipos de bloco SHALL ter desenho próprio do tema.

#### Scenario: Página com todos os blocos
- **WHEN** um site com tema `atelie` tem uma página com um bloco de cada tipo
- **THEN** a página responde 200 e cada bloco preenchido aparece

#### Scenario: Perfil de membro
- **WHEN** o site recebe `/membro/{slug}` de um membro com página de detalhe, no layout clássico ou no moderno
- **THEN** a resposta é 200 com o perfil no desenho do tema

#### Scenario: Rotas que o tema não tem
- **WHEN** o site recebe `/videos`, `/estudos` ou `/noticias`
- **THEN** a resposta é 404 "Página não encontrada."

### Requirement: Aparência do tema atelie
O tema `atelie` SHALL seguir o layout de referência aprovado (fundo claro, títulos em Manrope, texto em DM Sans, cantos retos), usando a cor primária do site em botões, destaques e na faixa de chamada, a secundária nos destaques sobre fundo escuro, a cor de fundo clara 1 do site no fundo da página e a cor clara 2 na faixa de etapas e na faixa de destaques; seções sem fundo definido não alternam cor. O tema só tem modo claro e não mostra seletor de aparência.

#### Scenario: Cores do site
- **WHEN** o site muda a cor primária de marrom para verde
- **THEN** botões, destaques e a faixa de chamada passam a verde, sem alterar o tema

#### Scenario: Visitante com modo escuro salvo
- **WHEN** o visitante escolheu o modo escuro em outro site da plataforma e abre um site com tema `atelie`
- **THEN** o site aparece no modo claro

#### Scenario: Contraste
- **WHEN** a cor primária do site é clara demais para texto sobre o fundo
- **THEN** os textos na cor da marca usam a variante legível, com contraste de pelo menos 4,5:1

### Requirement: Cabeçalho do tema atelie
O cabeçalho do tema `atelie` SHALL ficar fixo no topo, com a logo, o nome do site e a frase curta do site quando preenchida, e o menu: no Modo Landing Page com home, as seções da home; fora dele, "Início", as categorias com conteúdo, as páginas marcadas para o cabeçalho e os itens personalizados ativos. No celular o menu abre por um botão com `aria-expanded`.

#### Scenario: Menu no celular
- **WHEN** o visitante toca no botão do menu numa tela de 390 px
- **THEN** os itens aparecem, o botão passa a `aria-expanded="true"`, e tocar num item fecha o menu

#### Scenario: Site sem logo
- **WHEN** o site não tem logo cadastrada
- **THEN** o cabeçalho mostra o nome do site no lugar da imagem

### Requirement: Botão de ação do cabeçalho
O `/admin/settings` SHALL permitir informar o texto e o link de um botão de ação do cabeçalho e uma frase curta do site; o tema `atelie` SHALL desenhar o botão quando texto e link estão preenchidos. Salvar o `/admin/settings` ou o site no `/superadmin` MUST manter esses três valores, e os temas que não os usam não mudam.

#### Scenario: Botão preenchido
- **WHEN** o admin informa "Solicite um orçamento" e o link `#contato`
- **THEN** o cabeçalho do tema `atelie` mostra o botão com esse texto e esse destino

#### Scenario: Salvar pelo SuperAdmin
- **WHEN** o SuperAdmin salva a edição do site depois de o admin ter cadastrado o botão
- **THEN** o botão continua no cabeçalho

#### Scenario: Site no tema cetec
- **WHEN** um site com tema cetec preenche o botão de ação
- **THEN** o cabeçalho do cetec continua igual

### Requirement: Rodapé do tema atelie
O rodapé do tema `atelie` SHALL mostrar o nome e o texto curto do site, o endereço e os horários de funcionamento, telefone, e-mail e redes sociais cadastrados, as páginas marcadas para o rodapé, o formulário da newsletter quando ela está ligada e o crédito da WAB; cada dado ausente é omitido sem deixar rótulo vazio. Com link de WhatsApp cadastrado, o site SHALL mostrar um botão flutuante de WhatsApp em todas as páginas.

#### Scenario: Newsletter desligada
- **WHEN** o site tem `newsletterEnabled` falso
- **THEN** o rodapé não mostra o formulário de newsletter

#### Scenario: Sem WhatsApp
- **WHEN** o site não tem link de WhatsApp
- **THEN** o botão flutuante não aparece

### Requirement: Desempenho e SEO do tema atelie
As páginas do tema `atelie` SHALL sair com o SEO montado pelo mesmo mecanismo dos outros temas, sem CSS, fonte ou JavaScript de outro domínio (salvo o Google Analytics do site, o reCAPTCHA e os vídeos do YouTube, sob demanda), com todas as imagens pela biblioteca de miniaturas e com a imagem do topo pré-carregada.

#### Scenario: Home de demonstração
- **WHEN** o Lighthouse (celular) analisa a home do site de demonstração do tema
- **THEN** acessibilidade, práticas recomendadas e SEO dão 100, e desempenho dá 95 ou mais

#### Scenario: Nenhuma fonte de terceiro
- **WHEN** a home do tema é carregada
- **THEN** nenhuma requisição vai para `fonts.googleapis.com` ou `fonts.gstatic.com`

### Requirement: Rótulo do menu para o mapa de representantes
No menu do Modo Landing Page, uma seção sem título cujo primeiro bloco é do tipo `representatives` SHALL aparecer com o rótulo "Representantes".

#### Scenario: Seção do mapa sem título
- **WHEN** uma seção da home não tem título e o primeiro bloco é do tipo `representatives`
- **THEN** o menu mostra "Representantes" com link para a seção
