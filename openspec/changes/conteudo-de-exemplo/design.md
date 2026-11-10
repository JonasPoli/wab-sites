# Design: conteudo-de-exemplo

## Context

Motivação em `proposal.md`. Requisitos em `specs/conteudo-de-exemplo/spec.md`.

Estado atual que molda a solução:

- `ajope.wab.com.br` já é tenant em produção, com o tema `noticias`, cores `#00275c` e `#feb101`, favicon e nenhuma notícia (home pública em 10/11/2026). O id do tenant não foi conferido: a leitura do banco de produção por SSH foi bloqueada nesta sessão. O comando recebe o domínio, então o id não é necessário.
- Notícias (`Article`), jornalistas (`Author`) e editorias (`Category`) têm `tenant_id`. O `Author` exige um `User` (coluna obrigatória e única, `ON DELETE CASCADE`), que é quem faz login. `Article.categories` é ManyToMany com `ON DELETE CASCADE` na tabela de ligação.
- No console não há request, então o `TenantSubscriber` não roda e o filtro `tenant_filter` do Doctrine fica desligado.
- `PortalNoticiasFixtures` já gera capa em degradê com GD (`makeImage`) e tem o `slugify`. `app:generate-dummy-data` baixa fotos do picsum.photos com `file_get_contents`.
- Uploads de notícia e de jornalista pelo VichUploader (`article_image` em `public/uploads/article`, `author_photo` em `public/uploads/author`). O `delete_on_remove` não está configurado, então vale o padrão (`true`): remover a entidade pelo EntityManager apaga o arquivo.
- O PHP CLI do servidor está em UTC e o pool do site grava em America/Sao_Paulo. As datas vão para colunas `datetime` sem fuso.

## Goals / Non-Goals

**Goals:**
- Um comando que qualquer pessoa da WAB roda apontando o domínio e que deixa o portal apresentável em segundos.
- Saída limpa: remover o exemplo sem risco para o conteúdo real, inclusive depois que a equipe do cliente começou a cadastrar.
- Estrutura que aceita outros tipos de conteúdo de exemplo no futuro (páginas no cetec, por exemplo) sem mudar a interface do comando.

**Non-Goals:**
- Conteúdo de exemplo para os temas `cetec`, `moderno` e `wab` (o comando recusa).
- Páginas institucionais, banners, blocos ou membros de equipe de exemplo.
- Mudar `app:generate-dummy-data` ou as fixtures.
- Selo "Exemplo" no site público ou na área do jornalista.

## Decisions

### 1. Comando novo `app:conteudo-exemplo <dominio>`, com um gerador por tema
Assinatura: `app:conteudo-exemplo <dominio> [--quantidade=20] [--remover] [--substituir] [--simular]`. O comando acha o tenant por `TenantRepository::findByDomain()` (o mesmo match exato da resolução de domínio), escolhe o gerador pelo `Tenant.theme` e delega. Nesta etapa só existe o gerador `noticias`. Uma interface pequena (`supports(theme)`, `plan()`, `generate()`, `remove()`), com os geradores marcados por tag, permite acrescentar outros temas sem mexer no comando.

Alternativas: estender `app:generate-dummy-data` (descartado: ele é fixo em `127.0.0.1` e sobrescreve o tenant); rodar `PortalNoticiasFixtures` (descartado: o `doctrine:fixtures:load` apaga o banco inteiro).

### 2. Marca de exemplo numa coluna `sample` em `article`, `author` e `category`
Coluna booleana `NOT NULL DEFAULT 0` nas três tabelas, criada por `doctrine:migrations:diff`. Os registros que já existem ficam `false`. Os formulários do painel e da área do jornalista não expõem o campo, então editar não muda a marca. O usuário do jornalista de exemplo não ganha coluna: ele é achado pelo `Author` marcado.

Alternativas consideradas:
- prefixo no slug (`exemplo-...`): aparece na URL que o cliente vai ver, e um slug real pode começar igual;
- arquivo com os ids em `var/`: some num servidor novo, não vai na exportação e não serve para o sitemap nem para o painel;
- marcar só pelo e-mail `@exemplo.invalid`: cobre jornalistas, mas não notícias nem editorias.

