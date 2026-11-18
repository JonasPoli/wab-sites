# Design

## Context

- Um tema é uma pasta em `templates/themes/<nome>/`. O `WabPublicController` renderiza `themes/<tema>/<arquivo>` direto, então **estes arquivos são obrigatórios** (sem eles a rota dá erro 500): `page.html.twig`, `home.html.twig`, `category.html.twig`, `team_member_classic.html.twig`, `team_member_modern.html.twig`. `articles.html.twig`, `videos.html.twig` e `studies.html.twig` são opcionais (sem eles a rota responde 404, que é o comportamento desejado).
- O nome do tema é texto livre em `Tenant.theme`; as opções aparecem em três `<select>`: `templates/admin/settings.html.twig` (linha ~20), `templates/superadmin/tenant/new.html.twig` (linha ~11) e `edit.html.twig` (linha ~86).
- Todo `base.html.twig` de tema carrega `styles/app.css` (Tailwind + `@font-face`), inclui `pub/_seo_head.html.twig`, chama `tenant_css_vars()`, e usa `imagem_do_lcp(_context)`, `usa_carrossel(_context)` e `tenant_fontes()`. O do cetec (`templates/themes/cetec/base.html.twig`) é o modelo.
- O tema `noticias` é o precedente de CSS próprio em linha: `templates/themes/noticias/_news_styles.html.twig`, com classes de prefixo `nt-`, e um `_sections.html.twig` que desenha as seções para página e categoria.
- `navigationSettings` (JSON do tenant) é regravado inteiro em dois lugares, com lista fixa de chaves: `AdminContentController::settings()` (linhas ~1011–1042) e `SuperAdminController` (linhas ~774–781, que preserva só `customMenuItems`). Chave nova que não entrar nesses dois pontos é apagada no primeiro salvamento.
- Fontes: `HeadingFonts::FAMILIES` é o catálogo; `app:fontes:sincronizar` baixa os `.woff2` para `assets/fonts` e reescreve o trecho de `@font-face` do `app.css`.
- O formulário de contato inclui `pub/_contato_protecao.html.twig` (isca, hora assinada, reCAPTCHA) e posta em `pub_contact_post`.

## Goals / Non-Goals

**Goals:**
- Reproduzir o HTML de referência com conteúdo vindo do banco.
- Tema completo: todo tipo de bloco, toda rota pública e o blog funcionam nele.
- Nota 100 no Lighthouse (as quatro categorias) na home de demonstração.

**Non-Goals:**
- Modo escuro e seletor de aparência.
- Mudar o painel além da opção de tema e dos três campos de navegação.
- Busca no blog, comentários, página de autor.
- Dois desenhos distintos para o perfil de membro (clássico e moderno usam o mesmo).

## Decisions

### D1. Nome e registro

Pasta `templates/themes/atelie/`, valor `atelie`, rótulo "Ateliê (editorial, tons de madeira)". Nome genérico de propósito: o tema não leva nada da Pichoneri (textos, fotos e cores vêm do tenant).

### D2. CSS próprio em linha, portado do HTML de referência

- `templates/themes/atelie/_styles.html.twig`: um `<style>` com o CSS do arquivo de referência, **com todo seletor de classe prefixado por `at-`** (`.wrap` → `.at-wrap`, `.btn-primary` → `.at-btn-primary`, `.hero-grid` → `.at-hero-grid`…) e os seletores de elemento (`body`, `a`, `h1…`, `p`, `img`, `button`) escopados em `.at-root` (classe do `<body>`), para não brigar com o preflight do Tailwind nem com os parciais do cetec que algum bloco venha a reaproveitar.
- Cores: trocar os literais da marca por variáveis do tenant, mantendo o literal como reserva:

| No HTML de referência | No tema |
|---|---|
| `--brand` (`#9b4509`) | `var(--color-primary)` |
| `--brand2` (`#c96a18`) e `#e7a15b` (sobre escuro) | `var(--color-secondary)` |
| texto/eyebrow na cor da marca | `var(--color-primary-text)` |
| texto sobre a cor da marca | `var(--color-on-primary)` |
| `--bg` (`#f7f5f0`) e `#ebe6dc` (faixa alternada) | classes `auto-bg-a` / `auto-bg-b` que o `tenant_css_vars()` já gera a partir de `bgColorLight1/2` |
| `--ink`, `--muted`, `--line`, `--dark`, `--paper` | ficam como variáveis `--at-*` fixas no tema |

