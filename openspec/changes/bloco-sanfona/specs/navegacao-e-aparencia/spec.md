# Spec Delta

## MODIFIED Requirements

### Requirement: Menu do Modo Landing Page
Com `landingPageMode` ligado e `homePage` definida, o cabeçalho SHALL listar as seções ativas da página home com `showInMenu`, com link `#section-{id}` (precedido do caminho da home fora dela), e rótulo `titlePart1` + `titlePart2`; sem título, o rótulo vem do primeiro bloco (banner e hero_split: Início; blurbs4: Recursos; team: Equipe; contact e cta: Contato; gallery e portfolio: Portfólio; statement: Sobre; steps: Como trabalhamos; highlights: Diferenciais; video: Vídeo; blog_posts: Blog; accordion: Informações) ou é "Seção N".

#### Scenario: Seção de contato sem título
- **WHEN** a terceira seção da home não tem título e o primeiro bloco é do tipo `contact`
- **THEN** o menu mostra "Contato" com link para `#section-<id da seção>`

#### Scenario: Landing page sem home
- **WHEN** `landingPageMode` está ligado mas o tenant não tem `homePage`
- **THEN** o cabeçalho usa o menu normal de categorias

#### Scenario: Seção de portfólio sem título
- **WHEN** uma seção da home não tem título e o primeiro bloco é do tipo `portfolio`
- **THEN** o menu mostra "Portfólio"

#### Scenario: Seção de sanfona sem título
- **WHEN** uma seção da home não tem título e o primeiro bloco é do tipo `accordion`
- **THEN** o menu mostra "Informações"
