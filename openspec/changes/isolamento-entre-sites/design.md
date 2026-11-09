# Design: isolamento-entre-sites

## Context

Motivação em `proposal.md`. Estado atual lido no código e em produção em 09/11/2026:

- `TenantSubscriber` (prioridade 100, antes do firewall) acha o tenant pelo host, guarda no `TenantContext` e liga o `tenant_filter` em toda requisição, inclusive no `/superadmin`.
- `security.yaml`: provider de entidade por `username`, `form_login`, `switch_user` para ROLE_SUPER_ADMIN e `access_control` só por papel (`^/admin` ROLE_ADMIN). Não há `user_checker` nem conferência do tenant do usuário.
- `AdminContentController`: `PageSection` e `PageBlock` chegam pelo id (conversão automática da rota ou `find()`), sem tenant: `section_edit`, `section_delete`, `section_duplicate`, `section_reorder`, `block_index`, `block_new`, `block_edit`, `block_delete`, `block_duplicate` e `block_reorder`. Os filhos do bloco (galeria, depoimentos, logos, membros) só são alterados a partir do bloco, então ficam protegidos quando o bloco fica.
- `/admin/settings` e `/admin/editors` só têm `#[IsGranted('ROLE_ADMIN')]` da classe; quem esconde é o menu (`wab_base.html.twig`: Editores para `workGroup` 0, Configurações só para ROLE_SUPER_ADMIN, desde o commit e8d3a0d de 20/05/2026). `editorNew` grava o `workGroup` enviado e o tenant do usuário logado (nulo para o SuperAdmin); `editorDelete` apaga qualquer usuário do mesmo tenant.
- `PageBlockTeamMemberRepository::findBySlug()` busca o membro só pelo slug.
- `SuperAdminController`: edição, exportação (`TenantExporter`) e exclusão já consultam com `['tenant' => $tenant]` explícito, mas o filtro do domínio somado zera o resultado quando o tenant editado não é o do domínio. `populateTenantFromRequest()` usa `$pageRepo->find(homePageId)` sem conferir o tenant. `impersonate()` redireciona para `/admin?_switch_user=` no mesmo domínio. `TenantImporter` só consulta Tenant e User, que não são filtrados.
- Produção (leitura, 09/11/2026): 2 usuários sem tenant e com `workGroup` 0, ou seja, SuperAdmins: o id 1 (`superadmin`) e o id 19, cujo username é o nome da Pet Vida (tenant 7), que não tem nenhum usuário próprio; admins (`workGroup` 0) nos tenants 1, 8 e 9; editores nos tenants 1, 3, 4, 5 e 6; um revisor no tenant 1; nenhum usuário sem tenant com `workGroup` 1 ou 2. Os 8 tenants têm home do próprio tenant. Nenhum membro de equipe tem perfil detalhado ligado, então o vazamento de `/membro` ainda não tem dado exposto. O log do nginx (jun–nov) mostra uso do `/admin` e do `/superadmin` nos 8 domínios. O tenant 2 (`localhost`) não existe mais, e não há POST de exclusão de tenant no log desde junho.

## Goals / Non-Goals

**Goals:**

- Uma única regra de acesso ao painel por domínio, aplicada no login, na troca de usuário e em toda requisição ao `/admin`.
- Seções, blocos e membros sempre resolvidos pelo tenant da Page ou Category, sem depender de o filtro estar ligado.
- `/superadmin` com o mesmo resultado em qualquer domínio de tenant.

**Non-Goals:**

- Coluna `tenant_id` em PageSection, PageBlock ou filhos (exigiria migration e backfill).
- Token CSRF nas rotas de reordenação (`*/reorder` não conferem token hoje); fica para outra mudança.
- Mostrar "Configurações" no menu para o admin do site (ver Open Questions).
- Limpar arquivos órfãos de exclusões anteriores (ver Open Questions) e os ZIPs antigos de `var/tmp`.
- Confirmação extra ao criar no `/superadmin` usuário sem tenant com `workGroup` 0 (que vira SuperAdmin); fica como sugestão para outra mudança.
- Consertar os testes de `tests/`.

## Decisions

### 1. Login e troca de usuário: `TenantUserChecker`

`App\Security\TenantUserChecker` (implementa `UserCheckerInterface`), ligado em `firewalls.main.user_checker`. Em `checkPostAuth()`, se o usuário é `App\Entity\User`, não é SuperAdmin (`isSuperAdmin()`) e o tenant dele não tem o mesmo id do tenant do `TenantContext`, lança `CustomUserMessageAccountStatusException('Este usuário não tem acesso a este site.')`. O `checkPostAuth()` roda depois da senha no `form_login` (a mensagem só aparece para quem acertou a senha) e também no `switch_user`, que converte a exceção em 403.

