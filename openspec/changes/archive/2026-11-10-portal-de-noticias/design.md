# Design: portal-de-noticias

## Context

Motivação e escopo estão em `proposal.md`; o comportamento está em `specs/noticias`, `specs/navegacao-e-aparencia`, `specs/construtor-de-paginas` e `specs/isolamento-por-dominio`. O que segue é o estado atual que molda a solução, conferido no código em 09/11/2026:

- **Notícias:** não existe entidade de notícia. Uma tabela `article` foi criada e depois apagada nas migrations de maio (`Version20260519210432`). Ficaram só as rotas-esboço `pub_articles` e `pub_article_show` no `WabPublicController`, os templates `article(s).html.twig` dos três temas (que esperam campos que nunca existiram) e o mapeamento de upload `article_image` (`public/uploads/article`) em `config/packages/vich_uploader.yaml`.
- **Páginas e categorias:** `Page` e `Category` implementam `TenantAwareInterface` e passam pelo `TenantFilter`. `Category` tem um nível de subcategoria e já tem cadastro no painel e rota pública (`/categoria/{slug}`).
- **Usuários e papéis:** `User` tem `username` e `email` únicos em toda a base e `tenant` opcional. `getRoles()` deriva os papéis do `workGroup`: 0 vira ROLE_ADMIN (e ROLE_SUPER_ADMIN sem tenant); 1 e 2 viram ROLE_EDITOR e ROLE_ADMIN; os demais valores ficam só com ROLE_USER. O `security.yaml` tem um único firewall `main` com `form_login` (`default_target_path: admin_dash`) e `access_control` para `^/superadmin` e `^/admin`. Não existe cadastro público de usuário.
- **CSS:** é Tailwind compilado a partir de `templates/**/*.html.twig` (`./build.sh` roda `tailwind:build --minify`). As classes `cetec-primary` e `cetec-secondary` leem `--color-primary-rgb` e `--color-secondary-rgb`, que `tenant_css_vars()` gera com as cores do tenant. Por isso os blocos do cetec assumem as cores de qualquer site.
- **Editor rico:** o painel já tem um reutilizável, `templates/admin/_rich_editor.html.twig` (Quill 2.0.3, sem botão de imagem). O Quill 2 grava as listas com marcador como `<ol><li data-list="bullet">` e o alinhamento como a classe `ql-align-*`.
- **Imagens:** o LiipImagine tem o filtro `news_thumb` (400×220, `outbound`), usado hoje só pelo tema wab.
- **Fuso horário:** o projeto não define fuso. O PHP local roda em UTC, e o fuso padrão do PHP de produção não foi lido (a leitura por SSH foi barrada nesta sessão; ver tarefas).
- **Changes abertos nos mesmos arquivos:**
  - `newsletter-csv-e-rotas-de-tema`: rotas `/noticias`, `/videos` e `/estudos` no `WabPublicController`.
  - `isolamento-entre-sites`: `AdminContentController`, `SuperAdminController`, exportação e exclusão, papéis, e login restrito ao domínio com um `TenantUserChecker`.
- **Logo da AJoPE** (`var/jornalista/logo.png`): cores azul-marinho `#072156`, dourado `#FDB60F` e verde `#135D15`, com as uvas em roxo. O logo é colorido sobre fundo branco; não há versão em branco para fundo escuro.

## Goals / Non-Goals

**Goals:**

- Um site com o tema `noticias` funciona como portal: notícias com cadastro próprio em várias categorias, jornalistas que se candidatam, são aprovados pelo admin e publicam direto, home em lista, páginas de notícia, editoria e autor, "Mais lidas", "Leia também" e "Próximo".
- Nenhuma mudança visível nos 8 sites de clientes (todos no tema cetec) nem nos temas cetec, moderno e wab.
- Conferência completa em localhost, sem escrita em produção além do deploy e da migration.

**Non-Goals:**

