# Proposta: conteudo-de-exemplo

## Why

O portal da AJoPE (Associação dos Jornalistas Espíritas) já está no ar em https://ajope.wab.com.br com o tema `noticias`, mas sem nenhuma notícia: a home mostra só "Nenhuma notícia publicada ainda." (conferido em 10/11/2026). Para mostrar o layout ao cliente, o Jonas precisa de notícias de exemplo nesse domínio, e hoje não há jeito de criá-las fora do painel, uma a uma.

As fixtures do portal (`PortalNoticiasFixtures`) não servem: elas apagam o banco inteiro e só rodam em desenvolvimento. O comando antigo `app:generate-dummy-data` também não: ele é fixo no domínio `127.0.0.1`, sobrescreve nome, cores e logo do tenant e gera um site institucional, não notícias.

## What Changes

- Novo comando de console que recebe o domínio de um site e injeta conteúdo de exemplo nele. Para um site com tema `noticias`, cria 20 notícias por padrão (`--quantidade` muda o número), com as editorias e os jornalistas de que elas precisam.
- As notícias de exemplo variam de propósito para exercitar o layout: capa com foto ou sem capa, com e sem chapéu, de 0 a 3 links relacionados, publicação de minutos atrás a semanas atrás, algumas marcadas para o "Leia também" e algumas com visualizações nos últimos 7 dias, para aparecer em "Mais lidas".
- Tudo o que o comando cria fica marcado como exemplo. O mesmo comando remove só o que é de exemplo (`--remover`) ou troca por um lote novo (`--substituir`); o que a equipe do site cadastrou não é tocado.
- O comando nunca altera o tenant (nome, cores, logo, tema, configurações) e recusa domínio que não existe ou tema que não sabe preencher.
- As notícias de exemplo não entram no sitemap e a página delas sai com `noindex`, para que o buscador não guarde notícia falsa com o nome de uma associação real.
- No painel, as listas de notícias, de jornalistas e de categorias mostram o selo "Exemplo" nesses registros.
- Os jornalistas de exemplo não conseguem entrar no site: o usuário deles nasce com senha aleatória que ninguém conhece e e-mail em domínio reservado.
- A marca de exemplo vai na exportação do site e volta na importação.

## Capabilities

### New Capabilities
- `conteudo-de-exemplo`: geração, marcação e remoção de conteúdo de exemplo num site, a partir do domínio, e como esse conteúdo aparece para buscadores e no painel.

### Modified Capabilities

(nenhuma; as regras de notícias, jornalistas e sitemap que este change toca vêm do change `portal-de-noticias`, que ainda não foi arquivado. Por isso as exceções para o conteúdo de exemplo ficam como requisitos novos da capacidade `conteudo-de-exemplo`.)

## Impact

- Código: comando novo em `src/Command/`, campo de marca de exemplo nas entidades `Article`, `Author` e `Category` (uma migration), filtro no `SitemapController`, bloco `robots` em `themes/noticias/article.html.twig`, selo nas listas do `/admin` (notícias, jornalistas e categorias), `TenantExporter` e `TenantImporter`.
- Banco: uma migration com uma coluna booleana em três tabelas, com padrão `false` (os registros que já existem continuam reais).
- Arquivos: as fotos de exemplo vão para `public/uploads/article` e `public/uploads/author`, como as do painel, e são apagadas com os registros.
- Rede: o comando baixa fotos de picsum.photos; sem acesso, gera imagens localmente e segue.
- Produção: depois do deploy, rodar o comando para `ajope.wab.com.br` (escrita no banco e em `public/uploads`, com aprovação do Jonas e backup antes).
- `app:generate-dummy-data` e `PortalNoticiasFixtures` continuam como estão.
