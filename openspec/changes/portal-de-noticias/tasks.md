# Tasks: portal-de-noticias

Pré-requisito: `newsletter-csv-e-rotas-de-tema` implementado e arquivado (este change usa o helper de template de tema que ele cria no `WabPublicController` e modifica o requisito de rotas antigas na versão dele). Se `isolamento-entre-sites` entrar antes, o user checker dele e o deste change ficam encadeados (design, decisão 5).

Conferência em `http://noticias.localhost:8000` (tenant novo das fixtures, tema `noticias`), `http://127.0.0.1:8000` (tenant A, tema wab) e `http://localhost:8000` (tenant B, tema moderno). Os testes de `tests/` estão quebrados e não contam como conferência; cada tarefa diz o que foi conferido.

## 1. Dados: entidades, papéis, migration e dependência

- [x] 1.1 Criar os enums `App\Entity\Enum\ArticleStatus` (`rascunho`, `publicada`) e `AuthorStatus` (`pendente`, `aprovado`, `recusado`, `suspenso`), o mapeamento Vich `author_photo` (`public/uploads/author`) e as entidades `App\Entity\Author` e `App\Entity\Article` com os campos, FKs e índices das decisões 1 a 3 do design (`category` principal, `categories` em `article_category`, `author` com `SET NULL`). Conferir com `bin/console doctrine:schema:validate --skip-sync` sem erro de mapeamento.
- [x] 1.2 Acrescentar em `User::getRoles()` o ramo `workGroup === 3` → `ROLE_JOURNALIST`, sem `ROLE_ADMIN`. Conferir pelos logins de 5.1: o jornalista entra só em `/jornalista` e recebe 403 no `/admin`, e admin, editor e revisor continuam com os papéis de antes.
- [x] 1.3 Criar `App\Repository\ArticleRepository` com:
  - `visibleQueryBuilder()` (publicada e `publishedAt <= agora`);
  - `findVisiblePage(?Category, int $pagina, int $porPagina)`: principal ou entre as outras, incluindo subcategorias, com `DISTINCT`;
  - `countVisible(?Category)`, `findVisibleByAuthor(Author, int $pagina)`, `findOneVisibleBySlug()` e `findNextOlder(Article)`;
  - `findMostRead(int $dias = 7, int $limite = 5)` e `findFeatured(int $limite = 3)`;
  - `slugExists(string $slug, ?int $ignorarId)` e `incrementViews(Article)` (UPDATE por DBAL com o id da entidade).

  Criar também `App\Repository\AuthorRepository` com `findApproved()`, `findOneByUser(User)`, `countPending()` e `slugExists()`. Conferir pelo uso nas tarefas dos grupos 3 a 8.
- [x] 1.4 Gerar a migration com `bin/console doctrine:migrations:diff` e conferir que ela só cria `article`, `article_category` e `author`, com os índices e as FKs (`tenant_id` CASCADE; `category_id` e `author_id` SET NULL; `article_category` CASCADE nos dois lados; `author.user_id` único e CASCADE). Aplicar com `doctrine:migrations:migrate` local e conferir `doctrine:schema:validate` sincronizado.
- [x] 1.5 Instalar `ezyang/htmlpurifier` e criar `App\Service\ArticleBodySanitizer` com a lista permitida do design (decisão 12). Conferir com um script descartável:
  - `<p>Olá</p><script>alert(1)</script><a href="javascript:x">a</a>` sai `<p>Olá</p><a>a</a>`;
  - `<ol><li data-list="bullet">x</li></ol>` e `<p class="ql-align-center">` ficam intactos.
- [x] 1.6 Acrescentar o filtro `news_square` (160×160, `outbound`) em `config/packages/liip_imagine.yaml`. Conferir com `bin/console debug:config liip_imagine filter_sets` listando o filtro.

## 2. Fixtures

