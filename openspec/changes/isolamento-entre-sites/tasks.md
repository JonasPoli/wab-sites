# Tasks: isolamento-entre-sites

Conferência sempre em `http://127.0.0.1:8000` (tenant A das fixtures, tema wab) e `http://localhost:8000` (tenant B, tema moderno): são hosts diferentes, com sessões separadas. Os testes de `tests/` estão quebrados e não contam como conferência.

## 1. Ambiente local e dados de teste

- [ ] 1.1 Carregar as fixtures no banco local (`bin/console doctrine:fixtures:load --no-interaction`) e conferir que `/` responde 200 em 127.0.0.1:8000 e em localhost:8000.
- [ ] 1.2 Como `superadmin` em localhost:8000, montar no tenant B: uma página com seção e dois blocos (um deles `team` com um membro de perfil ligado e slug `membro-teste`), uma categoria com seção e bloco, a página como home do tenant B, e um usuário admin (`workGroup` 0) e um editor no tenant B pelo `/superadmin`; repetir página, seção, blocos e membro com o mesmo slug no tenant A, em 127.0.0.1:8000. Conferir anotando os ids de seções e blocos de cada tenant.

## 2. Login e sessão por site

- [ ] 2.1 Criar `App\Security\TenantUserChecker` (`checkPostAuth()`: usuário que não é SuperAdmin e cujo tenant não é o do `TenantContext` recebe "Este usuário não tem acesso a este site.") e ligá-lo em `firewalls.main.user_checker`. Conferir: `editor` (tenant A) é recusado em localhost:8000/login com a mensagem e aceito em 127.0.0.1:8000; o admin do tenant B é recusado em 127.0.0.1:8000; `superadmin` entra nos dois.
- [ ] 2.2 Criar `App\Security\Voter\TenantAccessVoter` com `TENANT_ACCESS` e `TENANT_MANAGE` e trocar o `access_control` de `^/admin` para `TENANT_ACCESS`. Conferir: com o checker desligado temporariamente, abrir sessão do `editor` em localhost:8000; religar o checker e ver 403 em localhost:8000/admin/page, enquanto a sessão de `superadmin` segue com 200.

## 3. Configurações e editores

- [ ] 3.1 Pôr `#[IsGranted('TENANT_MANAGE')]` em `settings`, `editorIndex`, `editorNew` e `editorDelete` e trocar a condição do item "Editores" do menu (`admin/wab_base.html.twig`) por `is_granted('TENANT_MANAGE')`. Conferir em 127.0.0.1:8000: `editor` e `revisor` recebem 403 em `/admin/settings`, `/admin/editors` e `/admin/editors/new`; `admin` abre os dois com 200; o menu do `editor` não mostra "Editores".
- [ ] 3.2 Fazer editores usarem o tenant do `TenantContext`, aceitar só `workGroup` 1 ou 2 na criação (outro valor vira 1) e excluir só usuário `workGroup` 1 ou 2 do tenant do domínio. Conferir: `superadmin` cria um editor em localhost:8000 e ele fica no tenant B; um POST de criação com `workGroup=0` grava 1; o `admin` não consegue excluir o editor do tenant B nem outro usuário `workGroup` 0 do tenant A.

## 4. Seções, blocos e perfil de membro

- [ ] 4.1 Criar `findOneForTenant()` e `findForTenantByIds()` em `PageSectionRepository` e `PageBlockRepository` (join até a Page ou Category com o tenant explícito) e `findBySlug(string $slug, Tenant $t)` em `PageBlockTeamMemberRepository`. Conferir com `bin/console lint:container` sem erro e, no profiler de 127.0.0.1:8000, que as consultas trazem o join até `page`/`category` com o `tenant_id`; o efeito é conferido em 4.2 a 4.4.
- [ ] 4.2 Trocar a resolução automática de `PageSection`/`PageBlock` por id + helpers `sectionOr404()`/`blockOr404()` em `section_edit`, `section_delete`, `section_duplicate`, `block_index`, `block_new`, `block_edit`, `block_delete` e `block_duplicate`. Conferir como `admin` em 127.0.0.1:8000, com os ids do tenant B: as oito rotas respondem 404 (POST com token válido nas de exclusão e duplicação) e as seções e blocos do tenant B continuam iguais em localhost:8000; com os ids do tenant A (seção de página e de categoria), tudo funciona.
- [ ] 4.3 Na reordenação de seções e blocos, aplicar posição só aos ids de `findForTenantByIds()`. Conferir com um POST JSON em 127.0.0.1:8000 misturando ids dos tenants A e B: só os do A mudam e a resposta é `{"ok": true}`.
- [ ] 4.4 Passar o tenant do `TenantContext` em `WabPublicController::teamMember()`. Conferir: `/membro/membro-teste` mostra o membro do tenant B em localhost:8000 e o do tenant A em 127.0.0.1:8000; depois de desligar o perfil do membro do tenant A, 127.0.0.1:8000/membro/membro-teste responde 404 em vez de mostrar o do tenant B.

