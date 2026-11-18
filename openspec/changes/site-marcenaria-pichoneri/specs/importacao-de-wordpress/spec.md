# Spec Delta

## Purpose

Traz os posts e as categorias de um site WordPress com a API pública aberta para o cadastro de notícias de um site da plataforma, preservando endereços por redirecionamento, para migrar blogs sem redigitação.

## ADDED Requirements

### Requirement: Comando de importação
O sistema SHALL oferecer um comando que recebe o domínio de um site da plataforma e o endereço de um WordPress e cria, nesse site, uma notícia para cada post publicado, com título, slug, data de publicação, categoria, texto, resumo e imagem de capa; o comando MUST NOT alterar o cadastro do site, as páginas nem as notícias que não criou.

#### Scenario: Importar o blog
- **WHEN** o comando roda para um site sem notícias, apontando para um WordPress com 44 posts em 5 categorias
- **THEN** o site passa a ter 44 notícias e as 5 categorias, com os mesmos slugs e as mesmas datas

#### Scenario: Domínio desconhecido
- **WHEN** o domínio informado não é de nenhum site
- **THEN** o comando termina com erro e não grava nada

### Requirement: Simulação
Com a opção de simulação, o comando SHALL mostrar o que criaria (notícias, categorias, capas, redirecionamentos e avisos) sem gravar no banco nem baixar arquivos.

#### Scenario: Simular
- **WHEN** o comando roda com a opção de simulação
- **THEN** o relatório lista os 44 posts e nenhuma notícia é criada

### Requirement: Importação repetível
Rodar o comando de novo SHALL criar só o que falta: post cujo slug já existe como notícia no site é pulado, e categoria e redirecionamento que já existem não são duplicados.

#### Scenario: Segunda execução
- **WHEN** o comando roda duas vezes seguidas no mesmo site
- **THEN** a segunda execução informa 0 notícias criadas e 44 puladas

### Requirement: Limpeza do texto importado
O texto de cada post SHALL ser gravado sem marcações de construtor de página, sem incorporações de páginas do próprio WordPress e com os links para outros posts importados apontando para o endereço novo, passando pela mesma sanitização das notícias digitadas no painel. Post que fica sem texto é gravado como rascunho.

#### Scenario: Marcação de construtor
- **WHEN** o texto do post traz `[et_pb_section]…[/et_pb_section]`
- **THEN** a notícia é gravada sem nenhum trecho entre colchetes desse tipo

#### Scenario: Post vazio
- **WHEN** um post tem só título e imagem
- **THEN** ele é importado como rascunho e não aparece no blog

#### Scenario: Resumo com marcação
- **WHEN** o resumo do post vem com marcação de construtor
- **THEN** o resumo gravado é o começo do texto limpo, com até 160 caracteres

### Requirement: Capa do post importado
A imagem destacada do post SHALL virar a capa da notícia, em JPG ou PNG, convertendo WebP para JPG; post sem imagem, com imagem em outro formato ou cujo download falha é importado sem capa, com aviso no relatório.

#### Scenario: Post sem imagem destacada
- **WHEN** o post não tem imagem destacada
- **THEN** a notícia é criada sem capa e o relatório lista o post entre os "sem capa"

### Requirement: Redirecionamentos dos endereços antigos
Para cada post importado ou já existente, o comando SHALL cadastrar o redirecionamento do endereço que o post tinha no WordPress para o endereço dele no blog do site, e para cada categoria, do endereço antigo para a lista da categoria no blog; post em rascunho redireciona para a lista do blog.

#### Scenario: Endereço com data
- **WHEN** o post estava em `/2023/08/31/209467/`
- **THEN** o site passa a responder 301 de `/2023/08/31/209467/` para `/blog/209467`

### Requirement: Isolamento da importação
Tudo o que a importação cria SHALL pertencer ao site do domínio informado e não aparecer em nenhum outro.

#### Scenario: Dois sites
- **WHEN** a importação roda para o site A
- **THEN** o site B continua com as mesmas notícias, categorias e redirecionamentos