- [x] 2.1 Criar `src/DataFixtures/PortalNoticiasFixtures.php` (carregada junto com o `AppFixtures`) com o tenant `noticias.localhost`, com:
  - "AJoPE — Local", tema `noticias`, cores `#072156` e `#FDB60F` e o logo copiado de `var/jornalista/logo.png`;
  - um admin, um editor e um revisor;
  - os jornalistas `jornalista.aprovada@exemplo.com` (com foto), `jornalista.pendente@exemplo.com`, `jornalista.recusado@exemplo.com` e `jornalista.suspenso@exemplo.com`, com usuário `workGroup` 3 e senha conhecida;
  - as editorias "Institucional", "Política", "Cidades" (com a subcategoria "Capital"), "Cultura" e "Esportes" (vazia, marcada para o cabeçalho);
  - cerca de 25 notícias: com e sem capa, chapéu e links; em uma e em várias categorias; da jornalista aprovada, do suspenso e sem autor; no "Leia também"; um rascunho; uma agendada; uma muito vista com 10 dias; idades de segundos a semanas;
  - a página "Sobre" com seções de texto, equipe e contato.

  Conferir com `bin/console doctrine:fixtures:load --no-interaction` sem erro e com o login do admin novo em `noticias.localhost:8000/admin`.
- [x] 2.2 Atualizar a seção de temas e a de fixtures do `README.md` com o tema `noticias`, o tenant `noticias.localhost`, os logins de admin, editor, revisor e dos quatro jornalistas, e o papel `workGroup` 3. Conferir que os comandos e logins do README funcionam como escritos.

## 3. Painel de notícias

- [x] 3.1 Criar `App\Controller\admin\AdminArticleController` (prefixo `/admin/noticias`, rotas `admin_article_index`, `_new`, `_edit` e `_delete`) com:
  - tenant do `TenantContext`;
  - editoria principal e outras categorias carregadas pelo `CategoryRepository` filtrado (a principal sai das outras; outras sem principal voltam com "Escolha a editoria principal.");
  - autor carregado pelo `AuthorRepository` filtrado, só entre os aprovados, ou vazio;
  - título obrigatório e slug gerado do título e deduplicado com `-2`, `-3`;
  - data e hora lidas em `America/Sao_Paulo` e convertidas para o fuso padrão;
  - texto pelo sanitizador `article_body`;
  - até 3 links relacionados com a normalização de URL da spec;
  - exclusão com CSRF.

  Conferir em `noticias.localhost:8000/admin`:
  - criar, editar e excluir uma notícia;
  - título vazio volta com aviso, e título repetido recebe `-2`;
  - o link `exemplo.com.br/pauta` é gravado com `https://`;
  - outras categorias sem a principal voltam com o aviso;
  - o seletor de autor só lista a jornalista aprovada.
- [x] 3.2 Criar `templates/admin/article/index.html.twig` (lista de 50 por página com título, editoria principal, autor, situação, data em horário de Brasília e visualizações), `new.html.twig`, `edit.html.twig` e `_form.html.twig`. O formulário tem título, slug, chapéu, linha fina, editoria principal, outras categorias, autor, `datetime-local`, situação, capa com prévia, legenda, crédito, texto com `admin/_rich_editor.html.twig`, 3 linhas de link e "Leia também". Autor e "Leia também" ficam em blocos que a área do jornalista pode omitir. Conferir que o formulário de edição mostra de volta todos os valores gravados, inclusive a hora em horário de Brasília e as categorias marcadas.
- [x] 3.3 Acrescentar em `templates/admin/wab_base.html.twig` o item "Notícias", só com `currentTenant.theme == 'noticias'`. Conferir que aparece em `noticias.localhost:8000/admin` e não aparece em `127.0.0.1:8000/admin` nem em `localhost:8000/admin`.
- [x] 3.4 Isolamento no painel: com o id de uma notícia do tenant de notícias, abrir `localhost:8000/admin/noticias/{id}/edit` e enviar POST para `/admin/noticias/{id}/delete` pelo tenant B. Conferir 404 nos dois casos e que a notícia continua no banco.

## 4. Tema `noticias`: estrutura, cabeçalho e rodapé

- [x] 4.1 Acrescentar a opção "Notícias (portal)" (`noticias`) nos seletores de tema de `templates/admin/settings.html.twig`, `templates/superadmin/tenant/new.html.twig` e `templates/superadmin/tenant/edit.html.twig`. Conferir que salvar com essa opção grava `theme = noticias` e que a opção vem marcada ao reabrir.
- [x] 4.2 Criar em `templates/themes/noticias/`:
  - `base.html.twig`: só modo claro, `tenant_css_vars()`, Open Sans como fonte padrão, os tokens que os blocos do cetec usam, meta e og tags e Google Analytics como no cetec;
  - `_header.html.twig`: fundo primário, botão "MENU" com painel lateral acessível por teclado, logo pela regra de `is_dark_color()` e editoria principal centralizada na página da notícia;
  - `_footer.html.twig`: fundo primário, logo, link "últimas notícias", páginas de rodapé, redes sociais e `© <ano> <nome do site>`.

  Conferir em `noticias.localhost:8000`:
  - o cabeçalho e o rodapé saem em `#072156`, com o logo da AJoPE sobre fundo branco arredondado;
  - trocando a cor primária para `#f8fafc` no `/admin/settings`, o logo aparece direto sobre o fundo (voltar a cor depois).