- O `app.css` continua carregado (traz os `@font-face` e mantém funcionando qualquer classe Tailwind usada num parcial copiado do cetec). O JavaScript do menu, do filtro e da animação de entrada vai num `<script>` no fim do `base.html.twig` (o filtro mora no parcial do portfólio). A animação de entrada usa o `assets/animacoes.js` que já existe (`data-aos="fade-up"`), não o `IntersectionObserver` do HTML de referência.

*Alternativa descartada:* escrever o tema em utilitários Tailwind. Fiel ao layout exigiria dezenas de valores arbitrários (`text-[clamp(...)]`, `tracking-[-.047em]`), incharia o `app.css` de todos os sites e custaria mais para portar do que copiar o CSS pronto.

### D3. Estrutura de arquivos

```
templates/themes/atelie/
  base.html.twig            <head> no molde do cetec, sem o script e o botão de tema; <body class="at-root">
  _styles.html.twig         CSS (D2)
  _header.html.twig         barra superior (include de themes/_topbar.html.twig) + cabeçalho fixo
  _footer.html.twig
  _sections.html.twig       laço de seções (fundo, título, blocos), usado por page e category
  _block.html.twig          dispatcher: lista literal dos 23 tipos → blocks/_<tipo>.html.twig
  page.html.twig  home.html.twig  category.html.twig
  _team_member.html.twig    corpo do perfil; team_member_classic e team_member_modern só o incluem
  team_member_classic.html.twig  team_member_modern.html.twig
  blog/list.html.twig  blog/post.html.twig  blog/_card.html.twig
  blocks/_head.html.twig    cabeçalho de bloco (eyebrow + h2 + texto à direita) = `.section-head`
  blocks/_<tipo>.html.twig  um por tipo (23)
```

### D4. Seções e largura

- `_sections.html.twig` copia a lógica do `cetec/page.html.twig` (seção ativa, tipo de fundo, `is_dark_color`, alternância `auto-bg-a/b`, imagem e vídeo de fundo com os mesmos filtros de miniatura, `id="section-{id}"`), trocando só as classes: `at-section` (respiro de 110 px; 73 px no celular) e `mp-section-dark` nas escuras.
- Quem põe o conteúdo na largura de 1200 px é o **dispatcher**: tipos de largura total (`banner`, `statement`, `cta`) são incluídos direto; os demais vão dentro de `<div class="at-wrap">`. Uma seção cujos blocos são todos de largura total recebe `at-section--flush` (sem respiro vertical).
- Título da seção (`titlePart1` + `titlePart2`), quando ligado, sai como eyebrow centralizado acima dos blocos.
- Em `page.html.twig`, o título da página (`showTitle`) sai num cabeçalho simples (`at-page-head`: categoria como eyebrow + `h1`).

### D5. Blocos: de onde vem cada desenho

| Tipo | Origem do desenho no HTML de referência | Observação |
|---|---|---|
| `hero_split` | `.hero` / `.hero-grid` / `.hero-stamp` / `.hero-caption` | `h1` quando é o primeiro bloco e a página não mostra título |
| `highlights` | `.cred-bar` | |
| `portfolio` | `#projetos`: `.filters`, `.portfolio-grid`, `.project`, `.projects-note` | script do filtro do próprio HTML, por bloco |
| `statement` | `.statement` | |
| `blurbs4` | `.services` / `.service` | ícone do painel via `icone_do_painel` no lugar do símbolo; manter a lógica de link do parcial do cetec |
| `steps` | `.process` / `.process-grid` / `.step` | |
| `testimonials` | `.testimonial-inner` | um depoimento: citação grande central; vários: faixa horizontal com `scroll-snap` e um depoimento por vez, sem biblioteca |
| `cta` | `.final-cta` | |
| `blog_posts` | cartão no estilo `.project` (foto) + texto abaixo; usa `blog/_card.html.twig` | |
| `video` | sem equivalente: duas colunas no padrão `.section-head` + moldura reta | |
| `text_image`, `text_only` | tipografia de `.section-head` e `.statement-text` | |
| `stats` | números no estilo `.stat strong` em grade de 4 | |
| `gallery` | grade no estilo `.portfolio-grid`, com o lightbox do parcial do cetec | |
| `partner_logos` | grade de logos em tons neutros, 3 colunas no celular e 6 no desktop | |
| `banner` | copiar `cetec/blocks/_banner.html.twig` inteiro (Swiper, slides, adiamento de imagem) e trocar só as classes de título e botão por `at-*` | não reescrever a lógica |
| `contact` | copiar `cetec/blocks/_contact.html.twig` (inclui `_contato_protecao`, horários, mapa) e restilizar campos e botão com `at-*` | o POST e os nomes dos campos não mudam |
| `newsletter`, `map`, `news_call`, `page_list`, `sub_categories`, `team` | copiar o parcial do cetec, trocar o cabeçalho pelo `blocks/_head.html.twig` e os cartões por `at-card` (canto de 3 px, sem sombra, borda `--at-line`) | lógica intacta |