A coluna também é o que permite o `noindex`, o filtro do sitemap e o selo no painel.

### 3. Textos fictícios num catálogo em PHP
Um catálogo fixo de 30 notícias (título, chapéu opcional, linha fina, 3 ou 4 parágrafos com um intertítulo, uma lista ou uma citação), escrito em pt-BR com temas de uma associação de jornalistas e de um portal regional: institucional, formação, cidades, cultura e comunicação. Pessoas, cidades e entidades citadas são inventadas, nunca reais. As editorias vêm do catálogo: Institucional, Cidades (com a subeditoria Capital), Cultura e Formação. O comando sorteia as notícias sem repetir, por isso `--quantidade` vai até 30 (o tamanho do catálogo); com mais, recusa e diz o máximo.

Os atributos de layout (capa, chapéu, links, idade, "Leia também", visualizações, notícia editada, várias editorias) não ficam no catálogo: um distribuidor determinístico os aplica pela posição, para que qualquer quantidade a partir de 8 cubra todas as variações da spec. Abaixo de 8, cobre o que couber.

Alternativa: Faker (`fakerphp/faker`) para gerar texto. Descartado porque o texto do Faker em pt-BR não tem cara de notícia, e o layout precisa de títulos e linhas finas com o tamanho de verdade.

### 4. Fotos do picsum.photos, com degradê local como reserva
Capa: `https://picsum.photos/seed/<slug>/1200/675`. Foto do jornalista: `.../seed/<slug>/400/400`. A semente é o slug, então o mesmo lote traz sempre as mesmas fotos. Cada download tem tempo-limite de 10 s. Se falhar ou não vier JPEG, o comando gera o degradê com GD, com o código do `makeImage` das fixtures movido para um serviço compartilhado (`PlaceholderImageFactory`), que as fixtures passam a usar. Sem GD e sem rede, a notícia fica sem capa e o comando avisa. Cerca de 1 em cada 5 notícias fica sem capa de propósito, e 1 dos 3 jornalistas fica sem foto. O nome do arquivo segue o padrão dos uploads (`exemplo-<slug>-<hash curto>.jpg`), e o arquivo é gravado direto na pasta do mapeamento do Vich, como fazem as fixtures.

Alternativa: só degradês (como as fixtures). Fica abstrato demais para mostrar o portal a um cliente.

### 5. Jornalistas de exemplo sem acesso
Três jornalistas (nomes inventados), aprovados, com `reviewedAt`, cidade, veículo e minibio. O `User` de cada um tem `workGroup` jornalista, tenant do site, e-mail e username `<slug>@exemplo.invalid` (TLD reservado pela RFC 2606, que não recebe e-mail) e senha com hash de `random_bytes(32)`, que não é guardada nem exibida. Se o e-mail já existir (rodada anterior que falhou no meio), o comando acrescenta um sufixo.

### 6. Datas no horário de Brasília
O gerador calcula as datas em `America/Sao_Paulo`, explicitamente, e não pelo fuso do PHP CLI. No servidor o CLI está em UTC: sem isso, "agora menos 2 minutos" seria gravado 3 horas à frente do relógio do site, e a notícia ficaria agendada (invisível) por 3 horas. A notícia mais recente fica 2 minutos antes de agora; a mais antiga, cerca de 30 dias antes. A notícia "editada" tem `updatedAt` 2 horas depois da publicação.

### 7. Gravação numa transação; remoção em ordem
A geração roda dentro de `wrapInTransaction`. Se algo falhar, o banco volta e os arquivos já gravados naquela rodada são apagados. `--simular` monta o mesmo plano (títulos, editorias que seriam criadas ou reaproveitadas, jornalistas), imprime e sai sem persistir nem baixar foto.

Remoção, também numa transação:
1. notícias `sample` do tenant (o Vich apaga as capas; a ligação com as editorias sai em cascata);
2. jornalistas `sample` sem nenhuma notícia restante (o Vich apaga a foto), e depois o `User` de cada um;
3. editorias `sample` sem notícia, sem página e sem subcategoria restantes, das folhas para a raiz.

