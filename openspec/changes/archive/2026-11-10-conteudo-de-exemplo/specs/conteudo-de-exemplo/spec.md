## Purpose

Permite encher um site com conteúdo de exemplo para mostrar o layout antes de o cliente cadastrar o conteúdo real, e tirar esse conteúdo depois sem tocar no que é real. O site é escolhido pelo domínio.

## ADDED Requirements

### Requirement: Comando de conteúdo de exemplo
O sistema SHALL oferecer um comando de console que recebe o domínio de um site e cria nele conteúdo de exemplo. O domínio SHALL ser comparado exatamente como na resolução do tenant; domínio sem site SHALL encerrar com erro sem gravar nada. O comando SHALL aceitar `--quantidade=N`, com padrão 20 e limite de 1 a 30 (fora disso, erro sem gravar), e `--simular`, que mostra o que seria criado ou removido sem gravar. Os títulos das notícias de exemplo SHALL ser diferentes entre si.

#### Scenario: Portal da AJoPE
- **WHEN** o comando roda com `ajope.wab.com.br` e sem opções, e o site usa o tema `noticias` e não tem conteúdo de exemplo
- **THEN** o site passa a ter 20 notícias de exemplo publicadas e o comando lista quantas notícias, editorias e jornalistas criou

#### Scenario: Domínio que não é site
- **WHEN** o comando roda com `www.ajope.wab.com.br`
- **THEN** o comando termina com erro "Nenhum site com o domínio www.ajope.wab.com.br" e nada é gravado

#### Scenario: Simulação
- **WHEN** o comando roda com `--simular`
- **THEN** o comando mostra as quantidades e os títulos que criaria, e o banco e a pasta de uploads continuam iguais

### Requirement: Conteúdo por tema
Para site com o tema `noticias`, o comando SHALL criar notícias de exemplo; para os outros temas, SHALL encerrar com erro dizendo que o tema não tem conteúdo de exemplo, sem gravar nada.

#### Scenario: Site institucional
- **WHEN** o comando roda com o domínio de um site do tema `cetec`
- **THEN** o comando termina com erro "O tema cetec não tem conteúdo de exemplo" e nada é gravado

### Requirement: O tenant não muda
O comando SHALL criar e remover apenas notícias, editorias e jornalistas de exemplo (com os usuários e as fotos deles). Nome, domínio, tema, cores, logos, configurações, página home, páginas, menus e usuários reais do site SHALL continuar iguais.

#### Scenario: Cores e logo do site
- **WHEN** o comando cria notícias no portal da AJoPE
- **THEN** as cores, o logo e o nome do site continuam os mesmos de antes

### Requirement: Notícias de exemplo publicadas
As notícias de exemplo SHALL ser criadas publicadas e visíveis na hora, cada uma com título, linha fina e texto em português; as que têm capa SHALL ter legenda e crédito. A data de publicação mais recente SHALL ser anterior ao momento em que o comando roda, no horário de Brasília.

#### Scenario: Comando rodado no servidor em UTC
- **WHEN** o comando roda num servidor cujo PHP de linha de comando está em UTC
- **THEN** a notícia mais recente aparece no site como "Há 2 minutos", e nenhuma fica agendada

#### Scenario: Quantidade pequena
- **WHEN** o comando roda com `--quantidade=5`
- **THEN** o site ganha 5 notícias de exemplo, publicadas, cada uma com título, linha fina e texto

### Requirement: Variações do layout
Com 8 notícias ou mais, o conjunto de exemplo SHALL ter: notícias com capa e sem capa; com e sem chapéu; com 0, 1, 2 e 3 links relacionados; publicação desde menos de 1 hora até mais de 7 dias atrás; ao menos 3 marcadas para o "Leia também"; ao menos 5 com visualizações e publicadas nos últimos 7 dias; uma editada depois de publicada; e uma em mais de uma editoria.

#### Scenario: Home do portal depois do comando
- **WHEN** o comando cria 20 notícias num site sem outras notícias e alguém abre a home
- **THEN** a home lista as 20, com a primeira em destaque, e a barra lateral mostra "Leia também" e "Mais lidas"

### Requirement: Editorias de exemplo
O comando SHALL distribuir as notícias em editorias. Editoria com o mesmo slug de uma categoria que já existe no site SHALL usar essa categoria, sem marcá-la como exemplo; as que não existem SHALL ser criadas como exemplo e marcadas para o cabeçalho, incluindo uma subeditoria.

#### Scenario: Site que já tem a editoria Cultura
- **WHEN** o site já tem a categoria `cultura` cadastrada pela equipe e o comando cria notícias de exemplo de cultura
- **THEN** as notícias usam a categoria `cultura` existente, e ela não é marcada como exemplo

### Requirement: Jornalistas de exemplo sem acesso
O comando SHALL atribuir as notícias a jornalistas de exemplo aprovados, com página pública de autor, parte deles com foto. O usuário de cada jornalista de exemplo SHALL ter e-mail no domínio reservado `exemplo.invalid` e uma senha aleatória que não é mostrada nem gravada em lugar nenhum.

