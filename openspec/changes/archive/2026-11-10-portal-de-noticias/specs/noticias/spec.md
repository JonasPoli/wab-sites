# Spec Delta

## Purpose

Portal de notícias de um site do WAB Sites: o cadastro de notícias, os jornalistas (candidatura, aprovação, acesso e área própria), as regras de publicação e as páginas públicas do tema `noticias` (home, lista, editoria, notícia e autor), com "Mais lidas", "Leia também", "Próximo", links relacionados e contagem de visualizações.

## ADDED Requirements

### Requirement: Notícia pertence ao site
Cada notícia e cada autor SHALL pertencer a um site (tenant) e só SHALL ser listado, exibido, editado ou excluído a partir do domínio desse site. Um id ou slug de notícia ou de autor de outro site SHALL responder 404, no painel, na área do jornalista e nas páginas públicas.

#### Scenario: Editar notícia de outro site
- **WHEN** o admin do site A abre `/admin/noticias/{id}/edit` com o id de uma notícia do site B
- **THEN** a resposta é 404 e nada é alterado

#### Scenario: Slug de outro site
- **WHEN** um visitante abre `/noticias/{slug}` no site A com o slug de uma notícia publicada do site B
- **THEN** a resposta é 404

### Requirement: Cadastro de notícias no painel
O `/admin` SHALL permitir que administrador, editor e revisor do site listem, criem, editem e excluam todas as notícias do site. O título é obrigatório, e a exclusão exige token CSRF válido. O item "Notícias" do menu do painel SHALL aparecer só quando o site usa o tema `noticias`.

#### Scenario: Notícia sem título
- **WHEN** um editor salva uma notícia com o título vazio
- **THEN** nada é gravado e o formulário volta com o aviso de título obrigatório

#### Scenario: Menu num site com tema cetec
- **WHEN** o admin de um site com tema cetec abre o `/admin`
- **THEN** o menu do painel não mostra "Notícias"

#### Scenario: Lista do painel
- **WHEN** o admin abre a lista de notícias
- **THEN** vê as notícias do site da mais recente para a mais antiga por data de publicação, com editoria principal, autor, situação, data e número de visualizações

### Requirement: Slug único no site
O sistema SHALL gerar o slug da notícia a partir do título, e o do autor a partir do nome, quando vem vazio, e SHALL mantê-lo único dentro do site entre as notícias (ou entre os autores), acrescentando `-2`, `-3` e assim por diante quando já é usado. Sites diferentes podem repetir o slug.

#### Scenario: Título repetido
- **WHEN** o editor cria a notícia "Assembleia geral" e o site já tem uma notícia com slug `assembleia-geral`
- **THEN** a nova notícia é gravada com o slug `assembleia-geral-2`

### Requirement: Situação e data de publicação
A notícia SHALL ter a situação rascunho ou publicada, e data e hora de publicação no horário de Brasília. As páginas públicas, o sitemap, "Mais lidas", "Leia também" e "Próximo" SHALL considerar só as notícias publicadas com data de publicação igual ou anterior ao momento atual.

#### Scenario: Rascunho
- **WHEN** um visitante abre `/noticias/{slug}` de uma notícia em rascunho
- **THEN** a resposta é 404

#### Scenario: Publicação agendada
- **WHEN** uma notícia publicada tem data de publicação daqui a duas horas
- **THEN** ela não aparece na home nem no sitemap, `/noticias/{slug}` responde 404 e, passado o horário, ela aparece sem nova edição

### Requirement: Texto da notícia sanitizado
Ao gravar, pelo painel ou pela área do jornalista, o sistema SHALL remover do texto da notícia scripts, atributos de evento, `iframe`, `style` e links `javascript:`, mantendo parágrafos, títulos, negrito, itálico, sublinhado, listas, citações, links e imagens.

#### Scenario: Script colado no texto
- **WHEN** o jornalista grava um texto com `<p>Olá</p><script>alert(1)</script>`
- **THEN** o texto gravado é `<p>Olá</p>`