Regra para os copiados: **não mudar nomes de campo, rotas, filtros de miniatura nem condições**; só marcação e classes.

Isolamento por tenant: nenhum parcial do tema faz consulta própria além das funções Twig que já existem (`latest_pages`, `latest_articles`), que passam pelo filtro de tenant.

### D6. Cabeçalho, navegação e rodapé

- **Menu**: mesma regra do cetec (copiar as duas cadeias, desktop e celular, de `cetec/_header.html.twig`): Modo Landing Page com home → seções; senão "Início" + categorias com conteúdo (com submenu) ; depois os itens personalizados ativos. Acréscimo do tema: as páginas com `showInHeader` (função `news_menu_pages()`, que já existe) entram depois das categorias quando o site não está em Modo Landing Page.
- **Botão de ação**: `navigationSettings.headerCtaLabel` e `headerCtaUrl`; sem os dois, o botão não é desenhado. Link externo (`http…`) abre em nova aba com `rel="noopener noreferrer"`.
- **Frase abaixo do nome**: `navigationSettings.headerTagline` (até 60 caracteres); vazia, não aparece.
- **Gravação das três chaves**: em `Tenant::getNavigationSettings()` acrescentar os três padrões (`''`); em `AdminContentController::settings()` ler os três campos (`trim`, corte em 60/255; a URL passa pela mesma normalização dos itens personalizados) e incluí-los no array gravado; em `SuperAdminController`, preservar as três chaves de `$existingNav` como já faz com `customMenuItems`. Campos novos no `templates/admin/settings.html.twig`, no painel "Configurações do Menu Principal", com a dica "Usado pelo tema Ateliê".
- **Logo**: imagem do tenant (`logo_site`) + nome do site + frase; sem logo, só o nome.
- **Rodapé**: três colunas (nome + `aboutText` curto; endereço + horários de `openingHours`; telefone, e-mail e redes), linha de páginas do rodapé (`footerPages`), formulário da newsletter quando `newsletterEnabled` (mesmo POST do bloco), e a linha final "© ano nome · Desenvolvido por wab.com.br".
- **WhatsApp flutuante**: pílula "WhatsApp" quando `whatsappLink` existe.

### D7. `<head>` e desempenho

- Copiar do `cetec/base.html.twig`: pré-carga do LCP, `app.css`, `_seo_head`, favicon, `tenant_css_vars()`, pré-carga da primeira fonte de `tenant_fontes()`, Swiper condicional (`usa_carrossel`), `animacoes.js`, `imagens-adiadas.js`, GA4. Não copiar: script de tema claro/escuro, botão `wab-theme-btn`, tokens `[data-theme="dark"]`.
- `<html lang="pt-BR" data-theme="light">` fixo.
- Pré-carga do LCP: quando o primeiro bloco da primeira seção ativa é `hero_split`, `imagesizes="(min-width: 721px) 58vw, 100vw"`; nos demais casos `100vw`. A foto do `hero_split` no tema usa o mesmo `srcset` (`banner_mobile` 768w, `banner_desktop` 1600w) e o mesmo `sizes`, para a pré-carga e a imagem pedirem o mesmo arquivo.
- Fonte do texto: `.at-root { font-family: 'DM Sans', system-ui, sans-serif }`; títulos `'Manrope'` por padrão do tema, sem `!important`, de modo que o `fontSettings` do site (que sai com `!important`) continua mandando.
- Nenhum recurso de terceiro: o `@import` do Google Fonts do HTML de referência **não** é portado.