- [x] 4.3 Criar `App\Twig\ArticleExtension` com a função `news_menu_categories()` (categorias com notícias visíveis como principal ou entre as outras, ou com páginas) e o filtro `relative_time`, e montar o painel do "MENU" com "Início", as editorias e subeditorias, as páginas com `showInHeader`, os itens de menu personalizados ativos e "Seja um autor". Conferir:
  - "Esportes", a editoria vazia, não aparece; "Cultura", que só tem notícias como outra categoria, aparece;
  - o filtro mostra "Agora", "Há 1 minuto", "Há 5 minutos", "Há 1 hora", "Há 3 horas" e `dd/mm/aaaa` para as notícias das fixtures com essas idades.

## 5. Jornalistas: acesso, cadastro e aprovação

- [x] 5.1 Criar `App\Security\JournalistStatusChecker` (tag `security.user_checker.main`) e o listener de `LoginSuccessEvent`, e alterar `config/packages/security.yaml`: `user_checker: security.user_checker.chain.main`, `^/jornalista/cadastro` → `PUBLIC_ACCESS` e `^/jornalista` → `ROLE_JOURNALIST`, antes de `^/admin`. Conferir em `noticias.localhost:8000/login`:
  - os logins pendente, recusado e suspenso são recusados com as três mensagens da spec;
  - a aprovada vai para `/jornalista`, onde ainda não há área (404 até o grupo 6);
  - o admin vai para `/admin`;
  - logada como jornalista, `/admin/page` responde 403;
  - sem login, `/jornalista` leva ao login;
  - o login do admin de `127.0.0.1:8000` continua funcionando.
- [x] 5.2 Criar `App\Controller\pub\JournalistSignupController` (`/jornalista/cadastro`, GET e POST, 404 fora do tema `noticias`) e `templates/themes/noticias/journalist_signup.html.twig`, com os campos da spec, campo isca `website` escondido, aceite com o texto provisório do design e gravação de `User` (`workGroup` 3, tenant do domínio) e `Author` pendente numa transação. Conferir:
  - um cadastro completo aparece pendente no banco com a mensagem de recebido;
  - um e-mail repetido (por exemplo, o do admin de `127.0.0.1`) volta com "Este e-mail já está cadastrado.";
  - senha com 7 caracteres ou confirmação diferente volta com aviso;
  - um POST por curl com `website` preenchido não grava nada e mostra a mesma mensagem;
  - `localhost:8000/jornalista/cadastro` responde 404.
- [x] 5.3 Criar `App\Controller\admin\AdminAuthorController` (`/admin/jornalistas`: lista com filtro por situação e pendentes primeiro, ficha, e as ações aprovar, recusar, suspender, reativar e excluir por POST com CSRF), com a permissão `workGroup` 0 ou SuperAdmin conferida no controller, as transições da decisão 6 e o registro de `reviewedAt`/`reviewedBy`. Criar também os templates `templates/admin/author/*` e o item "Jornalistas" com o número de pendentes em `wab_base.html.twig` (tema `noticias` e `workGroup` 0). Conferir:
  - aprovar o candidato de 5.2 libera o login dele;
  - suspender a aprovada derruba a próxima requisição dela a `/jornalista` (403, depois do grupo 6), e as notícias dela continuam no ar;
  - reativar devolve o acesso;
  - excluir o recusado apaga autor, usuário e foto e libera o e-mail;
  - logado como editor e como revisor, as ações respondem 403 e o item não aparece no menu;
  - o número de pendentes do menu bate com o banco.