## 5. Painel global sem filtro

- [ ] 5.1 No `TenantSubscriber`, não ligar o `tenant_filter` nos caminhos `^/superadmin(/|$)`, com um comentário dizendo que o `/superadmin` precisa informar o tenant em toda consulta. Conferir em 127.0.0.1:8000/superadmin: a edição do tenant B lista as páginas dele, e salvar sem mudar nada mantém a home do tenant B (localhost:8000 `/` continua igual).
- [ ] 5.2 Em `populateTenantFromRequest()`, aceitar como home só página do tenant editado (`findOneBy(['id' => …, 'tenant' => $tenant])`). Conferir com um POST de edição do tenant B trazendo o id de uma página do tenant A: o tenant B fica sem home e o tenant A não muda; criar um tenant novo deixa a home nula.
- [ ] 5.3 Conferir a exportação: exportar o tenant B a partir de 127.0.0.1:8000/superadmin e de localhost:8000/superadmin e comparar os `metadata.json` (mesmas páginas, seções, blocos e arquivos de mídia); apagar os ZIPs locais depois.
- [ ] 5.4 Conferir a exclusão: criar pelo `/superadmin` um tenant `teste.localhost` (o navegador resolve `*.localhost` para 127.0.0.1), montar nele em `http://teste.localhost:8000/admin` uma página com seção, bloco com imagem, galeria e banner, listar os arquivos dele em `public/uploads`, excluí-lo a partir de 127.0.0.1:8000/superadmin e conferir que o resumo traz páginas, seções, blocos e arquivos apagados, que esses arquivos sumiram e que os tenants A e B continuam inteiros. Não usar tenant importado do B: a importação reaproveita os nomes de arquivo (ver design).

## 6. "Entrar como" no domínio do usuário

- [ ] 6.1 Em `impersonate()`, redirecionar para o domínio do tenant do usuário, com o esquema e a porta da requisição (usuário sem tenant fica no domínio atual). Conferir: `superadmin` logado só em localhost:8000 entra como `admin` (tenant A), é levado a 127.0.0.1:8000, faz login como `superadmin` e cai no `/admin` do tenant A como `admin`; `127.0.0.1:8000/admin?_switch_user=<admin do tenant B>` com sessão de `superadmin` responde 403.

## 7. Produção

- [ ] 7.1 Backup (escrita no servidor, com aprovação do Jonas): anotar `git rev-parse HEAD` do webapp e gerar dump do banco para fora do docroot (sem exibir credenciais). Conferir que o dump abre e tem a tabela `user` com os 11 usuários de 09/11/2026.
- [ ] 7.2 Corrigir o usuário id 19 (escrita no banco pelo formulário do `/superadmin`, com aprovação do Jonas): tenant 7 e o `workGroup` que o Jonas indicar (padrão: 0, admin do site). Conferir por leitura que restou um único usuário sem tenant (id 1) e que o id 19 está no tenant 7.
- [ ] 7.3 Deploy (escrita, com aprovação do Jonas): `git push origin main` local; no servidor, como runcloud, `git pull` e `bin/console cache:clear` (só PHP e configuração; se algum template tiver mudado, `./build.sh`); sem migration. Conferir HTTP 200 em `/` dos 8 domínios de cliente (marapossi.com.br, pkwy.com.br, cemporcentoexpresso.com.br, imperiodainformatica.com.br, site.transterraararaquara.com.br, site.petvida.com.br, site.topdrim.com.br, site.abifer.com.br).
- [ ] 7.4 Conferência depois do deploy (leitura): `/login` 200 e `/membro/nao-existe` 404 nos 8 domínios; `/superadmin` redireciona para o login; o Jonas abre a edição de um tenant pelo domínio de outro e vê a lista de páginas, sem salvar; nas 24 h seguintes, contar 403 em `/admin` por domínio no log do nginx e tratar com o Jonas qualquer usuário barrado.
- [ ] 7.5 Atualizar o `context:` do `openspec/config.yaml`: o `/superadmin` passa a funcionar sem o filtro em qualquer domínio de tenant e o login só vale no site do usuário (tirar a orientação de exportar e excluir pelo domínio do próprio tenant). Conferir com `openspec validate isolamento-entre-sites --strict`.

## Workflow follow-up

- Não é pedido de cliente: não há ticket obrigatório; se o Jonas quiser registrar, ticket interno no cliente WAB 196.
- Ao arquivar, atualizar o `## Purpose` de `gestao-de-sites` (cita o filtro ligado no `/superadmin`) e conferir o de `isolamento-por-dominio`.
- Arquivar a mudança depois da conferência de produção.
