# Spec Delta

## MODIFIED Requirements

### Requirement: Blocos por tipo
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 25 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact, hero_split, highlights, portfolio, statement, steps, cta, video, blog_posts, accordion, representatives); tipo desconhecido não renderiza nada.

#### Scenario: Bloco de newsletter com a newsletter desligada
- **WHEN** a seção tem um bloco `newsletter` e o tenant tem `newsletterEnabled` falso
- **THEN** o bloco não é renderizado

#### Scenario: Tipo que não existe
- **WHEN** a seção tem um bloco gravado com um tipo que não está em `BlockType`
- **THEN** a página responde 200 e nada é desenhado no lugar desse bloco

## ADDED Requirements

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
