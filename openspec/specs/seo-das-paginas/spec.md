# seo-das-paginas Specification

## Purpose
Define o que toda página pública dos sites manda para buscadores e redes sociais: título, descrição, endereço canônico, cartão de compartilhamento, dados estruturados do schema.org, trilha de navegação e as regras de rastreamento por domínio, inclusive o SEO próprio da notícia, da editoria, da listagem e do autor no portal de notícias.

## Requirements

### Requirement: Descrição em toda página pública
Toda página pública SHALL sair com uma `<meta name="description">` não vazia, de 50 a 160 caracteres, sem HTML e sem quebra de linha. O conteúdo SHALL vir da primeira origem preenchida nesta ordem: campo de SEO da própria página, campo de SEO do site, primeiro texto do conteúdo da página e, por último, o nome do site.

#### Scenario: Home sem descrição cadastrada
- **WHEN** um visitante abre `/` de um site cujo tenant e cuja página home estão sem descrição de SEO
- **THEN** o HTML traz uma `<meta name="description">` montada a partir do texto da página, ou do nome do site quando não há texto, e ela não está vazia

#### Scenario: Página com descrição própria
- **WHEN** a página tem descrição de SEO cadastrada e o site também tem a dele
- **THEN** a descrição da página é a usada

#### Scenario: Texto longo como origem
- **WHEN** a descrição vem do texto do conteúdo e esse texto passa de 160 caracteres
- **THEN** a descrição é cortada em até 160 caracteres, sem cortar palavra ao meio e sem tags HTML

### Requirement: Título único por página
Toda página pública SHALL ter um `<title>` que identifique a página e o site, com no máximo 65 caracteres. Páginas diferentes do mesmo site MUST NOT ter o mesmo título.

#### Scenario: Página interna
- **WHEN** o visitante abre uma página interna de um site
- **THEN** o título traz o nome da página e o nome do site

#### Scenario: Home
- **WHEN** o visitante abre `/`
- **THEN** o título traz o nome do site e o complemento de SEO do site, quando houver

### Requirement: Endereço canônico
Toda página pública SHALL trazer `<link rel="canonical">` com a URL absoluta da própria página no domínio aberto, sem parâmetros de consulta e sem barra final a mais. A página que serve de home SHALL apontar o canônico para `/`, nunca para `/p/<slug>`.

#### Scenario: Página aberta com parâmetro de campanha
- **WHEN** o visitante abre uma página com `?utm_source=...`
- **THEN** o canônico aponta para a URL sem o parâmetro

#### Scenario: Página usada como home
- **WHEN** a página definida como home do site é aberta por `/p/<slug>`
- **THEN** o canônico aponta para `https://<domínio>/`

### Requirement: Cartão de compartilhamento
Toda página pública SHALL trazer `og:site_name`, `og:type`, `og:title`, `og:description`, `og:url`, `og:locale` e, quando houver imagem, `og:image` com URL absoluta mais `og:image:width` e `og:image:height`; e os equivalentes do Twitter (`twitter:card`, `twitter:title`, `twitter:description` e `twitter:image`). A imagem SHALL vir da primeira origem disponível: imagem da página, imagem de compartilhamento do site, logo do site.

#### Scenario: Página com capa
- **WHEN** a página tem imagem de capa
- **THEN** `og:image` traz a URL absoluta dessa imagem, com largura e altura

#### Scenario: Site sem nenhuma imagem
- **WHEN** a página não tem capa e o site não tem imagem de compartilhamento nem logo
- **THEN** as tags `og:image` e `twitter:image` não são geradas e as demais continuam presentes

### Requirement: Dados estruturados por tipo de página
Toda página pública SHALL trazer um bloco `application/ld+json` válido com o tipo do schema.org correspondente: `Organization` e `WebSite` na home, `WebPage` nas demais, e `BreadcrumbList` em toda página que não é a home. O bloco MUST NOT descrever conteúdo que não está na página.

#### Scenario: Home de um site de cliente
- **WHEN** o visitante abre `/`
- **THEN** o JSON-LD traz `Organization` com nome, URL, logo e os dados de contato cadastrados, e `WebSite` com a URL do site

#### Scenario: Página interna dentro de uma categoria
- **WHEN** o visitante abre uma página que pertence a uma categoria
- **THEN** o JSON-LD traz `BreadcrumbList` com Início, a categoria e a página, nessa ordem e com as URLs de cada uma

#### Scenario: Conteúdo sem imagem nem autor
- **WHEN** a página não tem imagem nem autor cadastrado
- **THEN** o JSON-LD sai sem as propriedades `image` e `author`, em vez de sair com valor vazio

