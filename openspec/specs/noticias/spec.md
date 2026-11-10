# noticias Specification

## Purpose
Portal de notícias de um site do WAB Sites: o cadastro de notícias, os jornalistas (candidatura, aprovação, acesso e área própria), as regras de publicação e as páginas públicas do tema `noticias` (home, lista, editoria, notícia e autor), com "Mais lidas", "Leia também", "Próximo", links relacionados e contagem de visualizações.

## Requirements

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
No tema `noticias`, `/jornalista/cadastro` SHALL oferecer ao público o cadastro com nome, e-mail, senha (mínimo de 8 caracteres) e confirmação, WhatsApp, endereço, estado, casa espírita que frequenta, registro profissional, veículo, cidade, minibio (até 600 caracteres), foto e aceite obrigatório do uso dos dados. O envio SHALL exigir token CSRF e campo isca vazio e criar, no site do domínio, o usuário (login = e-mail) e o autor como pendentes.

#### Scenario: Cadastro aceito
- **WHEN** uma jornalista envia o formulário com e-mail novo, WhatsApp "(16) 99999-1234", endereço, estado "SP" e casa espírita
- **THEN** o usuário e o autor são gravados como pendentes, com o WhatsApp, o endereço, o estado e a casa espírita informados

#### Scenario: E-mail já usado
- **WHEN** o candidato informa um e-mail que já é login ou e-mail de qualquer usuário da base
- **THEN** nada é gravado e o formulário volta com "Este e-mail já está cadastrado."

#### Scenario: Robô preenche o campo isca
- **WHEN** o envio chega com o campo isca preenchido
- **THEN** nada é gravado e a página mostra a mesma mensagem de cadastro recebido

#### Scenario: Site com outro tema
- **WHEN** alguém abre `/jornalista/cadastro` num site com tema cetec
- **THEN** a resposta é 404

### Requirement: Campos obrigatórios e WhatsApp do cadastro do jornalista
No cadastro do jornalista, nome, e-mail, senha, WhatsApp, endereço, estado (uma das 27 UFs) e aceite SHALL ser obrigatórios. O WhatsApp SHALL ter 10 ou 11 dígitos de DDD e número, desconsiderados a pontuação, o código do país 55 e o 0 de discagem à frente.

#### Scenario: WhatsApp com o código do país
- **WHEN** o candidato informa o WhatsApp "+5516981791110"
- **THEN** o número é aceito e gravado como foi digitado

#### Scenario: WhatsApp inválido
- **WHEN** o candidato informa o WhatsApp "1234"
- **THEN** nada é gravado e o formulário volta com "Informe o WhatsApp com DDD."

#### Scenario: Estado fora da lista
- **WHEN** o envio chega com o estado "XX"
- **THEN** nada é gravado e o formulário volta com "Escolha o estado."

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
Em `/jornalista/perfil`, o jornalista aprovado SHALL editar nome, WhatsApp, endereço, estado, casa espírita, registro profissional, veículo, cidade, minibio e foto, com as mesmas regras do cadastro para WhatsApp, endereço e estado, e trocar a senha informando a atual; o e-mail, o slug e a situação não mudam por ali.

#### Scenario: Senha atual errada
- **WHEN** o jornalista tenta trocar a senha informando a senha atual errada
- **THEN** a senha não muda e o formulário volta com "Senha atual incorreta."

#### Scenario: Troca do estado
- **WHEN** o jornalista muda o estado de "SP" para "MG" e salva
- **THEN** o perfil passa a mostrar "MG"

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

### Requirement: Dados privados do jornalista
A ficha do jornalista no `/admin/jornalistas` SHALL mostrar WhatsApp, endereço, estado e casa espírita; a página pública do autor (`/autor/{slug}`) MUST NOT mostrar nenhum desses dados.

#### Scenario: Página pública do autor
- **WHEN** alguém abre a página pública de um jornalista com endereço e WhatsApp gravados
- **THEN** a página não mostra o endereço, o estado, o WhatsApp nem a casa espírita

### Requirement: Aviso de cadastro em análise
Ao concluir o cadastro em `/jornalista/cadastro`, o candidato SHALL ficar pendente (sem acesso) e a página SHALL mostrar "Cadastro recebido! Ele está em análise pela administração do portal. Quando for aprovado, você receberá um e-mail com o link para entrar e publicar."

#### Scenario: Cadastro concluído
- **WHEN** uma jornalista envia o cadastro completo
- **THEN** ela fica pendente e a página mostra a mensagem de cadastro em análise

### Requirement: E-mail ao jornalista na mudança de situação
Quando o admin do site ou o SuperAdmin aprova, recusa, suspende ou reativa um jornalista no `/admin/jornalistas`, o sistema SHALL enviar um e-mail ao endereço do jornalista, com o nome do site no remetente e no assunto. Uma falha no envio MUST NOT impedir a mudança de situação, e o painel SHALL avisar que o e-mail não foi enviado.

