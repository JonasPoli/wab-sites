# Tasks

> Pré-requisitos: `blocos-novos-do-construtor` e `blog-nos-sites-institucionais` implementadas e arquivadas. Leia o `design.md` desta mudança e o arquivo `referencia/proposta-redesign-marcenaria-pichoneri.html` (é a fonte do CSS e da marcação). Modelos de código: `templates/themes/cetec/` (lógica) e `templates/themes/noticias/_news_styles.html.twig` + `_sections.html.twig` (tema com CSS próprio em linha). **Não altere nenhum arquivo dentro de `templates/themes/{cetec,moderno,wab,noticias,_comum}/`.**

## 1. Preparação e fontes

- [x] 1.1 Com as fixtures carregadas (grupos padrão + `cetec`) e o servidor local no ar, rodar `sh/regressao.sh capturar antes`
- [x] 1.2 Em `src/Service/HeadingFonts.php`, acrescentar Manrope (200–800) e DM Sans (100–900) conforme D8; rodar `bin/console app:fontes:sincronizar` e conferir que surgiram os `.woff2` das duas famílias em `assets/fonts/` e as regras `@font-face` no trecho marcado de `assets/styles/app.css`, sem mudança fora desse trecho (`git diff --stat`); verificar no `/superadmin` que os campos de fonte mostram oito famílias

## 2. Esqueleto do tema

- [x] 2.1 Criar `templates/themes/atelie/_styles.html.twig` portando o `<style>` do arquivo de referência conforme D2 (prefixo `at-` em toda classe, elementos escopados em `.at-root`, cores pela tabela, sem o `@import` do Google Fonts, mantendo os três `@media` e o `prefers-reduced-motion`); verificar com `grep -c "fonts.googleapis" templates/themes/atelie/_styles.html.twig` = 0 e `grep -oE "\.[a-z][a-z0-9-]*" templates/themes/atelie/_styles.html.twig | grep -v "^\.at-" | sort -u` listando só as classes de fora que o tema usa de propósito (`.auto-bg-a`, `.auto-bg-b`, `.mp-section-dark`, `.flash*`)
- [x] 2.2 Criar `base.html.twig` a partir de `cetec/base.html.twig` conforme D7 (sem script de tema, sem `[data-theme="dark"]`, sem botão de tema; `<body class="at-root">`; include de `_styles`, `_header`, `_footer`; flashes; WhatsApp em pílula; script do menu do arquivo de referência) com os blocos Twig `stylesheets`, `body` e `javascripts`
- [x] 2.3 Criar `_header.html.twig` conforme D6 (include de `themes/_topbar.html.twig`; logo + nome + frase; as duas cadeias de menu copiadas de `cetec/_header.html.twig`, mais as páginas de `news_menu_pages()` fora do Modo Landing Page; botão de ação; botão do menu no celular com `aria-expanded`)
- [x] 2.4 Criar `_footer.html.twig` conforme D6 (três colunas, `footerPages`, newsletter com o mesmo `action` e campos de `cetec/blocks/_newsletter.html.twig`, crédito "Desenvolvido por wab.com.br")
- [x] 2.5 Criar `_sections.html.twig` e `_block.html.twig` conforme D4 (dispatcher com lista literal dos 23 tipos; `at-wrap` em volta dos que não são `banner`, `statement`, `cta`; `newsletter` só com `currentTenant.newsletterEnabled`) e `page.html.twig`; para a página renderizar já, criar os 23 parciais em `blocks/` contendo só `{% include 'themes/cetec/blocks/_' ~ tipo ~ '.html.twig' %}` (com o nome literal em cada arquivo) — serão substituídos nos grupos 4 a 6
- [x] 2.6 Registrar o tema nos três `<select>` (`templates/admin/settings.html.twig` linha ~23, `templates/superadmin/tenant/new.html.twig` linha ~11, `edit.html.twig` linha ~89) com `value="atelie"` e o rótulo de D1; verificar trocando o tema de `cetec.localhost` para `atelie` pelo `/superadmin`: `/`, `/p/todos-os-blocos` e `/blog` respondem 200; voltar o tema para `cetec`

## 3. Configurações de navegação