### Requirement: Dados estruturados da notícia
No tema `noticias`, a página da notícia SHALL trazer JSON-LD do tipo `NewsArticle` com título, descrição, URL canônica, data de publicação e de alteração em ISO 8601 com fuso, editoria em `articleSection`, editor em `publisher` e, quando houver, a imagem da capa e o autor como `Person` com a URL da página dele.

#### Scenario: Notícia com autor e capa
- **WHEN** o visitante abre `/noticias/{slug}` de uma notícia com autor aprovado e capa
- **THEN** o `NewsArticle` traz `author` do tipo `Person` apontando para `/autor/{slug}`, `image` com a capa e `datePublished` igual à data mostrada na página

#### Scenario: Notícia de exemplo
- **WHEN** a notícia está marcada como exemplo e sai com `noindex`
- **THEN** a página não traz bloco `NewsArticle`

### Requirement: Dados estruturados da editoria, da listagem e do autor
No tema `noticias`, `/categoria/{slug}` e `/noticias` SHALL trazer `CollectionPage` com um `ItemList` das notícias listadas na página, na ordem em que aparecem; `/autor/{slug}` SHALL trazer `ProfilePage` com o `Person` do autor e o `ItemList` das notícias dele.

#### Scenario: Página 2 da listagem
- **WHEN** o visitante abre `/noticias?page=2`
- **THEN** o `ItemList` lista as notícias daquela página, com a posição contando a partir da primeira notícia da página 2

#### Scenario: Autor sem notícia publicada
- **WHEN** o visitante abre a página de um autor aprovado que ainda não publicou
- **THEN** a página traz `ProfilePage` com o `Person` e um `ItemList` vazio

### Requirement: Trilha de navegação visível
Toda página pública que não é a home SHALL mostrar na tela uma trilha de navegação com Início, os níveis intermediários e a página atual, com links em todos os níveis menos o atual, e essa trilha SHALL corresponder ao `BreadcrumbList` do JSON-LD.

#### Scenario: Notícia de uma editoria
- **WHEN** o visitante abre uma notícia
- **THEN** a trilha mostra Início › editoria › título da notícia, com links em Início e na editoria

#### Scenario: Leitor de tela
- **WHEN** a trilha é lida por um leitor de tela
- **THEN** ela é anunciada como navegação com rótulo próprio e o item atual é identificado

### Requirement: Regras de rastreamento por domínio
O sistema SHALL responder `/robots.txt` por domínio, com a linha `Sitemap:` apontando para `https://<domínio aberto>/sitemap.xml` e com `Disallow` para `/admin`, `/superadmin`, `/jornalista`, `/login` e `/busca` em todos os grupos. O sistema SHALL ter um grupo para robôs de busca e assistentes de IA, com o resto do site liberado; um grupo para robôs de treino de modelos, com o site inteiro proibido; e `Content-Signal: search=yes, ai-input=yes, ai-train=no` nos grupos que podem ler o site.

#### Scenario: Dois domínios diferentes
- **WHEN** o `/robots.txt` é aberto em dois tenants diferentes
- **THEN** cada resposta traz o `Sitemap:` do seu próprio domínio

#### Scenario: Robôs de IA
- **WHEN** o `/robots.txt` de um tenant é lido
- **THEN** OAI-SearchBot, ChatGPT-User, Claude-SearchBot, Claude-User e PerplexityBot podem ler o site
- **AND** GPTBot, ClaudeBot, Google-Extended, CCBot e Bytespider estão proibidos em `/`

#### Scenario: Domínio que não é tenant
- **WHEN** o `/robots.txt` é aberto num domínio que aponta para o servidor sem ser tenant
- **THEN** a resposta proíbe o rastreamento do site inteiro

### Requirement: Páginas que não devem ser indexadas
O sistema SHALL mandar `noindex` nas páginas sem valor de busca — resultado de busca, cadastro e área do jornalista, login e painéis — e SHALL manter `index, follow` no conteúdo público.

#### Scenario: Página de busca
- **WHEN** o visitante abre `/busca?q=teste`
- **THEN** a página sai com `noindex` e fica fora do `/sitemap.xml`

#### Scenario: Página institucional
- **WHEN** o visitante abre uma página institucional publicada
- **THEN** a página sai com `index, follow`

### Requirement: Nota de SEO do PageSpeed
As páginas públicas dos 9 tenants em produção SHALL alcançar 100 na categoria SEO do PageSpeed Insights, no celular e no computador, sem nenhuma auditoria reprovada.

#### Scenario: Home de cada site
- **WHEN** a home de cada um dos 9 domínios é medida no PageSpeed Insights
- **THEN** a categoria SEO marca 100 nos dois formatos

#### Scenario: Página de notícia
- **WHEN** uma notícia do ajope é medida
- **THEN** a categoria SEO marca 100 e a auditoria `meta-description` passa
