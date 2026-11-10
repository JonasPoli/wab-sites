# Proposta: portal-de-noticias

## Why

A AJoPE, associação de jornalistas, vai ter um portal de notícias no WAB Sites. O Jonas definiu o pedido em `var/jornalista/`: as telas de referência da home e da página da notícia, num layout comum de portal (lista com foto à esquerda e barra lateral; notícia em coluna única), e o logo da AJoPE, cujas cores substituem as da referência (o recorte `home da noticias.png` já mostra o cabeçalho em azul-marinho). No portal, os jornalistas candidatos se cadastram e só publicam depois de aprovados.

Hoje o sistema não tem notícia. `/noticias` renderiza uma lista sempre vazia e `/noticias/{slug}` responde 404. A página (`Page`) não tem autor, chapéu, linha fina, legenda da foto nem contagem de leitura, os três temas (cetec, moderno e wab) são de site institucional, e o painel só tem administrador, editor e revisor, sem cadastro público de usuários.

Decisões do Jonas de 09/11/2026:

- cadastro de notícias separado das páginas, com tema próprio para notícias;
- notícia em várias categorias, com uma editoria principal;
- autor da notícia é um jornalista cadastrado; o jornalista se candidata pelo site, o admin do site aprova e, aprovado, ele publica direto;
- página pública do autor;
- "Mais lidas" pela contagem de visualizações;
- nesta etapa, só o tema e os cadastros, sem criar o site da AJoPE em produção.

## What Changes

- Nova entidade **Notícia** (`Article`, tabela `article`), com tenant e: título, slug único no site, chapéu, linha fina, texto em editor rico, editoria principal e outras categorias do site, autor (um jornalista cadastrado), imagem de capa com legenda e crédito, data e hora de publicação, situação (rascunho ou publicada), até 3 links relacionados, marcação "Leia também" e contador de visualizações.
- Nova entidade **Autor** (`Author`), o perfil do jornalista ligado a um usuário do site: nome, slug, telefone, registro profissional, veículo, cidade, minibio, foto e situação (pendente, aprovado, recusado ou suspenso).
- **Cadastro do jornalista candidato** em `/jornalista/cadastro`, aberto ao público no tema `noticias`. Ele cria o usuário e o autor como pendentes.
- **Aprovação de jornalistas** em `/admin/jornalistas`, só pelo admin do site (`workGroup` 0) ou pelo SuperAdmin: aprovar, recusar, suspender, reativar e excluir candidato pendente ou recusado. O login do jornalista só é aceito com o cadastro aprovado.
- **Área do jornalista** em `/jornalista`, separada do `/admin`: o jornalista aprovado cria, edita, publica e exclui as próprias notícias e edita o próprio perfil. Um papel novo, `workGroup` 3 (ROLE_JOURNALIST), sem acesso ao `/admin`.
- Cadastro **Notícias** no `/admin` (listar, criar, editar e excluir), aberto a administrador, editor e revisor do site. A equipe edita qualquer notícia do site e escolhe o autor entre os jornalistas aprovados, ou deixa sem autor. Os itens "Notícias" e "Jornalistas" aparecem no menu do painel só quando o site usa o tema `noticias`. O texto passa por um sanitizador de HTML ao ser gravado.
- Novo tema **`noticias`** em `templates/themes/noticias/`, que pode ser escolhido no `/admin/settings` e no `/superadmin`:
  - Home: lista das notícias publicadas, da mais recente para a mais antiga. A primeira sai em destaque, com a linha fina. Cada item traz capa, chapéu, título, até 3 links relacionados e `Há X horas — Em <editoria principal>`. Na barra lateral ficam "Leia também" (com miniaturas) e "Mais lidas" (de 1 a 5).
  - Página da notícia em `/noticias/{slug}`: editoria principal no cabeçalho, título, linha fina, `Por <autor>, <veículo>` com link para o autor, data e "Atualizado há…", capa com legenda e crédito, texto, data no fim, link "Próximo" e "Mais lidas" com miniaturas.
  - `/noticias` (todas, paginadas), `/categoria/{slug}` (notícias que têm a categoria como principal ou entre as outras, mais as das subcategorias, paginadas) e `/autor/{slug}` (perfil e notícias do jornalista aprovado).
  - Cabeçalho na cor primária do site, com botão "MENU" que abre a lista de editorias, páginas e itens de menu, e o logo. Rodapé na cor primária, com as páginas de rodapé e as redes sociais.
  - As páginas institucionais (`/p/{slug}`), os blocos e o perfil de membro também funcionam no tema, reaproveitando os blocos do cetec.