- Busca, compartilhamento, vídeo embutido, carrossel, selo "AO VIVO", comentários e newsletter de notícias.
- Revisão das notícias antes de publicar: o jornalista aprovado e a equipe do painel publicam direto.
- E-mails de aviso do cadastro (candidato novo, aprovação, recusa), confirmação de e-mail e "esqueci a senha" do jornalista.
- Autor sem login; cadastro de jornalista feito pelo admin (todo autor nasce do cadastro público).
- Modo escuro no tema `noticias`.
- Sanitizar o HTML dos blocos `text_only` e `text_image` que já existem.
- Reproduzir a marca da referência. As telas servem só como padrão de layout. Nenhum nome, logo, fonte, cor ou barra de serviços do portal de referência entra no tema; a identidade vem do tenant (logo, cores e fontes).

## Decisions

### 1. Entidade `Article` separada de `Page`

Decisão do Jonas: o portal precisa de um cadastro próprio de notícias. O nome `Article` combina com as rotas (`pub_articles`, `pub_article_show`) e com o mapeamento `article_image`, que já existem. Campos:

| Campo | Tipo | Observação |
|---|---|---|
| `tenant` | ManyToOne `Tenant`, `ON DELETE CASCADE` | `TenantAwareInterface` |
| `title` | string 255 | obrigatório |
| `slug` | string 255 | índice único `(tenant_id, slug)` |
| `kicker` | string 120, nulo | chapéu |
| `summary` | text, nulo | linha fina; também é a meta description |
| `body` | longtext, nulo | HTML sanitizado |
| `category` | ManyToOne `Category`, nulo, `ON DELETE SET NULL` | editoria principal |
| `categories` | ManyToMany `Category` (tabela `article_category`, FKs `ON DELETE CASCADE`) | outras categorias, sem a principal |
| `author` | ManyToOne `Author`, nulo, `ON DELETE SET NULL` | jornalista autor |
| `coverImage` + `coverImageFile` | Vich `article_image` | capa |
| `coverCaption` / `coverCredit` | string 255, nulo | legenda e crédito |
| `status` | string 20 (`rascunho`/`publicada`) | enum PHP `ArticleStatus` |
| `publishedAt` | datetime_immutable | |
| `relatedLinks` | json | até 3 `{label, url}` |
| `featured` | bool, padrão falso | "Leia também"; só a equipe altera |
| `viewCount` | int, padrão 0 | |
| `createdAt` / `updatedAt` | datetime_immutable | `updatedAt` também dispara o upload do Vich |

Índice `(tenant_id, status, published_at)` para a lista e para "Mais lidas".

Alternativa descartada: reaproveitar `Page` com campos novos. Seria menos código, mas as notícias se misturariam às páginas institucionais na lista do painel, no sitemap e no `news_call`, e o Jonas pediu cadastro separado.

### 2. Editorias: principal em `category` e outras em `categories`

A editoria reaproveita `Category`: uma só árvore por site, com cadastro e rota pública que já existem e a mesma regra de um nível de subcategoria. A principal fica num campo próprio (`category`), porque é ela que aparece em `Em <editoria>` e no cabeçalho da notícia. As outras ficam na ManyToMany `categories`, e o controller tira delas a principal ao gravar. A consulta de uma editoria usa `category IN (:ids) OR :ids MEMBER OF categories` (com os ids da categoria e das subcategorias) e `DISTINCT`. Ao excluir uma categoria, o `SET NULL` e o `CASCADE` da tabela de ligação cuidam do resto.

Alternativas descartadas: só a ManyToMany, com a principal sendo a primeira (a ordem não é garantida numa ManyToMany do Doctrine); uma entidade "Editoria" própria (duplicaria o cadastro de categorias).

### 3. Jornalista: `Author` ligado a um `User` com `workGroup` 3

`App\Entity\Author` (`TenantAwareInterface`, mapeamento Vich novo `author_photo` em `public/uploads/author`):

| Campo | Tipo | Observação |
|---|---|---|
| `tenant` | ManyToOne `Tenant`, `ON DELETE CASCADE` | |
| `user` | OneToOne `User`, único, `ON DELETE CASCADE` | login do jornalista |
| `name`, `slug` | string 150 / 255 | índice único `(tenant_id, slug)` |
| `phone`, `professionalRegistration`, `outlet`, `city` | string, nulos | telefone, registro (MTb), veículo, cidade |
| `bio` | text, nulo | até 600 caracteres |
| `photo` + `photoFile` | Vich `author_photo` | |
| `status` | string 20 | enum `AuthorStatus`: `pendente`, `aprovado`, `recusado`, `suspenso` |
| `consentAt` | datetime_immutable | aceite do uso dos dados |
| `reviewedAt` / `reviewedBy` | datetime_immutable nulo / ManyToOne `User` nulo, `SET NULL` | quem decidiu e quando |
| `createdAt` / `updatedAt` | datetime_immutable | |