#### Scenario: Recusa
- **WHEN** o admin recusa um candidato pendente
- **THEN** o candidato recebe o e-mail de que o cadastro não foi aprovado, sem link de login

#### Scenario: Falha no envio
- **WHEN** o servidor de e-mail não responde no momento da aprovação
- **THEN** o jornalista fica aprovado e o painel mostra "Jornalista aprovado, mas o e-mail de aviso não pôde ser enviado."

### Requirement: Link para publicar no e-mail de aprovação
O e-mail de aprovação e o de reativação SHALL trazer o link absoluto de login do site (`https://<domínio>/login`), o e-mail que serve de login e a informação de que, depois de entrar, o jornalista publica pela área do jornalista.

#### Scenario: Aprovação
- **WHEN** o admin da AJoPE aprova a candidata Ana
- **THEN** Ana recebe o e-mail "[Associação dos Jornalistas Espíritas] Seu cadastro de jornalista foi aprovado" com o link `https://ajope.wab.com.br/login` e o e-mail dela como login

### Requirement: Jornalistas no painel inicial
Nos sites do tema `noticias`, o painel inicial do `/admin` SHALL mostrar ao admin do site e ao SuperAdmin o quadro "Jornalistas" com os candidatos pendentes (até 10, os mais recentes primeiro, cada um com link para a ficha), o número de jornalistas aprovados e o link para `/admin/jornalistas`; sem pendentes, o quadro SHALL dizer "Nenhum cadastro aguardando aprovação". Editor e revisor MUST NOT ver o quadro.

#### Scenario: Dois pendentes
- **WHEN** o portal tem 2 candidatos pendentes e 3 aprovados e o admin abre o `/admin`
- **THEN** o quadro lista os 2 pendentes com link para a ficha e mostra "3 aprovados"

#### Scenario: Editor no painel
- **WHEN** um editor do portal abre o `/admin`
- **THEN** o quadro "Jornalistas" não aparece

### Requirement: Campo de vídeo no cadastro da notícia
O formulário de notícia da área do jornalista e do `/admin` SHALL ter o campo opcional "Vídeo do YouTube (link)", com um vídeo por notícia; campo vazio SHALL deixar a notícia sem vídeo.

#### Scenario: Tirar o vídeo
- **WHEN** a jornalista apaga o link do vídeo de uma notícia e salva
- **THEN** a notícia fica sem vídeo

### Requirement: Links de vídeo aceitos
O campo de vídeo SHALL aceitar links do YouTube nos formatos `youtube.com/watch?v=`, `youtu.be/`, `youtube.com/shorts/`, `youtube.com/live/` e `youtube.com/embed/`, com ou sem `www.` e `m.`, e gravar o link como `https://www.youtube.com/watch?v=<id>`. Link de outro site ou sem o id do vídeo SHALL voltar com "Informe um link de vídeo do YouTube (youtube.com ou youtu.be)." sem gravar a notícia.

#### Scenario: Link curto do YouTube
- **WHEN** a jornalista cria uma notícia com o vídeo "https://youtu.be/dQw4w9WgXcQ?si=abc"
- **THEN** a notícia é gravada com o vídeo "https://www.youtube.com/watch?v=dQw4w9WgXcQ"

#### Scenario: Link de outro site
- **WHEN** o vídeo informado é "https://vimeo.com/123456"
- **THEN** a notícia não é gravada e o formulário volta com "Informe um link de vídeo do YouTube (youtube.com ou youtu.be)."

### Requirement: Vídeo no fim da notícia
No tema `noticias`, a página da notícia com vídeo SHALL mostrar o player do YouTube no fim do texto completo, antes da data final, com a largura da coluna do texto e proporção 16:9, carregado de `www.youtube-nocookie.com/embed/<id>` só quando chega perto da tela e com título acessível "Vídeo: <título da notícia>". Notícia sem vídeo SHALL não mostrar área de vídeo.

#### Scenario: Notícia com vídeo
- **WHEN** alguém abre uma notícia publicada com o vídeo "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
- **THEN** depois do último parágrafo do texto aparece o player de `https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ`

#### Scenario: Notícia sem vídeo
- **WHEN** alguém abre uma notícia sem vídeo
- **THEN** a página não tem player nem espaço reservado para vídeo

### Requirement: Vídeo na exportação e na importação
A exportação do site SHALL incluir o link do vídeo de cada notícia, e a importação SHALL restaurá-lo; arquivo exportado antes deste change SHALL ser importado com as notícias sem vídeo.

#### Scenario: Exportar e importar notícia com vídeo
- **WHEN** um site com uma notícia com vídeo é exportado e importado em outro domínio
- **THEN** a notícia importada tem o mesmo link de vídeo

