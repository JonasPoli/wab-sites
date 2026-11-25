# Proposal

## Why

Hoje o único botão flutuante dos sites é o do WhatsApp, que aparece sozinho sempre que o site tem `whatsappLink`, e só nos temas cetec e atelie. Há clientes (a Transmai, tenant 13, por exemplo) que preferem levar o visitante para o formulário de contato do site em vez do WhatsApp, e não há como desligar o botão nem trocá-lo sem mexer em código.

## What Changes

- Na edição do tenant no `/superadmin` (`/superadmin/tenant/{id}/edit`), um grupo novo "Botão flutuante" com a escolha:
  - **Nenhum**: o site não mostra botão flutuante, mesmo com WhatsApp cadastrado;
  - **WhatsApp**: comportamento de hoje, o botão aparece quando o campo WhatsApp está preenchido;
  - **Formulário**: o botão leva a uma página do site.
- No modo Formulário, três campos: a página de destino (lista das páginas do site, as mesmas de `/admin/page`), o texto do botão (até 40 caracteres) e o ícone, escolhido no mesmo seletor de ícones do bloco Blurbs4 (modal com busca e abas).
- O botão de formulário aparece no canto inferior direito, na cor primária do site (a versão escura no modo escuro), com texto em cor legível sobre ela; ele não aparece na própria página de destino nem na prévia do bloco no painel.
- Os cinco temas (cetec, moderno, wab, noticias e atelie) passam a desenhar o botão flutuante, dos dois tipos. moderno, wab e noticias não tinham o do WhatsApp; para não mudar o que os sites desses temas mostram hoje, os tenants que já usam esses temas começam com "Nenhum".
- Os tenants de cetec e atelie começam com "WhatsApp": nada muda no que já está no ar.
- A escolha vai na exportação e na importação do tenant (a página de destino é reconectada à página importada).
- O `app:icones:sincronizar` passa a considerar o ícone do botão flutuante.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `navegacao-e-aparencia`: requisitos novos do botão flutuante (escolha do tipo, WhatsApp, formulário, cores, onde não aparece) e o "Rodapé do tema atelie" deixa de amarrar o botão do WhatsApp só ao link cadastrado.
- `gestao-de-sites`: a edição do tenant no `/superadmin` ganha o grupo "Botão flutuante" (página só do próprio site, seletor de ícone) e a exportação/importação passa a levar a escolha.

## Impact

- Banco: coluna nova `tenant.floating_button` (JSON, nula), por migration gerada com `doctrine:migrations:diff`, mais o valor inicial "Nenhum" para os tenants de tema moderno, wab e noticias.
- Código: `src/Entity/Tenant.php`, `src/Controller/superadmin/SuperAdminController.php`, serviço novo que resolve o botão do site aberto e uma função Twig, `TenantExporter`, `TenantImporter`, `SincronizarIconesCommand`.
- Templates: `superadmin/tenant/edit.html.twig`; o seletor de ícones sai de `admin/wab_base.html.twig` para um parcial reaproveitado; parcial novo `themes/_comum/_botao_flutuante.html.twig` incluído no `base.html.twig` dos cinco temas (o cetec e o atelie trocam o bloco atual do WhatsApp por ele).
- Produção: backup do banco, `git pull`, `./build.sh` e `doctrine:migrations:migrate` (escrita, pedida pelo Jonas: "publique").