### Requirement: Links relacionados
A notícia SHALL ter até 3 links relacionados, cada um com rótulo e URL. Ao gravar, o sistema SHALL descartar os que não têm rótulo ou URL e acrescentar `https://` à URL que não começa com `http://`, `https://`, `//` ou `/`.

#### Scenario: URL sem protocolo
- **WHEN** o editor cadastra o link "Veja a pauta" com URL `exemplo.com.br/pauta`
- **THEN** o link é gravado com `https://exemplo.com.br/pauta`

### Requirement: Editorias da notícia
A notícia SHALL ter uma editoria principal e outras categorias opcionais, todas do mesmo site. Com outras categorias marcadas, a editoria principal SHALL ser obrigatória, e ela não se repete entre as outras. Ao excluir uma categoria, as notícias SHALL perder só essa categoria, sem serem apagadas.

#### Scenario: Outras categorias sem a principal
- **WHEN** o editor marca "Cultura" e "Cidades" em outras categorias e deixa a editoria principal vazia
- **THEN** nada é gravado e o formulário volta com o aviso "Escolha a editoria principal."

#### Scenario: Notícia em três categorias
- **WHEN** a notícia tem "Política" como principal e "Cidades" e "Cultura" como outras
- **THEN** ela aparece nas páginas das três categorias, e a lista e o cabeçalho da notícia mostram "Política"

#### Scenario: Categoria principal excluída
- **WHEN** o admin exclui a categoria "Política", principal de 4 notícias que também estão em "Cidades"
- **THEN** as 4 notícias continuam publicadas, sem editoria principal e ainda em "Cidades"

### Requirement: Autor da notícia
A notícia SHALL ter no máximo um autor, que é um jornalista do mesmo site. No painel, a equipe SHALL escolher o autor entre os jornalistas aprovados ou deixá-lo vazio; na área do jornalista, o autor SHALL ser sempre o próprio jornalista. A notícia SHALL mostrar `Por <nome>, <veículo>` (sem o veículo quando vazio), com link para a página do autor só quando ele está aprovado.

#### Scenario: Notícia da equipe sem autor
- **WHEN** o admin publica uma notícia sem escolher autor
- **THEN** a página da notícia não mostra a linha "Por"

#### Scenario: Autor suspenso
- **WHEN** o autor de uma notícia publicada é suspenso
- **THEN** a notícia continua no ar e mostra `Por <nome>` sem link

### Requirement: Home do tema noticias
Com o tema `noticias` e sem `homePage` definida, `/` SHALL listar as 20 notícias visíveis mais recentes por data de publicação, a primeira em destaque com a linha fina. Com mais de 20, SHALL mostrar o link "Mais notícias" para `/noticias?pagina=2`. Com `homePage` definida, vale a regra da home do construtor de páginas.

#### Scenario: Site com 25 notícias
- **WHEN** o site tem 25 notícias visíveis e nenhuma página home
- **THEN** a home mostra as 20 mais recentes, a primeira com a linha fina, e o link "Mais notícias"

#### Scenario: Site sem notícias
- **WHEN** o site não tem nenhuma notícia visível
- **THEN** a home mostra o aviso "Nenhuma notícia publicada ainda." no lugar da lista

### Requirement: Item da lista de notícias
Na home, em `/noticias`, na página da editoria e na página do autor, cada notícia SHALL aparecer com a capa à esquerda (ou sem imagem, quando não tem capa), o chapéu, o título com link para `/noticias/{slug}`, os links relacionados e a linha `<tempo relativo> — Em <editoria principal>` (só o tempo, quando não há editoria principal).

#### Scenario: Notícia com chapéu e dois links
- **WHEN** a notícia tem chapéu "Eleições na AJoPE", editoria principal "Institucional", dois links relacionados e foi publicada há 3 horas
- **THEN** o item mostra o chapéu, o título, os dois links com marcador e "Há 3 horas — Em Institucional"