- [x] 5.4 Acrescentar a opção "Jornalista" (`workGroup` 3) em `templates/superadmin/user/new.html.twig` e `edit.html.twig` e fazer o `SuperAdminController` recusar `workGroup` fora de 0 a 3. Conferir:
  - editar `jornalista.aprovada@exemplo.com` no `/superadmin` vem com "Jornalista" marcado, e trocar a senha mantém o `workGroup` 3;
  - o login com a senha nova vai para `/jornalista`;
  - um POST forjado com `workGroup=7` não grava.

## 6. Área do jornalista

- [x] 6.1 Criar `App\Controller\journalist\JournalistAreaController` e `templates/journalist/base.html.twig` (visual do painel, menu "Minhas notícias" e "Meu perfil"). Cada ação confere autor aprovado e `user.tenant` igual ao tenant do domínio (403), e as rotas de notícia só acham notícias com `author` = o autor do usuário (404). Conferir:
  - a jornalista aprovada vê só as notícias dela, inclusive a que o admin criou com ela como autora;
  - abrir `/jornalista/noticias/{id}/edit` com o id de uma notícia do suspenso ou sem autor responde 404;
  - em `localhost:8000`, o login dela é recusado com "Este usuário não tem acesso a este site." (o checker não acha o autor no outro domínio); a área ainda confere o domínio por conta própria.
- [x] 6.2 Criar, editar e excluir notícias na área com o parcial `templates/admin/article/_form.html.twig`, sem autor e sem "Leia também" (o autor é sempre ela; o `featured` enviado num POST forjado é ignorado). Conferir:
  - uma notícia gravada como publicada com data de agora aparece na home do portal com `Por <nome dela>`;
  - um rascunho não aparece;
  - um `<script>` no texto é removido ao gravar.
- [x] 6.3 Criar `/jornalista/perfil`: nome, telefone, registro profissional, veículo, cidade, minibio (até 600) e foto; troca de senha com a senha atual. Conferir:
  - as mudanças aparecem na página do autor (grupo 7);
  - a senha atual errada volta com "Senha atual incorreta." sem trocar;
  - e-mail, slug e situação não aparecem como campos editáveis, e POSTs forjados com eles são ignorados.

## 7. Tema `noticias`: páginas públicas

- [x] 7.1 Criar os parciais:
  - `_article_item.html.twig`: capa `news_thumb` ou sem imagem, chapéu, título, links relacionados com marcador, e `<tempo> — Em <editoria principal>`;
  - `_featured.html.twig`: "Leia também" com miniaturas `news_square`;
  - `_most_read.html.twig`: variações com e sem miniatura;
  - `_pagination.html.twig`;
  - `_byline.html.twig`: `Por <nome>, <veículo>`, com link só para autor aprovado.

  Conferir pelo uso em 7.2 a 7.6.
- [x] 7.2 Alterar `WabPublicController::home()` para, no tema `noticias` e sem `homePage`, renderizar `themes/noticias/home.html.twig` com as 20 notícias visíveis, "Leia também" e "Mais lidas". O template mostra a primeira notícia com a linha fina, o link "Mais notícias" quando há mais de 20 e o aviso "Nenhuma notícia publicada ainda." sem notícias. Conferir em `noticias.localhost:8000/`:
  - 20 itens, sem o rascunho nem a agendada;
  - o destaque com a linha fina;
  - o "Leia também" com as 3 marcadas mais recentes;
  - "Mais lidas" de 1 a 5, sem a notícia muito vista com mais de 7 dias;
  - o layout em coluna única abaixo de 768 px.
- [x] 7.3 Alterar `articles()` para, no tema `noticias`, listar 20 por página com `?pagina=N` (404 fora do intervalo) em `themes/noticias/articles.html.twig`. Conferir `/noticias`, `/noticias?pagina=2` e `/noticias?pagina=9` (404) em `noticias.localhost:8000`, e `/noticias` em `localhost:8000` com o comportamento de antes.
- [x] 7.4 Alterar `category()` para, no tema `noticias`, mostrar em `themes/noticias/category.html.twig` o nome, a descrição, as notícias visíveis em que a categoria (ou uma subcategoria) é principal ou está entre as outras, sem repetir e paginadas, e as páginas da categoria. Conferir:
  - `/categoria/cidades` traz as notícias de "Cidades" e de "Capital", inclusive as que têm essas categorias só entre as outras, uma vez cada;
  - `/categoria/cultura` lista as notícias que só têm "Cultura" entre as outras;
  - `/categoria/nao-existe` responde 404.
