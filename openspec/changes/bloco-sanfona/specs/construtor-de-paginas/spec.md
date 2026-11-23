# Spec Delta

## MODIFIED Requirements

### Requirement: Blocos por tipo
O sistema SHALL renderizar os blocos da seção na ordem de `position`, incluindo `blocks/_<tipo>.html.twig` do tema para cada um dos 24 tipos de `BlockType` (text_image, text_only, gallery, newsletter, stats, news_call, map, sub_categories, page_list, blurbs4, testimonials, partner_logos, banner, team, contact, hero_split, highlights, portfolio, statement, steps, cta, video, blog_posts, accordion); tipo desconhecido não renderiza nada.

#### Scenario: Bloco de newsletter com a newsletter desligada
- **WHEN** a seção tem um bloco `newsletter` e o tenant tem `newsletterEnabled` falso
- **THEN** o bloco não é renderizado

#### Scenario: Tipo que não existe
- **WHEN** a seção tem um bloco gravado com um tipo que não está em `BlockType`
- **THEN** a página responde 200 e nada é desenhado no lugar desse bloco

## ADDED Requirements

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