- "Mais lidas": as 5 notícias publicadas nos últimos 7 dias com mais visualizações. Cada abertura de `/noticias/{slug}` soma uma visualização, exceto as de robôs e de pré-visualizações de link.
- `/noticias/{slug}` e `/noticias` passam a mostrar as notícias do site quando o tema é `noticias`. Nos outros temas, o comportamento atual continua: 404 no detalhe e lista vazia (ou 404, conforme `newsletter-csv-e-rotas-de-tema`).
- O sitemap passa a incluir as notícias publicadas.
- Exportar, importar e excluir um site passam a incluir as notícias, os autores e as imagens deles.
- Dados de exemplo em localhost: um tenant com o tema `noticias`, as cores da AJoPE, editorias, jornalistas em todas as situações e notícias, para conferir sem tocar em produção.
- Fora desta etapa: busca, botões de compartilhar, vídeo embutido, carrossel de vídeos, selo "AO VIVO", revisão das notícias antes de publicar, e-mails de aviso do cadastro, autor sem login e a criação do site da AJoPE em produção (domínio e cliente ainda não definidos; a AJoPE não está no sistema WAB).

## Capabilities

### New Capabilities

- `noticias`: cadastro de notícias por site, editorias, publicação, jornalistas (candidatura, aprovação, acesso e área própria), páginas públicas do tema `noticias` (home, lista, editoria, notícia e autor), "Mais lidas", "Leia também", "Próximo", links relacionados, contagem de visualizações e notícias e autores na exportação, importação e exclusão do site.

### Modified Capabilities

- `navegacao-e-aparencia`: "Tema do tenant" passa a incluir o tema `noticias`; "Logos claro e escuro" ganha a regra do cabeçalho colorido do tema `noticias`.
- `construtor-de-paginas`: "Rotas antigas de notícias, vídeos e estudos" deixa de valer para `/noticias` e `/noticias/{slug}` no tema `noticias`; "Sitemap" passa a listar as notícias publicadas.
- `isolamento-por-dominio`: novo requisito "Papel de jornalista" (`workGroup` 3 recebe ROLE_JOURNALIST, sem ROLE_ADMIN). O requisito "Papéis pelo workGroup" não é alterado.
- `gestao-de-sites`: "Usuários pelo SuperAdmin" passa a oferecer e manter o `workGroup` 3 (jornalista). Hoje, editar um jornalista no `/superadmin` o salvaria como admin do site.

## Impact

- Código novo:
  - Entidades e repositórios: `App\Entity\Article` e `App\Entity\Author`, com `ArticleRepository` e `AuthorRepository`, e os enums `ArticleStatus` e `AuthorStatus`.
  - Controllers do painel para notícias e jornalistas, e controllers do cadastro público e da área do jornalista.
  - Segurança: um user checker para a situação do jornalista e um listener que manda o jornalista para `/jornalista` depois do login.
  - Templates: `templates/admin/article/*`, `templates/admin/author/*`, `templates/journalist/*` e `templates/themes/noticias/*`.
  - Twig: o filtro de tempo relativo ("Há 3 horas") e a função do menu de editorias.
- Código alterado:
  - `User::getRoles()` (`workGroup` 3) e `config/packages/security.yaml` (`access_control` de `/jornalista` e `user_checker` encadeado).
  - `WabPublicController` (`articles()`, `articleShow()`, `category()` e `home()` no tema `noticias`, mais a rota `/autor/{slug}`) e `SitemapController`.
  - `TenantExporter`, `TenantImporter` e a exclusão no `SuperAdminController`.
  - Formulários de usuário do `/superadmin` (`templates/superadmin/user/{new,edit}.html.twig` e o `SuperAdminController`), com a opção Jornalista.
  - Seletores de tema em `templates/admin/settings.html.twig` e `templates/superadmin/tenant/{new,edit}.html.twig`, o menu do painel em `templates/admin/wab_base.html.twig` e `AppFixtures`.
- Dependência nova: `ezyang/htmlpurifier` (o `symfony/html-sanitizer` 7.2, a versão travada no projeto, tem alertas de segurança sem correção nessa linha; decisão do Jonas de 09/11/2026).
- Banco: uma migration que cria as tabelas `article`, `article_category` e `author`. O mapeamento de upload `article_image` (`public/uploads/article`) já existe e passa a ser usado; o mapeamento `author_photo` (`public/uploads/author`) é novo.
- Temas cetec, moderno e wab: nenhum template alterado. Os 8 sites de clientes continuam iguais.
- Produção: deploy com `git pull`, `./build.sh` e a migration, com backup antes. Nenhum tenant é criado nem alterado.
- Ordem com os changes abertos: este change altera "Rotas antigas de notícias, vídeos e estudos" sobre o texto de `newsletter-csv-e-rotas-de-tema`, que precisa ser arquivado antes. Ele não altera os requisitos que `isolamento-entre-sites` modifica (papéis, login, exportação, exclusão, "Entrar como" e editores); acrescenta requisitos próprios em `noticias` e em `isolamento-por-dominio` e modifica só "Usuários pelo SuperAdmin", que nenhum outro change toca. O user checker do jornalista é encadeado, para conviver com o do `isolamento-entre-sites`.
