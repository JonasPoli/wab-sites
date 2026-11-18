# Spec Delta

## ADDED Requirements

### Requirement: Blog em site institucional
Um site com o interruptor "Blog" ligado e tema diferente de `noticias` SHALL servir as notícias cadastradas no `/admin` como blog em `/blog`, `/blog/categoria/{slug}` e `/blog/{slug}`; com o interruptor desligado, ou no tema `noticias`, esses três endereços respondem 404.

#### Scenario: Blog desligado
- **WHEN** um site com tema cetec e o blog desligado recebe `/blog`
- **THEN** a resposta é 404

#### Scenario: Blog ligado
- **WHEN** o SuperAdmin liga o blog de um site com tema cetec que tem 3 notícias publicadas
- **THEN** `/blog` responde 200 com as 3, da mais recente para a mais antiga

#### Scenario: Portal de notícias com o interruptor ligado
- **WHEN** um site com tema `noticias` tem o interruptor do blog ligado
- **THEN** `/blog` responde 404 e `/noticias` continua mostrando as notícias

#### Scenario: Endereços antigos não mudam
- **WHEN** um site com tema cetec e blog ligado recebe `/noticias/{slug}` de um post publicado
- **THEN** a resposta é 404, como antes desta mudança

### Requirement: Lista do blog
A lista do blog SHALL mostrar 9 posts visíveis por página (publicados, com data no passado), cada um com capa, categoria, data, título e resumo, as categorias que têm post visível como atalhos de filtro, e paginação por `?pagina=N`; página fora do intervalo responde 404.

#### Scenario: Segunda página
- **WHEN** o site tem 20 posts visíveis e o visitante abre `/blog?pagina=2`
- **THEN** aparecem os posts 10 a 18 e os links para as páginas 1 e 3

#### Scenario: Rascunho e post agendado
- **WHEN** o site tem um post em rascunho e um publicado com data no futuro
- **THEN** nenhum dos dois aparece na lista nem abre pelo endereço

#### Scenario: Categoria do blog
- **WHEN** o visitante abre `/blog/categoria/tendencias`
- **THEN** aparecem só os posts da categoria "Tendências" e das subcategorias dela

#### Scenario: Blog sem posts
- **WHEN** o blog está ligado e não há post visível
- **THEN** `/blog` responde 200 com o aviso de que ainda não há publicações

### Requirement: Post do blog
A página do post SHALL mostrar categoria, título, data, capa, texto, o vídeo do YouTube quando houver, o link para o post anterior no tempo e a volta para a lista; o nome do autor não é mostrado. A visualização é contada com a mesma regra da notícia do portal.

#### Scenario: Post de outro site
- **WHEN** o site A recebe `/blog/{slug}` de um post que pertence ao site B
- **THEN** a resposta é 404

#### Scenario: Post sem capa
- **WHEN** o post não tem imagem de capa
- **THEN** a página responde 200 sem a área da capa

### Requirement: Blog nos temas
O blog SHALL funcionar com o cabeçalho e o rodapé do tema do site em todos os temas institucionais (cetec, moderno, wab e os que vierem); um tema pode ter desenho próprio para a lista e o post, e os que não têm usam o desenho comum.

#### Scenario: Tema sem desenho próprio
- **WHEN** um site com tema moderno e blog ligado recebe `/blog`
- **THEN** a lista aparece entre o cabeçalho e o rodapé do tema moderno

### Requirement: Cadastro do blog no painel
Com o blog ligado, o menu do `/admin` do site SHALL mostrar o item "Blog", que abre o mesmo cadastro de notícias do portal, e o formulário SHALL informar o endereço do post como `/blog/<slug>`; os itens de jornalistas não aparecem.

#### Scenario: Site sem blog
- **WHEN** um site com tema cetec e blog desligado abre o `/admin`
- **THEN** o menu não mostra "Blog" nem "Notícias"

### Requirement: SEO do blog
O post do blog SHALL sair com endereço canônico em `/blog/<slug>`, dados estruturados do tipo `BlogPosting` e trilha Início → Blog → categoria → post; a lista SHALL sair como `CollectionPage` com a lista de posts, mantendo `?pagina=N` no canônico. Post de exemplo sai com `noindex`.

#### Scenario: Canônico do post
- **WHEN** um robô abre `/blog/moveis-de-madeira-clara?utm_source=x`
- **THEN** o canônico é `https://<domínio>/blog/moveis-de-madeira-clara`

### Requirement: Blog no sitemap
Com o blog ligado, o `/sitemap.xml` SHALL trazer `/blog` quando há post visível, uma URL `/blog/<slug>` por post visível que não é de exemplo e uma URL `/blog/categoria/<slug>` por categoria com post visível; com o blog desligado, nenhuma dessas URLs aparece.

#### Scenario: Site institucional sem blog
- **WHEN** um site com tema cetec e blog desligado recebe `/sitemap.xml`
- **THEN** o XML é o mesmo de antes desta mudança