- [x] 7.5 Alterar `articleShow()` para, no tema `noticias`, carregar a notícia visível pelo slug (404 para rascunho, agendada, inexistente ou de outro site), somar a visualização com a regra de User-Agent da spec e renderizar `themes/noticias/article.html.twig`. A página tem editoria principal no cabeçalho, título, linha fina, `_byline`, data e hora, "Atualizado há…", capa com legenda e crédito, texto com estilos de `li[data-list="bullet"]` e `.ql-align-*`, data no fim, "Próximo" e "Mais lidas" com miniaturas. Conferir:
  - duas aberturas com `curl -A "Mozilla/5.0"` somam 2 visualizações; `curl -A "WhatsApp/2.23"` e `curl -A ""` não somam;
  - a notícia mais antiga não mostra "Próximo";
  - uma notícia sem capa não mostra a área da imagem;
  - a notícia da aprovada mostra "Por" com link, a do suspenso sem link e a sem autor sem a linha;
  - o slug do rascunho responde 404;
  - em `localhost:8000` (moderno), `/noticias/{slug}` de uma notícia publicada responde 404.
- [x] 7.6 Criar a rota `/autor/{slug}` (`pub_author`, 404 fora do tema `noticias`) e `themes/noticias/author.html.twig`, com foto `news_square`, nome, veículo, cidade, minibio e as notícias visíveis dela, paginadas. Conferir:
  - `/autor/<slug da aprovada>` mostra o perfil e as notícias dela;
  - os slugs do suspenso, do pendente e do recusado respondem 404;
  - `localhost:8000/autor/<slug>` responde 404.
- [x] 7.7 Criar `themes/noticias/page.html.twig`, `_block.html.twig` (inclui os blocos do cetec) e `team_member_classic.html.twig`/`team_member_modern.html.twig` (copiados do cetec, estendendo o base do tema `noticias`). Conferir em `noticias.localhost:8000`:
  - uma página com os 15 tipos de bloco: todos renderizam nas cores da AJoPE, sem erro no log nem no console;
  - `/p/sobre` e um `/membro/{slug}` com perfil ativo abrem;
  - `/videos` e `/estudos` respondem 404.
- [x] 7.8 Rodar `bin/console tailwind:build` e conferir que as classes novas do tema `noticias`, da área do jornalista e do cadastro saem no CSS compilado; conferir que `127.0.0.1:8000/` e `localhost:8000/` continuam com a mesma aparência de antes do change.

## 8. Sitemap, exportação, importação e exclusão

- [x] 8.1 Alterar `SitemapController` para, no tema `noticias`, acrescentar `/noticias/{slug}` de cada notícia visível (prioridade 0.7, `lastmod` do `updatedAt`). Conferir em `noticias.localhost:8000/sitemap.xml` as notícias visíveis sem o rascunho e a agendada, e em `127.0.0.1:8000/sitemap.xml` o mesmo XML de antes, sem notícias.
- [x] 8.2 Incluir os autores e as notícias em `TenantExporter` (chaves `authors` e `articles`, com fotos de `uploads/author` e capas de `uploads/article`) e em `TenantImporter`. A importação mapeia usuário, autor e categorias para os registros novos, com visualizações zeradas e dentro da transação, e as consultas usam `findBy(['tenant' => $tenant])`. Conferir exportando o tenant de notícias pelo `/superadmin` aberto em `noticias.localhost:8000` e importando o ZIP com o domínio `noticias2.localhost`, resolvendo os conflitos de usuário no assistente. O site novo deve ter:
  - a mesma quantidade de notícias e de jornalistas;
  - capas e fotos abrindo;
  - editoria principal, outras categorias, autor e situação de cada notícia e de cada jornalista iguais;
  - visualizações zeradas;
  - o login da jornalista aprovada importada funcionando só pela situação dela.
- [x] 8.3 Incluir na exclusão do tenant no `SuperAdminController` a remoção das capas e das fotos dos autores (consulta explícita por tenant). Conferir excluindo o tenant `noticias2.localhost` de 8.2:
  - as linhas de `article`, `article_category` e `author` dele somem;
  - a capa e a foto exclusivas dele saem de `public/uploads`, e as que ele divide com `noticias.localhost` (o importador mantém os nomes dos arquivos) continuam.

## 9. Produção

