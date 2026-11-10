# Proposta: modo-escuro-no-tema-noticias

## Why

O tema `noticias`, usado pelo portal da AJoPE em https://ajope.wab.com.br, só tem modo claro: o `<html>` sai sempre com `data-theme="light"` e as cores ficam fixas nos templates (`#fff`, `#333`, `#111`, `#e6e6e6`...). Os temas `cetec` e `moderno` já deixam o visitante escolher entre claro, escuro e automático, e o site já tem as cores do modo escuro cadastradas no `/admin/settings` (cor primária e secundária do modo escuro, dois fundos escuros e o logo escuro). O Jonas quer a mesma sistemática no portal de notícias, para aplicar na AJoPE.

## What Changes

- Seletor de aparência no cabeçalho do tema `noticias` (no canto direito, hoje vazio), com três modos: Claro, Escuro e Automático (segue o sistema). O padrão é Claro. A escolha fica no navegador do visitante, com a mesma chave dos outros temas, e é aplicada antes de a página aparecer, sem piscar.
- Cores do tema em variáveis (fundo da página, cartões, texto, texto secundário, linhas, campos de formulário), com valores para os dois modos. As cores fixas de todas as páginas do tema (home, listas, notícia, editoria, autor, cadastro de jornalista e páginas de membro) passam a usar essas variáveis.
- No modo escuro, os fundos vêm das cores escuras do site, e títulos e links usam a cor primária do modo escuro. Quando essa cor não tem contraste suficiente com o fundo escuro, o tema usa texto claro no lugar dela.
- Cabeçalho e rodapé continuam na cor primária do site nos dois modos (identidade da marca), e a regra de qual logo aparece não muda.
- Os blocos do cetec nas páginas institucionais do portal acompanham o modo escolhido.
- No modo claro, o portal fica igual ao de hoje.
- O README deixa de dizer que o tema `noticias` só tem modo claro.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `navegacao-e-aparencia`: novos requisitos de modo claro e escuro, de seletor de aparência e das cores do modo escuro no tema `noticias`, e mudança no requisito "Logos claro e escuro", que hoje diz que o tema `noticias` não tem modo escuro (texto do change `portal-de-noticias`, que precisa ser arquivado antes deste).

## Impact

- Templates do tema `noticias`: `base.html.twig`, `_header.html.twig`, `_news_styles.html.twig`, `journalist_signup.html.twig`, `team_member_classic.html.twig` e `team_member_modern.html.twig`. Os temas `cetec`, `moderno` e `wab` não mudam.
- `App\Twig\TenantExtension`: função nova de contraste entre duas cores, usada pelo tema para escolher a cor dos títulos no modo escuro.
- Sem migration e sem mudança no painel: as cores do modo escuro já existem no `/admin/settings`.
- Sites afetados: só os de tema `noticias`. Em produção, hoje, é só o ajope.wab.com.br; os 8 sites de cliente usam o cetec.
- Produção: deploy com `git pull` e `./build.sh`. Ajustar as cores escuras da AJoPE no `/admin/settings` é opcional e fica a critério do Jonas.