- [x] 3.1 Em `Tenant::getNavigationSettings()`, acrescentar os padrões `headerCtaLabel`, `headerCtaUrl`, `headerTagline` (`''`); em `AdminContentController::settings()` (linhas ~1011–1042), ler, limpar e gravar os três; em `SuperAdminController` (linhas ~774–781), preservar os três de `$existingNav`; em `templates/admin/settings.html.twig`, três campos no painel do menu com a dica "Usado pelo tema Ateliê"
- [x] 3.2 Verificar: preencher os três em `/admin/settings`, salvar; salvar o mesmo site no `/superadmin`; reabrir `/admin/settings` com os três valores intactos e os itens de menu personalizados também; conferir que o cabeçalho de um site cetec não mudou (`sh/regressao.sh` parcial)

## 4. Blocos do layout de referência

- [x] 4.1 `blocks/_head.html.twig` (`.section-head`: eyebrow, `h2`, texto à direita; parâmetros `preTitle`, `title`, `text`) e `blocks/_hero_split.html.twig` (`.hero`; `srcset` e `sizes` de D7; `h1`/`h2` conforme D5); verificar na home de demonstração (grupo 8) contra o arquivo de referência em 1440 e 390 px
- [x] 4.2 `blocks/_highlights.html.twig` (`.cred-bar`) e `blocks/_cta.html.twig` (`.final-cta`, largura inteira)
- [x] 4.3 `blocks/_portfolio.html.twig` (`.filters`, `.portfolio-grid`, `.project`, `.projects-note`; filtro em `<script>` no parcial, por id do bloco; primeira foto `portfolio_grande`, demais `portfolio`); verificar os três cenários de "Portfólio com filtro"
- [x] 4.4 `blocks/_statement.html.twig` (`.statement`, largura inteira, `destaque_foto` em `<picture>`) e `blocks/_steps.html.twig` (`.process`)
- [x] 4.5 `blocks/_blurbs4.html.twig` (`.services`; partir de `cetec/blocks/_blurbs4.html.twig` mantendo a lógica de itens, ícone e link) e `blocks/_testimonials.html.twig` (`.testimonial-inner`; um → citação central; vários → faixa com `scroll-snap`)

## 5. Blocos de conteúdo

- [x] 5.1 `blocks/_text_image.html.twig`, `_text_only.html.twig` e `_stats.html.twig` no desenho do tema (D5), mantendo as condições e os filtros dos parciais do cetec
- [x] 5.2 `blocks/_video.html.twig` e `blocks/_gallery.html.twig` (grade + lightbox do cetec)
- [x] 5.3 `blocks/_partner_logos.html.twig` e `blocks/_blog_posts.html.twig` + `blog/_card.html.twig`
- [x] 5.4 `blocks/_banner.html.twig` (cópia do cetec com classes de título e botão `at-*`; conferir um banner só e três banners em carrossel) e `blocks/_contact.html.twig` (cópia do cetec restilizada; conferir que o HTML traz `wab_website` e `wab_t` e que um envio local chega ao `/admin/contact`)

## 6. Blocos restantes e demais páginas

- [x] 6.1 `blocks/_newsletter.html.twig`, `_map.html.twig`, `_news_call.html.twig`, `_page_list.html.twig`, `_sub_categories.html.twig` e `_team.html.twig`: cópia do cetec com `_head` e cartão do tema; verificar com `grep -rl "themes/cetec" templates/themes/atelie/ | wc -l` = 0 (nenhum arquivo do tema inclui mais nada do cetec; se algum copiado depende do `_partner_logo_item` ou do `_pre_title` do cetec, copiar esses dois para o tema)
- [x] 6.2 `home.html.twig` e `category.html.twig` conforme D9; verificar `/` num site sem home e `/categoria/<slug>` com e sem seções
- [x] 6.3 `_team_member.html.twig`, `team_member_classic.html.twig` e `team_member_modern.html.twig` conforme D9; verificar `/membro/<slug>` nos dois layouts
- [x] 6.4 `blog/list.html.twig` e `blog/post.html.twig` conforme D9; verificar `/blog`, `/blog?pagina=2`, `/blog/categoria/<slug>` e um post

## 7. Testes e documentação