O que fica por ter conteúdo real entra no aviso final. `--substituir` é a remoção seguida da geração, na mesma transação.

### 8. Isolamento por tenant
O comando roda sem o filtro do Doctrine, então toda consulta diz o tenant: `findBy(['tenant' => $tenant, 'sample' => true])` nas três entidades, e a contagem de "conteúdo real" de uma editoria ou de um jornalista é feita por consultas com `tenant`. A unicidade de slug é conferida dentro do tenant (`slug` + `tenant`), com sufixo `-2`, `-3`... quando já existe. As entidades novas recebem o tenant explicitamente. Não há `PageSection` nem `PageBlock` envolvidos.

### 9. Buscadores e painel
- `SitemapController`: o `findAllVisible()` usado no sitemap passa a excluir `sample = true` (parâmetro ou método próprio; a lista pública de notícias continua usando a versão sem filtro).
- `themes/noticias/article.html.twig` sobrescreve o bloco `robots` com `noindex, follow` quando a notícia é de exemplo. Esse template só existe no tema `noticias`; `cetec`, `moderno` e `wab` não têm página de notícia.
- `templates/admin/article/index.html.twig`, `author/index.html.twig` e `category/index.html.twig` mostram `<span class="badge bg-warn">Exemplo</span>` ao lado do nome. São templates do painel, não de tema.

### 10. Exportação e importação
`TenantExporter` grava `sample` em `articles`, `authors` e `categories`. `TenantImporter` lê `$item['sample'] ?? false`. Um ZIP antigo continua importando, com tudo real.

## Risks / Trade-offs

- [Notícia falsa com o nome de uma associação real indexada ou compartilhada] → `noindex` e fora do sitemap. A home e as listas continuam indexáveis, porque são as páginas reais do site. Antes de o portal ser divulgado, rodar `--remover`; isso fica nas tarefas e no aviso final do comando.
- [picsum.photos fora do ar ou bloqueado no servidor] → reserva em degradê local; o comando conclui e avisa quantas capas vieram do degradê.
- [Fotos do picsum sem relação com o texto] → aceito: o objetivo é mostrar o layout. As fotos são do Unsplash, com licença livre.
- [Equipe do cliente edita uma notícia de exemplo achando que é dela] → o selo "Exemplo" no painel avisa. Se ela quiser mantê-la, hoje não há botão para desmarcar; a remoção mantém editorias e jornalistas com conteúdo real, mas apaga a notícia de exemplo editada. Isso é documentado no aviso do `--remover`.
- [Servidor de 1 vCPU] → 20 downloads de cerca de 150 KB e uma transação pequena; rodar com `nice`. As miniaturas do LiipImagine são feitas na primeira visita, como já acontece hoje.
- [Usuários `@exemplo.invalid` na tabela `user`] → não entram (senha desconhecida) e saem com o `--remover`; aparecem na lista de jornalistas do painel com o selo.

## Migration Plan

1. Local: migration por `doctrine:migrations:diff` (três colunas `sample`), comando, ajustes no sitemap, no template, no painel e na exportação; conferência em `noticias.localhost` e `127.0.0.1` com as fixtures.
2. Produção (escrita, com aprovação do Jonas): backup (dump do banco e `git rev-parse HEAD`), `git pull`, `doctrine:migrations:migrate`, `./build.sh` (mudaram templates), HTTP 200 nos domínios.
3. Produção (escrita, com aprovação do Jonas): `--simular` e depois o comando para `ajope.wab.com.br`, com o PHP 8.3 do pool.
4. Reversão: `app:conteudo-exemplo ajope.wab.com.br --remover` tira o conteúdo; para desfazer o código, reverter o commit e rodar `doctrine:migrations:migrate prev` (a coluna sai sem perda de dado real).

## Open Questions

- Quando o portal da AJoPE for divulgado, quem roda o `--remover`? Fica como lembrete no histórico, sem mudar o comando.