### Requirement: Tempo relativo
O sistema SHALL mostrar a data de publicação como "Agora" com menos de 1 minuto, "Há N minutos" com menos de 1 hora, "Há N horas" com menos de 24 horas e, depois disso, a data `dd/mm/aaaa`, sempre no horário de Brasília e no singular quando N é 1.

#### Scenario: Publicada há 1 hora e 10 minutos
- **WHEN** a notícia foi publicada há 70 minutos
- **THEN** o tempo aparece como "Há 1 hora"

### Requirement: Lista de todas as notícias
No tema `noticias`, `/noticias` SHALL listar as notícias visíveis do site, 20 por página, da mais recente para a mais antiga, com a página escolhida por `?pagina=N` e links para a página anterior e a seguinte; página fora do intervalo SHALL responder 404.

#### Scenario: Página além da última
- **WHEN** o site tem 30 notícias visíveis e alguém abre `/noticias?pagina=3`
- **THEN** a resposta é 404

### Requirement: Página da editoria
No tema `noticias`, `/categoria/{slug}` SHALL mostrar o nome e a descrição da categoria, as notícias visíveis que têm a categoria ou uma subcategoria dela como editoria principal ou entre as outras, sem repetir, 20 por página como em `/noticias`, e as páginas da categoria; slug que não existe no site SHALL responder 404.

#### Scenario: Editoria com subeditoria
- **WHEN** a categoria "Cidades" é principal de 3 notícias, a subcategoria "Capital" é principal de 2 e outra notícia tem "Capital" e "Cidades" entre as outras
- **THEN** `/categoria/cidades` lista as 6, uma vez cada, da mais recente para a mais antiga

### Requirement: Página da notícia
No tema `noticias`, `/noticias/{slug}` SHALL mostrar a editoria principal no cabeçalho, o título, a linha fina, a linha do autor, a data e a hora de publicação, a capa com `<legenda> — Foto: <crédito>`, o texto, a data no fim, o link "Próximo" e o quadro "Mais lidas", com título e descrição da página a partir do título e da linha fina.

#### Scenario: Notícia editada depois de publicada
- **WHEN** a notícia foi publicada às 19h02 e editada 2 horas depois
- **THEN** abaixo do autor aparece "09/11/2026 19h02 · Atualizado há 2 horas"

#### Scenario: Notícia sem capa
- **WHEN** a notícia não tem imagem de capa
- **THEN** a página não mostra área de imagem nem legenda

### Requirement: Próximo
O link "Próximo" SHALL apontar para a notícia visível imediatamente mais antiga do mesmo site, na ordem de publicação; na notícia mais antiga, SHALL não aparecer.

#### Scenario: Notícia mais antiga do site
- **WHEN** um visitante abre a notícia visível mais antiga do site
- **THEN** a página não mostra o link "Próximo"

### Requirement: Contagem de visualizações
Cada abertura de `/noticias/{slug}` que responde 200 SHALL somar 1 às visualizações da notícia, exceto quando o User-Agent está vazio ou indica robô ou pré-visualização de link (contém `bot`, `crawl`, `spider`, `slurp`, `facebookexternalhit`, `whatsapp` ou `preview`, sem diferenciar maiúsculas).

#### Scenario: Pré-visualização no WhatsApp
- **WHEN** o link de uma notícia é colado no WhatsApp e o WhatsApp busca a página para montar a prévia
- **THEN** as visualizações da notícia não mudam

### Requirement: Mais lidas
O quadro "Mais lidas" SHALL listar, numeradas de 1 a 5, as notícias visíveis publicadas nos últimos 7 dias com pelo menos uma visualização, da mais vista para a menos vista (a mais recente primeiro no empate); sem nenhuma, o quadro não aparece. Na home, fica na barra lateral sem imagens; na notícia, aparece no fim, com miniaturas.

#### Scenario: Notícia antiga muito vista
- **WHEN** a notícia mais vista do site foi publicada há 10 dias
- **THEN** ela não aparece em "Mais lidas"

### Requirement: Leia também
A equipe do painel SHALL poder marcar a notícia para o "Leia também"; o jornalista não vê essa opção. Na home do tema `noticias`, a barra lateral SHALL mostrar, com miniatura, as 3 notícias visíveis marcadas mais recentes; sem nenhuma marcada, o quadro não aparece.