### D8. Fontes novas no catálogo

`HeadingFonts::FAMILIES` ganha `'Manrope' => ['Manrope (Geométrica, Editorial)', 'sans-serif', 200, 800]` e `'DM Sans' => ['DM Sans (Limpa, Texto)', 'sans-serif', 100, 900]`. Depois, `bin/console app:fontes:sincronizar` (baixa os `.woff2` e reescreve o trecho marcado do `app.css`); os arquivos vão para o git. Os temas atuais não mudam: só passam a oferecer duas famílias a mais no `/superadmin`.

### D9. Demais páginas

- `home.html.twig` (site sem página home): primeiro banner ativo (`banners`) desenhado como `hero_split` (título, subtítulo, botão, imagem com `banner_*`), depois `aboutText` do site quando houver e, com blog, as 3 últimas publicações. Sem banner, só um título com o nome do site.
- `category.html.twig`: cabeçalho da categoria, `_sections.html.twig` com as seções dela e a grade das páginas da categoria (mesma lógica de `cetec/category.html.twig`, cartões `at-card`).
- `_team_member.html.twig`: foto (`equipe`), nome, cargo, resumo, bio, experiência, áreas de atuação, seções personalizadas, contatos e redes; os campos e as condições vêm de `cetec/team_member_classic.html.twig`.
- `blog/list.html.twig` e `blog/post.html.twig`: mesmas variáveis dos compartilhados (`themes/_comum/blog/`), com `at-*`.

### D10. Demonstração e conferência

- `src/DataFixtures/AtelieFixtures.php`, grupo `atelie`, domínio `atelie.localhost`: tema `atelie`, cores `#9b4509` / `#c96a18`, fundos `#f7f5f0` / `#ebe6dc`, `fontSettings` h1–h5 em Manrope (pesos 600/600/600/700/700), barra superior ligada, botão de ação "Solicite um orçamento" → `#contato`, blog ligado com 10 posts. Página home `inicio` em Modo Landing Page com as seções do HTML de referência, na ordem e com os mesmos textos: `hero_split`, `highlights`, `portfolio` (5 fotos, 3 categorias), `statement`, `blurbs4`, `steps`, `testimonials` (1), `cta`. Página `todos-os-blocos` com os 23 tipos. Fotos pelo `PlaceholderImageFactory`.
- Conferência visual: abrir lado a lado o arquivo de referência e `http://atelie.localhost:8000/` nas larguras 1440, 1050, 720 e 390 px (os três pontos de quebra do CSS) e comparar seção por seção.
- `BlockTypeCoberturaTest` passa a exigir `templates/themes/atelie/blocks/_<tipo>.html.twig` e a menção do tipo em `atelie/_block.html.twig`, e ganha um caso que confere a existência dos cinco arquivos obrigatórios do tema.

## Risks / Trade-offs

- [O preflight do Tailwind zera margens e tamanhos de título e muda o que o CSS portado espera] → todo estilo de elemento fica escopado em `.at-root` e declarado explicitamente (tamanho, peso, margem de `h1`–`h3`, `p`, `a`, `img`, `button`); a conferência visual por largura pega o que escapar.
- [Parcial copiado do cetec fica com cara de cetec] → aceitável na primeira entrega para os tipos da última linha da tabela D5, desde que use o `_head` e o cartão do tema; a página `todos-os-blocos` é o lugar de conferir.
- [Chaves novas de navegação apagadas por um salvamento] → coberto em D6 nos dois controllers; a tarefa de verificação salva nos dois painéis e confere as chaves.
- [Cor da marca clara demais para texto] → o tema usa `--color-primary-text` e `--color-on-primary`, que o `tenant_css_vars()` já calcula com contraste de 4,6:1.
- [Site trocado para `atelie` com o visitante em modo escuro salvo no navegador] → o tema ignora `localStorage` e fixa `data-theme="light"`.

## Migration Plan

Sem migration. Deploy: `git pull` + `./build.sh` (templates, CSS e fontes novos). Nenhum site usa o tema depois do deploy. Rollback: commit anterior + `./build.sh`; um site que já esteja no tema `atelie` precisa voltar para `cetec` antes.