#### Scenario: Jornalista de exemplo tenta entrar
- **WHEN** alguém tenta entrar no site com o e-mail de um jornalista de exemplo e qualquer senha
- **THEN** o login é recusado como senha inválida

### Requirement: Marca de exemplo
Toda notícia, editoria e jornalista criados pelo comando SHALL ficar marcado como exemplo, e os registros criados pelo painel, pela área do jornalista, pela importação de um site sem a marca ou que já existiam antes da marca SHALL ser reais (sem a marca). A marca SHALL ser preservada quando a equipe edita o registro no painel.

#### Scenario: Notícia de exemplo editada no painel
- **WHEN** o admin do site muda o título de uma notícia de exemplo no `/admin`
- **THEN** a notícia continua marcada como exemplo

### Requirement: Remoção do conteúdo de exemplo
Com `--remover`, o comando SHALL apagar do site informado só as notícias, as editorias e os jornalistas marcados como exemplo, com os usuários dos jornalistas de exemplo e as fotos desses registros em `public/uploads`, sem criar nada. Com `--substituir`, SHALL remover da mesma forma e então criar um lote novo.

#### Scenario: Remoção com notícia real no site
- **WHEN** o site tem 20 notícias de exemplo e 2 notícias reais, e o comando roda com `--remover`
- **THEN** as 20 notícias de exemplo, as editorias e os jornalistas de exemplo e as fotos deles são apagados, e as 2 notícias reais continuam no ar

#### Scenario: Substituir o lote
- **WHEN** o site tem 20 notícias de exemplo e o comando roda com `--substituir --quantidade=10`
- **THEN** o site fica com 10 notícias de exemplo novas e nenhuma das 20 anteriores

### Requirement: Conteúdo real preservado na remoção
Na remoção, editoria de exemplo que tem notícia real, página ou subcategoria real, e jornalista de exemplo que tem notícia real, SHALL ser mantidos, e o comando SHALL listá-los num aviso.

#### Scenario: Editoria de exemplo usada por notícia real
- **WHEN** a equipe publicou uma notícia real na editoria de exemplo "Cidades" e o comando roda com `--remover`
- **THEN** a editoria "Cidades" é mantida e o comando avisa que ela tem conteúdo real

### Requirement: Segunda geração sem remover
Quando o site já tem notícias de exemplo, o comando sem `--remover` nem `--substituir` SHALL encerrar com erro, sem gravar, dizendo quantas notícias de exemplo existem e quais opções usar.

#### Scenario: Comando rodado duas vezes
- **WHEN** o comando roda de novo no portal da AJoPE sem opções
- **THEN** o comando termina com erro "O site já tem 20 notícias de exemplo; use --substituir ou --remover" e o site continua com as mesmas 20

### Requirement: Isolamento entre sites
O comando SHALL ler, criar e apagar apenas registros do site do domínio informado; slugs de notícia, editoria e jornalista SHALL ser únicos dentro do site, sem conflitar com os que já existem.

#### Scenario: Dois portais com conteúdo de exemplo
- **WHEN** os sites A e B têm notícias de exemplo e o comando roda com `--remover` para o domínio de A
- **THEN** o conteúdo de exemplo de B continua igual

#### Scenario: Slug já usado por notícia real
- **WHEN** o site já tem uma notícia real com o slug que o comando usaria
- **THEN** a notícia de exemplo recebe outro slug, e a notícia real não muda

### Requirement: Conteúdo de exemplo fora dos buscadores
As notícias marcadas como exemplo SHALL ficar fora do `/sitemap.xml`, e a página delas (`/noticias/{slug}`) SHALL sair com `<meta name="robots" content="noindex, follow">`. As demais páginas do site não mudam.

#### Scenario: Sitemap do portal com exemplos
- **WHEN** o site tem 20 notícias de exemplo e 1 notícia real e alguém abre `/sitemap.xml`
- **THEN** o sitemap lista só a notícia real

#### Scenario: Página de uma notícia de exemplo
- **WHEN** alguém abre a página de uma notícia de exemplo
- **THEN** a página responde 200 com `noindex, follow` no robots

### Requirement: Selo de exemplo no painel
As listas de notícias, de jornalistas e de categorias do `/admin` SHALL mostrar o selo "Exemplo" nos registros marcados como exemplo. A área do jornalista e as páginas públicas SHALL não mostrar o selo.

#### Scenario: Lista de notícias com exemplos
- **WHEN** o admin do portal abre a lista de notícias depois do comando
- **THEN** cada notícia criada pelo comando aparece com o selo "Exemplo"

### Requirement: Marca de exemplo na exportação e na importação
A exportação do site SHALL incluir a marca de exemplo de cada notícia, editoria e jornalista, e a importação SHALL restaurá-la; arquivo exportado antes deste change, sem a marca, SHALL ser importado com todos os registros como reais.

#### Scenario: Exportar e importar o portal
- **WHEN** o SuperAdmin exporta um portal com 20 notícias de exemplo e importa o arquivo em outro domínio
- **THEN** o site importado tem as mesmas 20 notícias marcadas como exemplo, e `--remover` no domínio novo apaga as 20