#### Scenario: Quatro notícias marcadas
- **WHEN** o site tem 4 notícias visíveis marcadas para o "Leia também"
- **THEN** o quadro mostra as 3 mais recentes

### Requirement: Menu do tema noticias
No tema `noticias`, o botão "MENU" do cabeçalho SHALL abrir uma lista com "Início", as categorias raiz com `showInHeader` que têm notícias visíveis (como principal ou entre as outras) ou páginas, nelas ou nas subcategorias, com as subcategorias abaixo, as páginas com `showInHeader`, os itens de menu personalizados ativos e o link "Seja um autor" para o cadastro de jornalista.

#### Scenario: Editoria sem notícias nem páginas
- **WHEN** a categoria raiz "Esportes" está marcada para o cabeçalho mas não tem notícias visíveis nem páginas
- **THEN** ela não aparece no menu

### Requirement: Páginas institucionais no tema noticias
No tema `noticias`, `/p/{slug}`, a home definida em `homePage`, `/membro/{slug}` e as seções de categoria SHALL renderizar com o cabeçalho e o rodapé do tema `noticias` e com os 15 tipos de bloco na aparência do tema cetec, sem erro.

#### Scenario: Página "Sobre a AJoPE"
- **WHEN** o site com tema `noticias` tem a página `sobre` com seções de texto, equipe e contato
- **THEN** `/p/sobre` responde 200 com os três blocos entre o cabeçalho e o rodapé do portal

### Requirement: Cadastro do jornalista candidato
No tema `noticias`, `/jornalista/cadastro` SHALL oferecer ao público o cadastro com nome, e-mail, senha (mínimo de 8 caracteres) e confirmação, telefone, registro profissional, veículo, cidade, minibio (até 600 caracteres), foto e aceite obrigatório do uso dos dados. O envio SHALL exigir token CSRF e campo isca vazio e criar, no site do domínio, o usuário (login = e-mail) e o autor como pendentes.

#### Scenario: Cadastro aceito
- **WHEN** uma jornalista envia o formulário completo com e-mail novo
- **THEN** o usuário e o autor são gravados como pendentes e a página mostra "Cadastro recebido. Você poderá entrar depois que a administração do portal aprovar."

#### Scenario: E-mail já usado
- **WHEN** o candidato informa um e-mail que já é login ou e-mail de qualquer usuário da base
- **THEN** nada é gravado e o formulário volta com "Este e-mail já está cadastrado."

#### Scenario: Robô preenche o campo isca
- **WHEN** o envio chega com o campo isca preenchido
- **THEN** nada é gravado e a página mostra a mesma mensagem de cadastro recebido

#### Scenario: Site com outro tema
- **WHEN** alguém abre `/jornalista/cadastro` num site com tema cetec
- **THEN** a resposta é 404

### Requirement: Aprovação de jornalistas
O `/admin/jornalistas` SHALL listar os autores do site, os pendentes primeiro, e permitir ao admin do site (`workGroup` 0) e ao SuperAdmin aprovar ou recusar um pendente, suspender um aprovado, reativar um suspenso e excluir um pendente ou recusado (com o usuário dele). Editor e revisor SHALL receber 403. Cada ação exige token CSRF e grava quem decidiu e quando.

#### Scenario: Revisor tenta aprovar
- **WHEN** um revisor (`workGroup` 2) envia a aprovação de um candidato
- **THEN** a resposta é 403 e o candidato continua pendente

#### Scenario: Menu com pendentes
- **WHEN** o site com tema `noticias` tem 3 candidatos pendentes e o admin abre o `/admin`
- **THEN** o menu mostra "Jornalistas" com o número 3; para editor e revisor, o item não aparece

#### Scenario: Excluir candidato recusado
- **WHEN** o admin exclui um candidato recusado
- **THEN** o autor e o usuário dele são apagados, com a foto, e o e-mail pode ser usado num novo cadastro

