# Spec Delta

## MODIFIED Requirements

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

## ADDED Requirements

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
O tema `atelie` SHALL seguir o layout de referência aprovado (fundo claro, títulos em Manrope, texto em DM Sans, cantos retos), usando a cor primária do site em botões, destaques e na faixa de chamada, a secundária nos destaques sobre fundo escuro, e as cores de fundo claras do site nas seções sem fundo definido, alternadas; o tema só tem modo claro e não mostra seletor de aparência.

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
