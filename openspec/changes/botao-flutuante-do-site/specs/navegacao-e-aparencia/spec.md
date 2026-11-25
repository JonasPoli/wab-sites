## ADDED Requirements

### Requirement: Tipo do botão flutuante
Cada site SHALL ter um botão flutuante de um destes tipos: Nenhum, WhatsApp ou Formulário. Sem escolha gravada, vale WhatsApp. O botão SHALL aparecer em todas as páginas públicas dos temas cetec, moderno, wab, noticias e atelie, no canto inferior direito, e nunca na prévia do bloco no painel.

#### Scenario: Nenhum
- **WHEN** o site tem o tipo Nenhum e o link de WhatsApp preenchido
- **THEN** nenhuma página pública mostra botão flutuante

#### Scenario: Site sem escolha gravada
- **WHEN** um site do tema cetec nunca teve o tipo escolhido e tem link de WhatsApp
- **THEN** as páginas mostram o botão flutuante do WhatsApp, como antes da mudança

#### Scenario: Prévia do bloco
- **WHEN** o admin abre a prévia de um bloco no painel de um site com botão flutuante
- **THEN** a prévia não mostra o botão

### Requirement: Botão flutuante do WhatsApp
Com o tipo WhatsApp, o botão SHALL aparecer só quando o link de WhatsApp do site está preenchido, abrir esse link numa aba nova e ter um rótulo acessível que cite o WhatsApp. Nos temas cetec e atelie, o desenho SHALL continuar o de antes da mudança.

#### Scenario: Tema moderno com WhatsApp
- **WHEN** um site do tema moderno tem o tipo WhatsApp e o link `https://wa.me/5516999999999`
- **THEN** todas as páginas mostram o botão flutuante do WhatsApp apontando para esse link, em aba nova

#### Scenario: Sem link
- **WHEN** o site tem o tipo WhatsApp e o link de WhatsApp vazio
- **THEN** o botão flutuante não aparece

### Requirement: Botão flutuante de formulário
Com o tipo Formulário, o botão SHALL aparecer só quando a página de destino existe no próprio site e o texto do botão está preenchido. Ele SHALL mostrar o ícone escolhido (quando há) e o texto, e levar, na mesma aba, à página de destino, ou ao endereço da home quando ela é a home do site. O botão do WhatsApp não aparece nesse tipo.

#### Scenario: Formulário configurado
- **WHEN** o site tem o tipo Formulário, a página "Fale conosco" (slug `fale-conosco`) como destino, o texto "Solicite um orçamento" e o ícone `fa-solid fa-envelope`
- **THEN** as páginas mostram o botão com o ícone de envelope e o texto "Solicite um orçamento", com link para `/p/fale-conosco`, e nenhum botão de WhatsApp

#### Scenario: Página de destino excluída
- **WHEN** a página de destino do botão é excluída do site
- **THEN** o botão flutuante não aparece, e o site não dá erro

#### Scenario: Sem texto
- **WHEN** o site tem o tipo Formulário com página de destino mas sem texto do botão
- **THEN** o botão flutuante não aparece

### Requirement: Botão de formulário fora da página de destino
O botão flutuante de formulário SHALL ficar oculto na própria página de destino.

#### Scenario: Visitante já no formulário
- **WHEN** o visitante abre `/p/fale-conosco`, que é a página de destino do botão
- **THEN** essa página não mostra o botão flutuante; as outras páginas mostram

### Requirement: Cores do botão de formulário
O botão de formulário SHALL usar como fundo a cor primária do site no modo claro e a cor primária escura no modo escuro, com o texto e o ícone em branco sobre fundo que o sistema considera escuro e em quase preto sobre fundo claro.

#### Scenario: Cor primária clara
- **WHEN** a cor primária do site é `#ffcc00`
- **THEN** o botão de formulário tem fundo `#ffcc00` e texto escuro

#### Scenario: Modo escuro
- **WHEN** o visitante está no modo escuro de um site cetec com cor primária escura `#3b82f6`
- **THEN** o fundo do botão é `#3b82f6`

## MODIFIED Requirements

### Requirement: Rodapé do tema atelie
O rodapé do tema `atelie` SHALL mostrar o nome e o texto curto do site, o endereço e os horários de funcionamento, telefone, e-mail e redes sociais cadastrados, as páginas marcadas para o rodapé, o formulário da newsletter quando ela está ligada e o crédito da WAB; cada dado ausente é omitido sem deixar rótulo vazio. O botão flutuante do site SHALL seguir o tipo escolhido para ele (Nenhum, WhatsApp ou Formulário).

#### Scenario: Newsletter desligada
- **WHEN** o site tem `newsletterEnabled` falso
- **THEN** o rodapé não mostra o formulário de newsletter

#### Scenario: Sem WhatsApp
- **WHEN** o site tem o tipo WhatsApp e não tem link de WhatsApp
- **THEN** o botão flutuante não aparece