- [x] 9.1 Conferência antes (leitura, com `ssh runcloud@162.243.56.194`): (10/11/2026, leitura: última aplicada Version20261006170000 (28 executadas, nenhuma nova); tabelas `article`, `article_category` e `author` ausentes; fuso: o PHP 8.3 CLI responde UTC; o pool (config não legível como runcloud; a auditoria de 21/10 registra `date.timezone = America/Sao_Paulo`) grava em America/Sao_Paulo, porque o último contato do site ficou com 11:18 de 10/11, horário de Brasília)
  - `doctrine:migrations:status` (última aplicada `Version20261006170000`);
  - ausência das tabelas `article`, `article_category` e `author`;
  - fuso padrão do PHP 8.3 do pool (`/RunCloud/Packages/php83rc/bin/php -r 'echo date_default_timezone_get();'`).

  Se existir alguma dessas tabelas, parar e falar com o Jonas. Anotar o fuso encontrado neste arquivo.
- [x] 9.2 Backup (escrita no servidor, com aprovação do Jonas): dump do banco do app-wab-sites fora do docroot e `git rev-parse HEAD` do webapp anotado, antes do deploy. (10/11/2026 14:35 UTC, autorizado pelo Jonas no pedido de 10/11 11:07: HEAD f089e6b anotado em `~/backups/wab-sites-HEAD-antes-20261110-1435.txt`; dump em `~/backups/wab-sites-antes-deploy-20261110-1435.sql.gz` (chmod 600, com nice), 17 tabelas e 11 usuários)
- [x] 9.3 Deploy (escrita, com aprovação do Jonas): `git push origin main` local; no servidor, como runcloud, em `~/webapps/app-wab-sites`: (10/11/2026 14:37 UTC (11:37 em Brasília): 0666069 no ar, junto com isolamento-entre-sites e newsletter-csv-e-rotas-de-tema; git pull, `./build.sh` (com nice) e a migration Version20261110015129 aplicada; HTTP 200 em `/` nos 8 domínios)
  - `git pull`;
  - `./build.sh` (mudou template, CSS, dependência e configuração de segurança);
  - `bin/console doctrine:migrations:migrate --no-interaction` (cria as três tabelas).

  Conferir HTTP 200 em `/` dos 8 domínios de cliente: marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br e site.abifer.com.br.
- [ ] 9.4 Conferência depois (leitura): (10/11/2026: migrations sem pendência; o sitemap de marapossi.com.br tem 19 URLs e nenhuma de notícia; `/noticias` 200 e `/noticias/qualquer` 404 nos 8 domínios; `/jornalista/cadastro` e `/autor/qualquer` 404 nos 8; nenhum erro novo nos logs do nginx e do apache e nenhum 5xx depois do deploy. Pendente: o Jonas conferir o login de um usuário de painel e o menu do /admin de um site cetec)
  - `doctrine:migrations:status` sem pendências;
  - `/sitemap.xml` de marapossi.com.br sem URLs de notícia;
  - `/noticias` e `/noticias/qualquer` nos 8 domínios com o mesmo comportamento de antes (lista vazia do cetec e 404);
  - `/jornalista/cadastro` e `/autor/qualquer` com 404 nos 8 domínios;
  - o Jonas confere que o login de um usuário de painel que já existe continua funcionando e que o menu do `/admin` de um site cetec não mostra "Notícias" nem "Jornalistas".

## Workflow follow-up

- Criar o site da AJoPE em produção fica para um próximo passo, pelo `/superadmin`, quando houver domínio, nome oficial, cliente no sistema WAB e, se possível, o logo em branco e o texto definitivo do aceite.
- Próximas etapas prováveis, fora deste change: "esqueci a senha" do jornalista, e-mails de aviso do cadastro e busca.
- Registro: a AJoPE ainda não é cliente no sistema WAB. Quando for cadastrada, registrar o trabalho num ticket dela; até lá, se o Jonas quiser, num ticket interno do cliente WAB 196.
- Antes de arquivar, conferir que `newsletter-csv-e-rotas-de-tema` já foi arquivado e reler os deltas de `construtor-de-paginas`, `isolamento-por-dominio` e `gestao-de-sites` contra as specs principais (e contra o `isolamento-entre-sites`, se ele tiver sido arquivado antes).
- Arquivar este change depois da conferência de produção.