O `User` do jornalista tem `tenant` = site do domínio, `workGroup` 3, `username` e `email` = e-mail informado (em minúsculas) e o nome. `User::getRoles()` ganha o ramo `workGroup === 3` → `ROLE_JOURNALIST` (sem `ROLE_ADMIN`). O perfil público fica no `Author`, não no `User`. Assim o `User` continua sendo só o login de todos os sites, e o autor pode ter regras próprias de situação e de slug.

Alternativa descartada: pôr os campos do jornalista no `User`. Misturaria um perfil público de portal com a conta de painel de todos os sites, e o `User` não passa pelo filtro de tenant.

### 4. Área do jornalista separada do `/admin`

Rotas `/jornalista` (lista "Minhas notícias"), `/jornalista/noticias/new|{id}/edit|{id}/delete` e `/jornalista/perfil`, num `App\Controller\journalist\JournalistAreaController` com layout próprio (`templates/journalist/base.html.twig`, com o visual do painel e só dois itens de menu). `access_control` ganha `^/jornalista/cadastro` → `PUBLIC_ACCESS` e `^/jornalista` → `ROLE_JOURNALIST`, antes de `^/admin`. Como o jornalista não tem `ROLE_ADMIN`, nenhuma rota do `/admin` precisa aprender a barrá-lo.

Cada ação da área confere três coisas: o autor do usuário está `aprovado`; `user.tenant` é o tenant do domínio (403 nos dois casos); e a notícia tem `author` = o autor do usuário (404 se não tiver). O formulário de notícia é o mesmo parcial do painel, sem os campos "Leia também" e "Autor".

Alternativa descartada: abrir `/admin/noticias` para o jornalista. Obrigaria a separar, rota a rota, o que o jornalista pode e não pode fazer no painel, além de mexer no `AdminContentController`, que o `isolamento-entre-sites` está alterando.

### 5. Login e situação do jornalista

- Um `App\Security\JournalistStatusChecker` (`UserCheckerInterface`) recusa no `checkPreAuth` o jornalista cujo autor não está aprovado, com as mensagens da spec (`CustomUserMessageAccountStatusException`). Ele é registrado com a tag `security.user_checker.main`, e o firewall usa `user_checker: security.user_checker.chain.main`, para conviver com o `TenantUserChecker` do `isolamento-entre-sites`, seja qual for o change que entrar primeiro.
- Um listener de `LoginSuccessEvent` troca o destino para `/jornalista` quando o usuário tem `ROLE_JOURNALIST` (o `default_target_path` continua `admin_dash` para os demais).
- A suspensão vale na próxima requisição pela checagem de situação da área (decisão 4); não é preciso derrubar a sessão.
- Antes do `isolamento-entre-sites`, o login é global. Por isso a área confere o domínio por conta própria: o jornalista da AJoPE que fizer login em outro domínio recebe 403 em `/jornalista` e não tem acesso ao `/admin`.

### 6. Cadastro público e aprovação

- `/jornalista/cadastro` (GET e POST, só no tema `noticias`, senão 404) num `App\Controller\pub\JournalistSignupController`. O POST valida o CSRF e um campo isca escondido (`website`): se vier preenchido, responde com a mensagem de sucesso sem gravar nada. Depois confere a senha (mínimo de 8 caracteres e a confirmação) e se o e-mail já existe como `username` ou `email` de qualquer `User` ("Este e-mail já está cadastrado."). Grava o `User` (senha com o hasher do Symfony) e o `Author` `pendente` numa transação. Não há e-mail de aviso.
- `/admin/jornalistas` num `App\Controller\admin\AdminAuthorController`. A lista é filtrável por situação, com os pendentes primeiro, e a ficha mostra todos os dados. As ações (POST com CSRF) seguem as transições: `pendente → aprovado|recusado`, `aprovado → suspenso`, `suspenso → aprovado`, e excluir só `pendente|recusado`, junto com o `User` e a foto. Cada ação grava `reviewedAt` e `reviewedBy`. A permissão é `workGroup === 0` (com tenant) ou SuperAdmin, conferida no próprio controller, e editor e revisor recebem 403. O item de menu "Jornalistas", com o número de pendentes, só aparece com o tema `noticias` e `workGroup` 0.
- Notícias de um jornalista suspenso continuam no ar. A equipe pode despublicar uma a uma.