### Requirement: Formatos de imagem aceitos no portal
O envio da capa da notícia (no `/admin` e na área do jornalista) e da foto do jornalista (no cadastro e no perfil) SHALL aceitar só arquivos com extensão `.jpg`, `.jpeg` ou `.png`, em maiúsculas ou minúsculas, e conteúdo JPEG ou PNG. Qualquer outro arquivo SHALL voltar com "A imagem precisa ser JPG ou PNG." e MUST NOT ser gravado.

#### Scenario: Capa em AVIF
- **WHEN** a jornalista envia uma capa em AVIF
- **THEN** a notícia não é gravada e o formulário volta com "A imagem precisa ser JPG ou PNG."

#### Scenario: Capa em WebP
- **WHEN** a jornalista envia uma capa em WebP
- **THEN** a notícia não é gravada e o formulário volta com "A imagem precisa ser JPG ou PNG."

#### Scenario: Extensão em maiúsculas
- **WHEN** a jornalista envia a capa "FOTO.JPG" com conteúdo JPEG
- **THEN** a capa é gravada e a miniatura da lista é gerada

#### Scenario: Extensão trocada
- **WHEN** a jornalista envia um arquivo AVIF renomeado para "foto.jpg"
- **THEN** a notícia não é gravada e o formulário volta com "A imagem precisa ser JPG ou PNG."

### Requirement: Imagem gravada que não vira miniatura
No tema `noticias`, quando a capa de uma notícia ou a foto de um jornalista está num formato que o servidor não consegue abrir, as listas de notícias, "Mais lidas", "Leia também" e a página do autor SHALL mostrar o arquivo original no lugar da miniatura, no mesmo tamanho e recorte, e MUST NOT apontar para o endereço da miniatura.

#### Scenario: Capa AVIF já gravada
- **WHEN** uma notícia tem capa AVIF gravada antes desta mudança e alguém abre a página do autor dela
- **THEN** o item mostra `/uploads/article/<arquivo>.avif`, sem erro 500 e sem imagem quebrada

### Requirement: Campo de busca do portal
No tema `noticias`, o cabeçalho SHALL ter, no canto direito e em todas as larguras de tela, um botão de lupa com nome acessível "Buscar" que mostra e esconde um campo de busca logo abaixo da barra, já com o foco no campo; o menu lateral SHALL ter o mesmo campo. Enviar o campo SHALL abrir `/busca?q=<termo>`.

#### Scenario: Busca pelo cabeçalho no celular
- **WHEN** um visitante a 390 px toca na lupa, digita "festival" e confirma
- **THEN** o navegador abre `/busca?q=festival`

### Requirement: Resultados da busca
`/busca?q=<termo>` SHALL listar as notícias visíveis do site do domínio que têm todas as palavras do termo no título, no chapéu, na linha fina ou no texto, sem diferenciar maiúsculas nem acentos, primeiro as que têm alguma palavra no título e depois da mais recente para a mais antiga, 20 por página com `?pagina=N`, com o título "N resultados para "<termo>"". Páginas institucionais do site cujo título tem todas as palavras SHALL aparecer num quadro "Páginas".

#### Scenario: Palavra sem acento
- **WHEN** o site tem a notícia "Mutirão recupera praças e canteiros" e alguém busca "mutirao pracas"
- **THEN** a notícia aparece nos resultados

#### Scenario: Notícia de outro site
- **WHEN** outro site tem uma notícia com "festival" e alguém busca "festival" no portal da AJoPE
- **THEN** só as notícias da AJoPE aparecem

#### Scenario: Sem resultado
- **WHEN** ninguém usou o termo "xyzw"
- **THEN** a página mostra "Nenhuma notícia encontrada para "xyzw"." e o campo de busca

### Requirement: Regras do termo de busca
O termo SHALL ser cortado em 100 caracteres e em até 5 palavras, ignorando palavras com menos de 2 letras; termo vazio ou só com palavras ignoradas SHALL mostrar só o campo de busca, sem resultados. Rascunhos, notícias agendadas e notícias de outro site MUST NOT aparecer.

#### Scenario: Termo vazio
- **WHEN** alguém abre `/busca` sem `q`
- **THEN** a página mostra só o campo de busca

#### Scenario: Notícia agendada
- **WHEN** uma notícia agendada para amanhã tem a palavra buscada
- **THEN** ela não aparece nos resultados

### Requirement: Busca fora dos buscadores e dos outros temas
A página `/busca` SHALL sair com `noindex` e ficar fora do `/sitemap.xml`; em site de outro tema, `/busca` SHALL responder 404.

#### Scenario: Site cetec
- **WHEN** alguém abre `/busca?q=teste` num site com tema cetec
- **THEN** a resposta é 404
