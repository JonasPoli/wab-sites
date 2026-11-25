# Proposal

## Why

Sites de clientes da WAB costumam ter, acima do menu, uma faixa fina com telefone, WhatsApp, e-mail, cidade com link para o mapa, redes sociais e um ou outro link próprio (por exemplo "Portal do Aluno"), que some quando a página rola (como em kayoacorp.com.br e cetecararaquara.com.br). O sistema já tem uma "TopBar", mas ela só aceita cinco itens fixos marcados em caixas, sem ordem, sem texto próprio, sem ícone nem link livres, não existe no tema noticias e, no tema moderno, deixa um vão de 40 px em cima do menu quando a página rola.

## What Changes

- A "Barra superior" passa a ter, em cada lado (esquerdo e direito), uma **lista ordenada de itens** editada num pequeno editor: acrescentar, remover, subir, descer e passar de um lado para o outro.
- Tipos de item:
  - **Telefone**, **WhatsApp**, **E-mail**, **Endereço**, **Horário** e **Redes sociais**, com os dados já cadastrados no site; nos quatro primeiros, um texto opcional substitui o texto mostrado (por exemplo "Araraquara" no lugar do endereço completo). O endereço passa a abrir o Google Maps; o WhatsApp mostra o número formatado.
  - **Personalizado**: ícone (mesmo seletor de ícones do Blurbs4), texto e link, cada um opcional (precisa ao menos de ícone ou texto), com opção de abrir em nova aba.
- Cor de fundo opcional da barra; sem ela, continua a cor primária do site (a escura no modo escuro). O texto fica claro ou escuro conforme o fundo.
- A barra **não fica fixa**: sai da tela quando a página rola, e o menu do site continua com o comportamento de cada tema. No moderno, o menu sobe para o topo quando a barra sai (fim do vão).
- Vale nos **cinco temas** (cetec, moderno, wab, noticias e atelie); o noticias passa a desenhar a barra.
- No celular, itens com ícone mostram só o ícone; a barra nunca causa rolagem lateral.
- O editor fica no `/admin/settings` do site e na edição do tenant no `/superadmin`, no lugar das caixas atuais.
- **Compatível**: a configuração antiga (lista de nomes como `"phone"`) continua sendo lida e desenhada como antes; o site da Pichoneri, o único com a barra ligada em produção, não muda.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `navegacao-e-aparencia`: requisitos novos da barra superior (itens por lado, tipos, personalizado, cor, rolagem, celular, cinco temas, configuração antiga) e da edição no `/admin/settings`.
- `gestao-de-sites`: a edição do tenant no `/superadmin` usa o mesmo editor e o `app:icones:sincronizar` passa a ler os ícones da barra.

## Impact

- Banco: nenhuma coluna nova; tudo fica em `tenant.navigation_settings` (`topBarEnabled`, `topBarLeft`, `topBarRight` no formato novo e `topBarBg`). Exportação e importação já levam o `navigation_settings` inteiro.
- Código: serviço novo de normalização e resolução dos itens e função Twig; `AdminContentController::settings()`, `SuperAdminController::populateTenantFromRequest()`, `SincronizarIconesCommand`, `TenantExtension` (cores da barra).
- Templates: `themes/_topbar.html.twig` reescrito; editor novo `admin/_editor_da_barra_superior.html.twig` em `admin/settings.html.twig` e `superadmin/tenant/edit.html.twig`; `themes/noticias/_header.html.twig` passa a incluir a barra; `themes/moderno/_header.html.twig` acompanha a rolagem.
- Produção: `git pull`, `./build.sh`, sem migration (escrita, pedida pelo Jonas: "publique").
