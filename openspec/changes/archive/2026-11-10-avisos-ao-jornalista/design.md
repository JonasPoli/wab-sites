# Design: avisos-ao-jornalista

## Context

Motivação em `proposal.md`; requisitos em `specs/noticias/spec.md`.

- **Aprovação:** `AdminAuthorController::action()` aplica a transição, grava quem decidiu e redireciona para a ficha. Não envia e-mail.
- **Envio de e-mail:** o `MainController` e o `WabPublicController` já mandam o e-mail de contato com `TemplatedEmail`, `emailFrom` (parâmetro) e o nome do tenant, e registram erros no log.
- **Mensagem do cadastro:** está em `JournalistSignupController::SUCCESS_MESSAGE`.
- **Painel inicial:** o `dashboard()` monta números de contatos, páginas e newsletter.

## Decisions

1. **Serviço `App\Service\JournalistNotifier`.**
   - Método `statusChanged(Author $author, AuthorStatus $from, string $loginUrl): bool`, que devolve se o envio deu certo.
   - Monta um `TemplatedEmail`:
     - de `emailFrom`, com o nome do site;
     - para o e-mail do usuário do autor;
     - assunto `[<site>] …`;
     - template `email/journalist_status.html.twig`, com o caso (`aprovado`, `reativado`, `recusado` ou `suspenso`).
   - Erros vão para o log e o método devolve `false`.
   - **Alternativa descartada:** um listener de Doctrine na mudança de `status`, porque dispararia também em importações e fixtures.
2. **Link de login.** Sai de `generateUrl('app_login', [], ABSOLUTE_URL)` dentro do pedido do admin, que acontece no domínio do site. Em produção o esquema detectado é `https`: o `canonical` das páginas já sai com `https://ajope.wab.com.br/`.
3. **Textos dos e-mails** (HTML simples, com o nome do site):
   - **aprovado:** "Seu cadastro como jornalista no portal X foi aprovado. Para publicar, entre em <link> com o seu e-mail (<e-mail>) e a senha que você escolheu no cadastro. Depois de entrar, você vai para a área do jornalista, onde cria e publica as suas notícias.";
   - **reativado:** "Seu acesso de jornalista ao portal X foi reativado", com o mesmo link;
   - **recusado:** "Seu cadastro como jornalista no portal X não foi aprovado pela administração.";
   - **suspenso:** "Seu acesso de jornalista ao portal X foi suspenso pela administração. Enquanto estiver suspenso, você não consegue entrar nem publicar."
4. **Falha no envio.** A situação já está gravada quando o envio é tentado. Se o envio falhar, o painel mostra a mensagem de sucesso seguida de ", mas o e-mail de aviso não pôde ser enviado.", como aviso.
5. **Quadro no painel.**
   - O `dashboard()` só busca os dados quando o tema é `noticias` e o usuário é admin do site ou SuperAdmin (a mesma regra do `AdminAuthorController`).
   - Os pendentes vêm de `AuthorRepository::findForAdmin(Pending)`, limitados a 10.
   - Os aprovados são contados com `count`.
   - As consultas passam pelo filtro de tenant do `/admin` (Author é TenantAware).
6. **Cadastro.** A mensagem nova vai no `SUCCESS_MESSAGE`, que também é mostrada ao robô do campo isca. A situação inicial continua "pendente", o padrão de `Author`.

## Risks / Trade-offs

- **[E-mail na caixa de spam]** O remetente é `noreply@wab.com.br`, pelo WMailer, o mesmo do formulário de contato.
- **[Aprovação em lote no futuro]** Hoje a aprovação é uma por vez, e cada uma manda um e-mail na hora.

## Migration Plan

Deploy junto com os outros changes (`git pull` e `./build.sh`, mudaram templates), sem migration. Conferência local com um servidor SMTP de teste e, em produção, com um cadastro de teste. O cadastro de teste precisa do OK do Jonas, porque aprová-lo manda e-mail de verdade.
