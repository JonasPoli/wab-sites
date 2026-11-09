# Proposta: isolamento-entre-sites

## Why

Uma instalação e um banco atendem os 8 sites de clientes, e hoje o isolamento entre eles depende quase só do filtro de tenant do Doctrine. Onde o filtro não alcança (usuários, seções, blocos, membros de equipe) ou atrapalha (`/superadmin` aberto no domínio de outro site), um site enxerga ou altera dados de outro, e o painel global apaga a home, exporta vazio e deixa arquivos órfãos. Decisões do Jonas de 09/11/2026.

## What Changes

- **BREAKING** O login do painel passa a valer só no domínio do site do usuário: o usuário com tenant só entra (e só continua logado) no domínio desse tenant; o SuperAdmin continua entrando em todos. Hoje em produção há usuários de painel nos tenants 1, 3, 4, 5, 6, 8 e 9, além de 2 usuários sem tenant que são SuperAdmins.
- Seções e blocos do `/admin` passam a ser localizados pelo tenant da página ou categoria a que pertencem: editar, excluir, duplicar, listar blocos, criar bloco e reordenar com o id de uma seção ou bloco de outro site responde 404 ou é ignorado.
- `/membro/{slug}` só mostra membro de equipe cadastrado num bloco de página ou categoria do site do domínio.
- `/admin/settings` e `/admin/editors` (lista, criação e exclusão) passam a exigir administrador (`workGroup` 0) do site do domínio ou SuperAdmin; editor e revisor recebem 403 mesmo digitando o endereço. A criação só aceita `workGroup` 1 ou 2, e a lista e a criação de editores usam o tenant do domínio.
- O `/superadmin` passa a trabalhar sem o filtro de tenant do domínio aberto: editar, exportar e excluir um site dá o mesmo resultado em qualquer domínio de tenant. O seletor de página home só aceita páginas do tenant editado.
- "Entrar como" no `/superadmin` passa a abrir o `/admin` no domínio do site do usuário escolhido.
- Correção de dado em produção, com aprovação do Jonas: o usuário id 19, com nome da Pet Vida, hoje é SuperAdmin só por estar sem tenant (usuário sem tenant com `workGroup` 0 vira SuperAdmin); ele passa ao tenant 7 pelo formulário do `/superadmin`.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

- `isolamento-por-dominio`: o filtro deixa de valer no `/superadmin`; seções e blocos passam a ser isolados pelo tenant da página ou categoria; o login global por username é substituído pelo login restrito ao site do usuário; papéis passam a barrar editor e revisor em configurações e editores; editores passam a usar o tenant do domínio.
- `construtor-de-paginas`: o perfil de membro da equipe passa a respeitar o site do domínio.
- `gestao-de-sites`: home, exportação e exclusão de um tenant deixam de depender do domínio aberto; "Entrar como" muda de domínio; editores só por administrador, só `workGroup` 1 ou 2 e no tenant do domínio.

## Impact

- Código: `App\EventSubscriber\TenantSubscriber`, `config/packages/security.yaml`, novos `App\Security\TenantUserChecker` e `App\Security\Voter\TenantAccessVoter`, `App\Controller\admin\AdminContentController` (seções, blocos, reordenação, configurações e editores), `App\Controller\pub\WabPublicController::teamMember()`, `PageSectionRepository`, `PageBlockRepository`, `PageBlockTeamMemberRepository` e `App\Controller\superadmin\SuperAdminController` (home do tenant e "Entrar como").
- Sem mudança de esquema nem migration; sem template público alterado.
- Usuários: quem hoje entra no painel pelo domínio de outro site passa a receber recusa no login ou 403. Sessões abertas antes do deploy num domínio que não é o do usuário deixam de valer na próxima requisição ao `/admin`.
- Produção: deploy de código (git pull e cache:clear) e a edição do usuário id 19 pelo `/superadmin`; nenhuma outra escrita no banco.