### 7. Tema `noticias` em pasta própria, reaproveitando os blocos do cetec

Templates novos, existentes só em `templates/themes/noticias/` (cetec, moderno e wab não ganham nem perdem template):

- `base.html.twig`, `_header.html.twig` e `_footer.html.twig`: só modo claro, fonte padrão Open Sans (Google Fonts) quando o tenant não define fontes e cores pelas variáveis do tenant. Cabeçalho e rodapé usam o fundo `--color-primary`. Títulos e links das notícias usam a cor primária; o marcador dos links relacionados usa a secundária; os números de "Mais lidas" usam a primária, mais clara, porque o dourado da AJoPE sobre branco não tem contraste para texto; o chapéu usa cinza-escuro.
- `home.html.twig`, `articles.html.twig`, `category.html.twig`, `article.html.twig` e `author.html.twig`, mais os parciais `_article_item.html.twig`, `_most_read.html.twig`, `_featured.html.twig`, `_pagination.html.twig` e `_byline.html.twig` (linha "Por").
- `journalist_signup.html.twig`: o formulário público de cadastro, dentro do cabeçalho e do rodapé do portal.
- `page.html.twig` (seções e fundos copiados do cetec), `_block.html.twig` (inclui `themes/cetec/blocks/_<tipo>.html.twig`) e `team_member_classic.html.twig`/`team_member_modern.html.twig` (cópias do cetec que estendem o base do tema `noticias`).
- Sem `videos.html.twig` e `studies.html.twig`: `/videos` e `/estudos` respondem 404, pela regra de `newsletter-csv-e-rotas-de-tema`.

Layout (das telas de `var/jornalista/`): container de cerca de 1100 px; na home e nas listas, uma coluna de cerca de 2/3 com os itens separados por linha fina e uma barra lateral de 1/3 com cartões brancos arredondados ("Leia também" no topo e "Mais lidas" abaixo). Na notícia, uma coluna central de cerca de 680 px. A capa da lista usa `news_thumb`, e as miniaturas e a foto do autor usam um filtro novo, `news_square` (160×160, `outbound`). Abaixo de 768 px, a barra lateral vai para baixo da lista e a capa fica sobre o texto.

Logo (spec `navegacao-e-aparencia`): `is_dark_color(primaryColor)` escolhe entre `darkLogo` e o logo normal. Sem `darkLogo` num fundo escuro, o logo normal vai sobre um fundo branco arredondado. É o caso da AJoPE hoje.

Alternativa descartada: criar os 15 blocos do zero no tema novo. Daria muito trabalho para páginas que no portal são secundárias (Sobre, Diretoria, Contato). Como as classes `cetec-*` seguem as cores do tenant, os blocos do cetec ficam coerentes com o portal.

### 8. Rotas públicas no `WabPublicController`, decididas pelo tema

`articles()`, `articleShow()`, `home()` e `category()` verificam `tenant.theme === 'noticias'`. No tema `noticias`, usam o `ArticleRepository`; nos outros, o comportamento atual continua intacto. A rota nova `/autor/{slug}` (`pub_author`) responde 404 fora do tema `noticias`. O parâmetro de página é `?pagina=N` (20 por página). As URLs de notícia ficam em `/noticias/{slug}`, porque as rotas já existem e a referência usa esse formato. Este change é escrito sobre o `WabPublicController` já alterado por `newsletter-csv-e-rotas-de-tema` e reaproveita o helper de "template do tema existe" que aquele change cria.

A regra de visibilidade (`status = publicada` e `publishedAt <= agora`) fica num único método do repositório (`visibleQueryBuilder()`), usado pela home, pelas listas, pelo detalhe, pela página do autor, pelo sitemap, por "Mais lidas", por "Leia também" e por "Próximo".

### 9. Isolamento por tenant