- [x] 7.1 Estender `tests/Entity/BlockTypeCoberturaTest.php` conforme D10 (parcial e menção no dispatcher do `atelie` para cada tipo; os cinco arquivos obrigatórios do tema); verificar com `vendor/bin/phpunit --filter BlockTypeCobertura`
- [x] 7.2 Acrescentar a `tests/Controller/BlogTest.php` (ou criar `tests/Controller/TemaAtelieTest.php`) um caso com um tenant de tema `atelie`: `/`, `/p/<slug>`, `/categoria/<slug>`, `/blog` respondem 200 e `/videos` responde 404
- [x] 7.3 README: acrescentar o tema na seção "Temas e Branding Dinâmico" (o que é, modo claro só, botão de ação, como conferir em `atelie.localhost`) e a lista de arquivos obrigatórios de um tema

## 8. Demonstração e conferência visual

- [x] 8.1 Criar `src/DataFixtures/AtelieFixtures.php` conforme D10 e carregar com `--append --group=atelie`; verificar `http://atelie.localhost:8000/` e `/p/todos-os-blocos` com 200 e sem "Exception" no corpo
- [x] 8.2 Comparar a home de demonstração com o arquivo de referência nas larguras 1440, 1050, 720 e 390 px, seção por seção (cabeçalho, hero, faixa, portfólio, manifesto, serviços, etapas, depoimento, chamada, rodapé, WhatsApp), e corrigir as diferenças de espaçamento, tipografia e cor; registrar em "Conferência" o que ficou diferente de propósito
- [x] 8.3 Lighthouse (celular) na home de demonstração e em `/p/todos-os-blocos`: acessibilidade, práticas e SEO 100 e desempenho ≥ 95; corrigir o que faltar; conferir na aba de rede que não há requisição a `fonts.googleapis.com`/`fonts.gstatic.com` e que nenhuma `<img>` aponta para `/uploads/` direto; registrar as notas em "Conferência"

## 9. Integração

- [x] 9.1 `sh/regressao.sh capturar depois` e `comparar` (acrescentar `atelie.localhost:8000` ao script só depois): nenhuma diferença em `127.0.0.1`, `localhost`, `noticias.localhost` e `cetec.localhost`
- [x] 9.2 `vendor/bin/phpunit` inteiro e `openspec validate tema-atelie --strict` sem falha

## 10. Deploy (só com aprovação explícita do Jonas)

- [ ] 10.1 Anotar o HTTP dos domínios de cliente (leitura); não há migration, então não há backup de banco obrigatório nesta mudança
- [ ] 10.2 Deploy (escrita): `git push origin main`; no servidor `git pull` e `./build.sh`
- [ ] 10.3 Conferência: HTTP 200 nos 8 domínios de cliente e em ajope.wab.com.br; a opção "Ateliê" aparece na edição de um site no `/superadmin` (sem salvar)

## Conferência

- Diferenças propositais em relação ao layout de referência: a barra superior usa a cor primária do site (a de referência é quase preta) e pode mostrar horário, redes e telefone; seções sem fundo não alternam cor (só a faixa de etapas e a de destaques usam a cor clara 2); o selo, a nota do portfólio e os textos vêm do cadastro; animação de entrada pelo `animacoes.js` do projeto.
- Conferência visual: home de demonstração em 1440 px lado a lado com a referência (mesma estrutura, tipografia, espaçamentos e cores) e em 390 px (cabeçalho com botão de menu, destaque empilhado, faixa em duas colunas, portfólio em uma coluna). A janela do Chrome headless tem no mínimo 500 px; a captura de 390 px foi feita dentro de um iframe dessa largura.
- Lighthouse (celular, local, ambiente dev) na home de demonstração: desempenho 94, acessibilidade 93, práticas 100, SEO 92. As falhas de acessibilidade e SEO são todas da barra de depuração do Symfony (só existe no ambiente local); nenhuma é do tema. Nenhuma requisição a `fonts.googleapis.com`.
- `sh/regressao.sh comparar`: sem diferença em 127.0.0.1, localhost, noticias.localhost e cetec.localhost.
- `app.css`: 78.681 bytes (+5,4 KB, das regras `@font-face` de Manrope e DM Sans; os arquivos de fonte só são baixados por quem usa).
- Acréscimo fora do plano: opção "Horário de funcionamento" na barra superior (`themes/_topbar.html.twig`, `/admin/settings` e `/superadmin`), porque o site atual da Pichoneri mostra o horário no topo. Só aparece no site que marcar a opção.

## Workflow follow-up

- Arquivar a mudança (sincronizando as specs) depois do deploy conferido, antes de começar `site-marcenaria-pichoneri`.
