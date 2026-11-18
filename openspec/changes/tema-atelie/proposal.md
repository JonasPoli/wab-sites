# Proposal

## Why

A Marcenaria Pichoneri aprovou um redesign (em `referencia/proposta-redesign-marcenaria-pichoneri.html`) de linguagem editorial: fundo cru, tipografia Manrope + DM Sans, cantos retos, muito respiro. Nenhum dos quatro temas do wab-sites tem essa aparência. Esta mudança cria o quinto tema, `atelie`, genérico (serve a qualquer cliente de marcenaria, arquitetura, decoração), com desenho próprio para todos os tipos de bloco e para o blog.

Mudança 3 de 4. Depende de `blocos-novos-do-construtor` e `blog-nos-sites-institucionais` arquivadas. Não cria o site da Pichoneri: isso é a mudança 4.

## What Changes

- Tema novo `atelie` em `templates/themes/atelie/`, oferecido no `/admin/settings` e no `/superadmin` como "Ateliê (editorial, tons de madeira)".
- Desenho próprio, fiel ao HTML de referência, para os 23 tipos de bloco, para a página, a categoria, a home sem página montada, o perfil de membro da equipe e o blog (lista e post).
- Cabeçalho fixo com logo, nome do site, menu (seções da landing, categorias, páginas e itens personalizados) e **botão de ação** configurável; barra superior que já existe; rodapé com endereço, horário, contatos, redes, páginas do rodapé e newsletter; botão flutuante de WhatsApp em formato de pílula.
- Três configurações novas de navegação, por site, editadas no `/admin/settings`: texto e link do botão de ação do cabeçalho e a frase curta abaixo do nome. Os temas atuais não as usam.
- Famílias Manrope e DM Sans no catálogo de fontes dos títulos (servidas pelo próprio domínio).
- O tema só tem modo claro (sem seletor claro/escuro).
- Site de demonstração nas fixtures (`atelie.localhost`) que reproduz o HTML de referência seção por seção.
- Nenhum template, CSS ou comportamento dos temas existentes muda.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `navegacao-e-aparencia`: "Tema do tenant" passa a listar o `atelie`; requisitos novos do tema (cabeçalho, botão de ação, rodapé, modo claro, blocos, desempenho); "Peso dos títulos dentro da faixa da família" ganha a Manrope.
- `gestao-de-sites`: "Fontes dos títulos na edição do tenant" passa de seis para oito famílias.
- `construtor-de-paginas`: perfil de membro da equipe e home sem página no tema novo.

## Impact

- Arquivos novos: `templates/themes/atelie/**` (cerca de 40 arquivos), `src/DataFixtures/AtelieFixtures.php`, fontes em `assets/fonts/`.
- Arquivos alterados: `templates/admin/settings.html.twig`, `templates/superadmin/tenant/new.html.twig` e `edit.html.twig` (opção do tema); `src/Controller/admin/AdminContentController.php` e `src/Controller/superadmin/SuperAdminController.php` (configurações de navegação); `src/Entity/Tenant.php` (padrões de navegação); `src/Service/HeadingFonts.php`; `assets/styles/app.css` (só o trecho de `@font-face` gerado pelo comando); `tests/Entity/BlockTypeCoberturaTest.php`; `README.md`.
- Banco: nenhuma migration (as configurações novas ficam no JSON `navigationSettings`).
- `app.css` cresce com as regras `@font-face` das duas famílias (o arquivo de fonte só é baixado por quem usa).