- `Article` e `Author` implementam `TenantAwareInterface`, e o `TenantFilter` acrescenta `tenant_id` em todas as consultas do site, do painel e da área do jornalista. O id na URL e o slug público só encontram registros do domínio aberto; o resto responde 404.
- Ao gravar, `tenant` vem do `TenantContext`, nunca do formulário. A editoria principal e as outras categorias são carregadas pelo `CategoryRepository` filtrado, e o autor escolhido pela equipe pelo `AuthorRepository` filtrado, só entre os aprovados. Um id de outro site é ignorado.
- O `User` não passa pelo filtro. Por isso o cadastro grava `user.tenant` com o tenant do domínio, a área compara `user.tenant` com o domínio, e o autor do usuário é buscado pelo `AuthorRepository` filtrado (`findOneBy(['user' => $user])`).
- A contagem de visualizações é um `UPDATE article SET view_count = view_count + 1 WHERE id = :id` por DBAL (que não passa pelo filtro), feito só depois de a notícia ter sido carregada pela consulta filtrada. O id vem dessa entidade, nunca da requisição.
- Exportação, importação e exclusão consultam `findBy(['tenant' => $tenant])` explicitamente. Assim funcionam com e sem o filtro do domínio ligado no `/superadmin` (o `isolamento-entre-sites` vai desligá-lo).
- `Article` e `Author` não têm filhos sem `tenant_id`. A tabela `article_category` só liga registros carregados pelo filtro.

### 10. Datas no horário de Brasília sem mudar o fuso global

O formulário usa `datetime-local`, interpretado em `America/Sao_Paulo` e convertido para o fuso padrão do PHP antes de gravar. A exibição converte de volta com `|date(formato, 'America/Sao_Paulo')`, e o filtro Twig `relative_time` ("Agora", "Há N minutos", "Há N horas", "dd/mm/aaaa") compara instantes. Mudar `date.timezone` do projeto deslocaria em 3 horas as datas que os outros sites já gravaram (contatos e inscritos). Por isso a conversão fica restrita às notícias. O método só falha se o fuso padrão do PHP mudar depois que houver notícias gravadas; o fuso de produção é conferido antes do deploy.

### 11. Contagem e "Mais lidas"

Sem cookie nem sessão: cada GET 200 de `/noticias/{slug}` soma 1, exceto robôs e prévias de link (expressão na spec). Recarregar a página conta de novo, o que é aceitável para um quadro editorial e dispensa aviso de cookies. "Mais lidas" consulta as visíveis com `publishedAt >= agora - 7 dias` e `viewCount > 0`, ordenadas por `viewCount DESC, publishedAt DESC`, com limite de 5.

### 12. Texto da notícia: Quill no formulário, HTMLPurifier ao gravar

O painel e a área do jornalista reaproveitam `_rich_editor.html.twig` sem alteração. O serviço `App\Service\ArticleBodySanitizer`, com o HTMLPurifier (`ezyang/htmlpurifier` 4.19), permite: `p`, `br`, `h2`, `h3`, `strong`, `b`, `em`, `i`, `u`, `s`, `ol`, `ul`, `li` (com `data-list`), `blockquote`, `a` (`href` http, https e mailto) e `img` (`src` http e https), mais o atributo `class` só com os valores `ql-align-*`. Ele remove scripts, eventos, `iframe`, `style` e `javascript:`. O tema estiliza `li[data-list="bullet"]` como lista com marcador e `.ql-align-*`. O texto é exibido com `|raw` porque já foi sanitizado ao ser gravado. O `<h1>` do Quill vira `<h2>`, porque o `<h1>` da página é o título da notícia.

Alternativa descartada durante a implementação: o `symfony/html-sanitizer`. O projeto trava os pacotes Symfony em 7.2, e todas as versões 7.2.x têm alertas de segurança justamente de desvio do sanitizador (CVE-2026-45753, CVE-2026-48761 e outros), corrigidos só na 6.4.50+, 7.4.23+ e 8.1.11+. Atualizar o Symfony está fora deste change. Com jornalistas de fora da equipe escrevendo, a sanitização deixa de ser opcional: sem ela, um jornalista poderia gravar um script que roda na sessão do admin.

### 13. Painel em controllers próprios