Alternativa considerada: um `loadUserByIdentifier()` no `UserRepository` que só acha usuários do tenant do domínio. Bloquearia login e troca com "credenciais inválidas", mas não dá mensagem clara e não cobre a sessão já aberta (o refresh da sessão busca por id). Fica o checker, que é explícito.

### 2. Toda requisição ao `/admin`: `TenantAccessVoter`

`App\Security\Voter\TenantAccessVoter` com dois atributos:

- `TENANT_ACCESS`: o token tem ROLE_ADMIN e o usuário é SuperAdmin ou tem tenant com o mesmo id do tenant do domínio.
- `TENANT_MANAGE`: `TENANT_ACCESS` e `workGroup` 0.

`access_control` passa a `{ path: ^/admin, roles: TENANT_ACCESS }` (o voter já exige ROLE_ADMIN). `settings`, `editorIndex`, `editorNew` e `editorDelete` ganham `#[IsGranted('TENANT_MANAGE')]`. Isso cobre sessões abertas antes do deploy, que o checker não vê, e a troca de domínio de um tenant pelo `/superadmin`. O item "Editores" do menu pode passar a `is_granted('TENANT_MANAGE')`, com o mesmo efeito do `workGroup == 0` atual.

Alternativa considerada: um listener de `kernel.request` depois do firewall. Funciona, mas o voter também serve para `#[IsGranted]` e para o Twig.

### 3. Seções e blocos pelo tenant da Page ou Category

Novos métodos nos repositórios, com o tenant passado explicitamente (não dependem do filtro):

- `PageSectionRepository::findOneForTenant(int $id, Tenant $t)`: `s LEFT JOIN s.page p LEFT JOIN s.category c WHERE s.id = :id AND (p.tenant = :t OR c.tenant = :t)`.
- `PageBlockRepository::findOneForTenant(int $id, Tenant $t)`: o mesmo, partindo de `b JOIN b.section s`.
- `findForTenantByIds(array $ids, Tenant $t)` nos dois, para a reordenação.

No controller, as ações que hoje recebem `PageSection $section` ou `PageBlock $block` (ou `int $sectionId` + `find()`) passam a receber o id e usar helpers privados `sectionOr404()` e `blockOr404()` com o tenant de `TenantContext::requireTenant()`. A reordenação só aplica posição aos ids devolvidos por `findForTenantByIds()` e continua respondendo `{"ok": true}`. O `DuplicatorService` não muda: só é chamado depois do helper.

Por que não confiar no lazy load (`$section->getPage()->getTenant()`): com o filtro ligado, carregar o proxy de uma Page de outro tenant lança `EntityNotFoundException` (erro 500) em vez de 404.

Alternativa considerada: coluna `tenant_id` nas tabelas de seção e bloco. Rejeitada (Non-Goals).

### 4. `/membro/{slug}` pelo tenant do domínio

`PageBlockTeamMemberRepository::findBySlug(string $slug, Tenant $t)` faz o join `m.block → b.section → (s.page | s.category)` com `p.tenant = :t OR c.tenant = :t`, e `teamMember()` passa o tenant do `TenantContext`. Os templates `team_member_classic` e `team_member_modern` não mudam e existem nos três temas (cetec, moderno e wab).

### 5. `/superadmin` sem o filtro de tenant

No `TenantSubscriber`, o tenant continua sendo resolvido e guardado para todo caminho (assim `site.wab.com.br/superadmin` segue em 404), mas o `tenant_filter` só é ligado quando o caminho não casa com `^/superadmin(/|$)`. A regra fica num só lugar e vale para ações futuras do painel global.

Consequências tratadas:

- `populateTenantFromRequest()`: a home passa a `findOneBy(['id' => $homePageId, 'tenant' => $tenant])`; no tenant novo, que não tem páginas, sempre fica nula.
- Edição, exportação e exclusão já filtram por tenant explicitamente e passam a funcionar em qualquer domínio; a importação não muda.
- As globais do Twig (`headerCategories`, `footerPages`, `footerCategories`) são calculadas também nas telas do `/superadmin`, agora sem filtro. Os templates `superadmin/*` não as usam; o custo são três consultas pequenas.

Alternativa considerada: ligar o filtro com o id do tenant do registro editado. Rejeitada: o painel, a lista de usuários e a importação não têm um tenant só, e as ações por tenant já consultam com o tenant explícito.

### 6. "Entrar como" no domínio do usuário

`impersonate()` monta `<esquema>://<domínio do tenant do usuário>[:porta]/admin?_switch_user=<username>`, com o esquema e a porta da requisição atual (a porta só quando não é 80/443, para funcionar em `127.0.0.1:8000` e `localhost:8000`); usuário sem tenant fica no domínio atual. Sem sessão no outro domínio, o `switch_user` dispara a tela de login, que guarda o endereço e volta a ele depois do login. A troca forçada no domínio errado é recusada pelo checker (decisão 1) com 403.

### 7. Editores no tenant do domínio