### Requirement: Acesso do jornalista
O login de jornalista SHALL ser aceito só com o autor aprovado; pendente, recusado e suspenso SHALL ser recusados com "Seu cadastro aguarda aprovação.", "Seu cadastro não foi aprovado." e "Seu acesso está suspenso.". Depois do login, o jornalista SHALL ir para `/jornalista`. A área SHALL responder 403 quando o autor deixou de estar aprovado ou o domínio não é o do site dele.

#### Scenario: Candidato pendente tenta entrar
- **WHEN** um candidato pendente faz login com a senha certa
- **THEN** o login é recusado com "Seu cadastro aguarda aprovação."

#### Scenario: Suspenso com sessão aberta
- **WHEN** o admin suspende um jornalista que está com a área aberta
- **THEN** a próxima requisição dele a `/jornalista` responde 403

### Requirement: Área do jornalista
Em `/jornalista`, o jornalista aprovado SHALL listar, criar, editar e excluir só as notícias de que é autor, com os campos e regras do painel, exceto "Leia também" e a escolha do autor. Ele SHALL poder gravá-las como rascunho ou publicadas, sem revisão. Notícia de outro autor SHALL responder 404.

#### Scenario: Publicar direto
- **WHEN** um jornalista aprovado grava uma notícia como publicada com data de agora
- **THEN** ela aparece na home do portal com `Por <nome dele>`

#### Scenario: Notícia de outro autor
- **WHEN** um jornalista abre `/jornalista/noticias/{id}/edit` com o id de uma notícia de outro autor do mesmo site
- **THEN** a resposta é 404

#### Scenario: Notícia da equipe com ele como autor
- **WHEN** o admin cria uma notícia e escolhe o jornalista como autor
- **THEN** a notícia aparece na lista da área dele, que pode editá-la

### Requirement: Perfil do jornalista
Em `/jornalista/perfil`, o jornalista aprovado SHALL editar nome, telefone, registro profissional, veículo, cidade, minibio e foto, e trocar a senha informando a atual; o e-mail, o slug e a situação não mudam por ali.

#### Scenario: Senha atual errada
- **WHEN** o jornalista tenta trocar a senha informando a senha atual errada
- **THEN** a senha não muda e o formulário volta com "Senha atual incorreta."

### Requirement: Página do autor
No tema `noticias`, `/autor/{slug}` SHALL mostrar a foto, o nome, o veículo, a cidade e a minibio do autor aprovado e as notícias visíveis dele, 20 por página como em `/noticias`. Autor pendente, recusado, suspenso ou de outro site SHALL responder 404.

#### Scenario: Autor aprovado
- **WHEN** um visitante clica em "Por Ana Souza" numa notícia
- **THEN** `/autor/ana-souza` mostra o perfil dela e as notícias visíveis de que ela é autora

#### Scenario: Autor suspenso
- **WHEN** alguém abre `/autor/{slug}` de um autor suspenso
- **THEN** a resposta é 404

### Requirement: Notícias e autores na exportação, importação e exclusão do site
A exportação de um site SHALL incluir as notícias com as capas e os autores com as fotos e a situação; a importação SHALL recriá-los no tenant novo, ligando cada autor ao usuário importado, as notícias ao autor e às categorias correspondentes, com as visualizações zeradas; a exclusão SHALL apagar as capas e as fotos dos autores do site que nenhum outro site usa.

#### Scenario: Exportar e importar um portal
- **WHEN** o SuperAdmin exporta um site com 12 notícias e 4 jornalistas e importa o ZIP com outro domínio
- **THEN** o site novo tem as 12 notícias e os 4 jornalistas, com capas, fotos, editorias, autores, situações e datas iguais, e visualizações zeradas

#### Scenario: Excluir o site importado
- **WHEN** o SuperAdmin exclui o site importado, que divide com o original os arquivos de capa trazidos no ZIP e tem uma capa enviada só nele
- **THEN** a capa exclusiva sai de `public/uploads/article` e as capas que o site original também usa continuam