`App\Controller\admin\AdminArticleController` (prefixo `/admin/noticias`, rotas `admin_article_*`) e `AdminAuthorController` (`/admin/jornalistas`, rotas `admin_author_*`) ficam separados do `AdminContentController`, que tem mais de 1000 linhas e está sendo alterado por `isolamento-entre-sites`. Eles estão sob `^/admin` (`ROLE_ADMIN`). Os itens "Notícias" (para todos do painel) e "Jornalistas" (só para `workGroup` 0) em `templates/admin/wab_base.html.twig` só aparecem com `currentTenant.theme == 'noticias'`.

O formulário de notícia (`templates/admin/article/_form.html.twig`, compartilhado com a área do jornalista) tem: título, slug, chapéu, linha fina, editoria principal, outras categorias (caixas de marcar), autor (só no painel: jornalistas aprovados ou "Sem autor"), data e hora, situação, capa (legenda e crédito), texto, 3 linhas de link relacionado e "Leia também" (só no painel). O slug é gerado e deduplicado no servidor, e os links seguem a mesma normalização dos itens de menu.

### 14. Menu do tema

Uma função Twig nova, `news_menu_categories()`, chamada só pelo `_header` do tema `noticias`, devolve as categorias raiz com `showInHeader` que têm notícias visíveis (como principal ou entre as outras) ou páginas, nelas ou nas subcategorias. O menu termina com o link "Seja um autor" (`/jornalista/cadastro`). A global `headerCategories` e o menu do cetec não mudam.

### 15. Exportação, importação e exclusão

- O `TenantExporter` ganha as chaves `authors` (dados, situação, `user` pelo id exportado e foto de `uploads/author`) e `articles` (com `category`, `categories`, `author` pelos ids exportados e capa de `uploads/article`).
- O `TenantImporter` recria os autores depois dos usuários e as notícias depois das categorias e dos autores, mapeando os ids antigos para os novos, com `viewCount` 0, dentro da mesma transação.
- A exclusão no `SuperAdminController` apaga as capas e as fotos (consulta explícita por tenant, por DBAL) antes de os registros saírem, mas só as que nenhum outro tenant usa: o importador mantém os nomes dos arquivos, e o site importado divide os arquivos com o original. Os usuários do tenant já são apagados antes do tenant, o que remove os autores pelo `CASCADE`.

### 16. Jornalista no formulário de usuário do `/superadmin`

Os formulários `templates/superadmin/user/new.html.twig` e `edit.html.twig` ganham a opção "Jornalista" (`workGroup` 3), e o `SuperAdminController` só aceita os valores 0 a 3. Ao editar um jornalista, a opção vem marcada e o salvamento mantém o `workGroup` 3. Criar um jornalista por ali não cria o `Author`: sem autor aprovado, o login é recusado (decisão 5). Assim o cadastro continua nascendo só pelo formulário público.

### 17. Conferência local em `noticias.localhost`

As fixtures ganham um terceiro tenant, `noticias.localhost`, com:

- tema `noticias`, nome "AJoPE — Local", cores `#072156` e `#FDB60F` e o logo de `var/jornalista/logo.png`;
- um admin, um editor e um revisor;
- jornalistas em cada situação, com login: `jornalista.aprovada`, `jornalista.pendente`, `jornalista.recusado` e `jornalista.suspenso`, todos `@exemplo.com`;
- editorias, uma delas vazia e marcada para o cabeçalho;
- cerca de 25 notícias: com e sem capa, chapéu e links; em uma e em várias categorias; com e sem autor; algumas no "Leia também"; um rascunho; uma agendada; visualizações variadas;
- uma página "Sobre" com blocos.

Navegadores e o curl resolvem `*.localhost` para 127.0.0.1 sem mexer no `/etc/hosts`, e os tenants `127.0.0.1` e `localhost` continuam como estão.

## Risks / Trade-offs