`editorIndex`, `editorNew` e `editorDelete` passam a usar o tenant do `TenantContext`. Para quem não é SuperAdmin, ele é igual ao tenant do usuário (decisões 1 e 2); para o SuperAdmin, corrige o caso atual em que editores eram gravados sem tenant. `editorNew` aceita só `workGroup` 1 ou 2 (outro valor vira 1); `editorDelete` só apaga usuário com `workGroup` 1 ou 2 do tenant do domínio.

### 8. Usuário id 19 deixa de ser SuperAdmin

Com o login restrito, um SuperAdmin continua entrando em todos os sites e no `/superadmin`. O usuário id 19 tem o nome da Pet Vida e hoje é SuperAdmin só porque está sem tenant. A correção é de dado, não de código: com aprovação do Jonas, editar o usuário no `/superadmin`, escolher o tenant 7 e manter `workGroup` 0 (admin do site), ou o valor que o Jonas indicar. Feita pelo formulário, sem SQL, com backup antes e conferência depois (tasks). Deve ir ao ar junto com o deploy, ou antes dele.

### Como o isolamento fica garantido

| Acesso | Garantia |
|---|---|
| Login, troca de usuário | `TenantUserChecker` compara o tenant do usuário com o do domínio |
| Toda rota `/admin` | `TENANT_ACCESS` no `access_control`; configurações e editores com `TENANT_MANAGE` |
| Page, Category, banners, contatos, inscritos no `/admin` | `tenant_filter` (como hoje) |
| PageSection, PageBlock e filhos no `/admin` | Consultas com join até a Page ou Category e o tenant explícito |
| `/membro/{slug}` | Consulta com join até a Page ou Category e o tenant explícito |
| `/superadmin` | Sem filtro; cada ação diz o tenant explicitamente; acesso só para ROLE_SUPER_ADMIN |

## Risks / Trade-offs

- [Usuário que hoje entra pelo domínio de outro site perde o acesso] → Os editores dos tenants 3, 4, 5 e 6 e os admins dos tenants 1, 8 e 9 passam a entrar só pelo próprio domínio. Antes do deploy, o Jonas avisa quem precisar; depois, contar respostas 403 e POST `/login` recusados por domínio no log do nginx (leitura).
- [Editor que usava `/admin/settings` digitando o endereço perde o acesso] → Os tenants 3, 4 e 5 só têm editores. Se algum deles precisar das configurações, o Jonas promove o usuário a admin (`workGroup` 0) pelo `/superadmin`.
- [Código novo do `/superadmin` que consultar entidade com tenant sem dizer o tenant vê todos os sites] → Comentário no `TenantSubscriber`, o requisito "Filtro automático no site e no painel" e a linha do `context:` do `openspec/config.yaml` atualizada depois do deploy.
- ["Entrar como" exige login também no domínio do usuário] → É o custo de sessões por domínio; o retorno automático ao endereço depois do login reduz o atrito.
- [Seção sem página nem categoria fica inacessível no `/admin`] → Ela já não aparece em nenhum site; aceitável.
- [Tenant importado neste servidor usa os mesmos nomes de arquivo do original (o `TenantImporter` copia a mídia com o mesmo nome); com a exclusão completa, excluir um apagaria arquivos do outro] → Em 09/11/2026 nenhum arquivo de bloco, galeria, fundo ou logo é usado por mais de um tenant (leitura), e a única importação no log (07/07) não chegou a gravar. Antes de excluir um tenant que veio de importação, conferir nomes compartilhados; renomear a mídia na importação fica para outra mudança.

## Migration Plan

1. Implementar e conferir em `127.0.0.1` e `localhost` com as fixtures (dois tenants, sessões separadas por host).
2. Produção, com aprovação do Jonas (escrita): anotar o commit atual e fazer backup do banco; corrigir o usuário id 19 (decisão 8); `git pull` e `bin/console cache:clear` (só PHP e configuração; se algum template mudar, `./build.sh`); sem migration.
3. Conferência: HTTP 200 dos 8 domínios, `/login` 200, `/membro/<slug-inexistente>` 404, `/superadmin` redireciona para o login, e o Jonas abre a edição de um tenant pelo domínio de outro e vê a lista de páginas (sem salvar).
4. Volta: `git checkout <commit anotado>` e `cache:clear`. Não há dado a desfazer.

## Open Questions

- O admin do site (`workGroup` 0 com tenant) deve ver "Configurações" no menu? Hoje só o SuperAdmin vê (commit e8d3a0d), embora o README diga que o admin do site cuida das configurações. A mudança mantém o acesso dele pelo endereço; mostrar o item é uma linha no menu e não muda os specs.
- O tenant 2 (`localhost`, sobra das fixtures) não existe mais em produção, e o log do nginx desde junho não mostra a exclusão. Se ela foi pelo `/superadmin` aberto em outro domínio, arquivos do tenant 2 podem ter ficado em `public/uploads`. Levantar e limpar fica para depois, com aprovação.