- [Os três changes mexem no `WabPublicController`, no `security.yaml` e nas mesmas specs] → Arquivar `newsletter-csv-e-rotas-de-tema` antes deste. Os requisitos de papéis, login, exportação e exclusão que o `isolamento-entre-sites` modifica não são modificados aqui; entram requisitos novos em `noticias` e em `isolamento-por-dominio`. O user checker é encadeado. Antes de arquivar, conferir de novo os deltas.
- [E-mail único em toda a base] → Quem já tem usuário com o mesmo e-mail em outro site (ou no mesmo) recebe "Este e-mail já está cadastrado." e precisa usar outro e-mail. Isso revela que o e-mail existe na base. É aceitável num cadastro que passa por aprovação, e mudar a unicidade do `User` está fora do escopo.
- [Cadastros de spam] → O CSRF e o campo isca barram os robôs simples. O que passar fica pendente, sem acesso, e o admin recusa e exclui. Se o volume crescer, um limitador por IP pode entrar depois, sem mudar o esquema.
- [Jornalista sem "esqueci a senha"] → Fora do escopo. O SuperAdmin troca a senha pelo formulário de usuário do `/superadmin`, que passa a manter o `workGroup` 3 (decisão 16). Fica registrado como próxima etapa provável.
- [Editar um jornalista no `/superadmin` o transformava em admin] → O formulário de usuário só tinha as opções 0, 1 e 2, e um usuário com `workGroup` 3 seria salvo como Admin (a primeira opção). A decisão 16 corrige isso.
- [Notícias de jornalista suspenso continuam no ar] → Decisão consciente: a suspensão corta o acesso, não o conteúdo. A equipe despublica o que for preciso.
- [Produção pode ter sobrado uma tabela `article` das migrations de maio] → Antes de migrar, ler em produção se existem `article`, `article_category` ou `author`; se existir alguma, parar e falar com o Jonas.
- [Fuso padrão do PHP de produção desconhecido] → A conversão explícita funciona com qualquer fuso fixo. A tarefa de deploy confere o fuso (leitura) e registra o valor encontrado.
- [Robôs não identificados inflam "Mais lidas"] → A lista de padrões cobre os robôs e prévias comuns. Se o número parecer irreal, a lista pode crescer sem mudar o esquema.
- [Blocos do cetec dependem de estilos do `base` do cetec] → O `base` do tema `noticias` replica os tokens que os blocos usam (`--bg`, `--text`, `--radius`, classes de seção). A conferência abre uma página com os 15 tipos de bloco.
- [Logo da AJoPE sem versão em branco] → O fundo branco arredondado resolve por enquanto. O tenant pode receber depois um `darkLogo` sem mudar código.
- [Build no servidor de 1 vCPU] → O `build.sh` já é o caminho de deploy de template. As miniaturas novas (`news_square`) só são geradas quando houver notícias ou autores, e nenhum site de cliente tem.

## Migration Plan

1. Local: implementar, gerar a migration com `doctrine:migrations:diff` (só `CREATE TABLE` de `article`, `article_category` e `author`, com índices e FKs), carregar as fixtures e conferir em `noticias.localhost`, `127.0.0.1` e `localhost`.
2. Produção (leitura): conferir que não existem as tabelas `article`, `article_category` e `author`; conferir o fuso padrão do PHP 8.3 do pool e o `doctrine:migrations:status`.
3. Produção (escrita, com aprovação do Jonas): backup do banco, `git pull`, `./build.sh` e `doctrine:migrations:migrate`.
4. Produção (leitura): HTTP 200 nos 8 domínios de cliente; `/sitemap.xml` de um site cetec sem URLs de notícia; `/jornalista/cadastro` com 404 num site cetec; `/admin` de um site cetec sem os itens "Notícias" e "Jornalistas"; login dos usuários de painel que já existem funcionando.
5. Desfazer: nenhum tenant usa o tema e não há jornalistas. Basta `doctrine:migrations:migrate prev` (apaga as três tabelas, vazias) e reverter o commit, com novo `./build.sh`.

## Open Questions

- Domínio, nome oficial e cliente da AJoPE no sistema WAB: necessários só para criar o site em produção, num passo futuro pelo `/superadmin`.
- Versão do logo em branco, para o cabeçalho azul-marinho: pedir à AJoPE quando o site for criado.
- Texto do aceite do uso dos dados no cadastro de jornalista: o design usa um texto provisório ("Autorizo o uso destes dados para a análise do meu cadastro e a exibição do meu perfil de autor no portal."). A AJoPE pode trocá-lo sem mudar o comportamento.
