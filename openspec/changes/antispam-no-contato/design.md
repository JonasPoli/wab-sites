# Design

## Context

Ver proposal.md — Why. O estado de hoje que molda a solução:

- O POST `/contato` só tem CSRF; o `RecaptchaVerifier` existe mas depende das duas chaves do tenant, e nenhum tenant em produção as tem (conferido no HTML público de imperiodainformatica.com.br, que não traz nada de reCAPTCHA).
- Os 7 formulários de contato (`pub/main/home`, temas `cetec`, `moderno` e `wab`, cada um em `home.html.twig` e em `blocks/_contact.html.twig`) já incluem o mesmo parcial `pub/_recaptcha.html.twig`. Esse ponto único é por onde os campos novos entram em todos os temas de uma vez.
- Em produção não há worker nem cron, o Monolog escreve no stderr do PHP-FPM, que ninguém lê, e `var/log` fica vazio: um bloqueio que só exista no log é um bloqueio invisível. Daí gravar o bloqueado no banco.
- `cache.app` é filesystem (`var/cache`), sem Redis. Serve para um contador por IP de uma hora, aceitando que um `cache:clear` zere os contadores.
- Nenhum tenant tem `contactEmail` próprio na maioria dos casos: o contato cai em `EMAIL_CONTACT_TO` (suporte@wab.com.br), comum a todos os sites. Por isso o domínio de origem passa a ser gravado e mostrado.

## Goals / Non-Goals

**Goals:**

- Barrar o robô genérico sem pedir nada do visitante: nenhum captcha, nenhum clique, nenhum campo novo visível.
- Funcionar em qualquer tenant, sem chave de terceiro e sem dependência nova no composer.
- Nunca perder em silêncio a mensagem de um cliente de verdade: o que é bloqueado fica gravado e visível no painel.
- Um único ponto de verdade no servidor (um serviço) e um único ponto nos templates (o parcial já incluído nos 7 formulários).

**Non-Goals:**

- Não tenta parar spam feito à mão por pessoa, nem robô escrito sob medida para este formulário (preenche só os campos visíveis, espera alguns segundos, troca de IP). Para esse caso a defesa continua sendo o reCAPTCHA v3, que já está pronto e só espera chaves.
- Não mexe na newsletter nem nos campos extras configuráveis (`ContactFormField`, que o `contactPost()` ainda não processa).
- Não cria tela nova no painel: o filtro de bloqueados é um parâmetro na tela que já existe.

## Decisions

### Um serviço `ContatoAntispam` que devolve o motivo, em vez de espalhar `if` no controller

`verificar(Tenant $tenant, Request $request, string $nome, string $mensagem): ?string` devolve `null` quando o envio passa, ou um motivo curto (`isca`, `tempo`, `limite_ip`, `conteudo`) quando reprova. O controller decide o que fazer com o motivo; o serviço não escreve no banco e não responde ao visitante. Isso espelha o `RecaptchaVerifier` (que devolve `accepted`/`rejected`/`unavailable`) e deixa o serviço testável sem banco.

Alternativa descartada: um `EventSubscriber` no `kernel.request`. Mais escondido, e precisaria saber de rota, tenant e campos — sem ganho.

### Campo de hora assinado com HMAC do `APP_SECRET`, não timestamp cru nem sessão

O parcial renderiza `wab_t` com `<timestamp>.<hmac>`, onde a assinatura é `hash_hmac('sha256', timestamp, APP_SECRET)` truncada em 16 caracteres. O servidor confere a assinatura, rejeita hora no futuro e exige 3 segundos de diferença.

- Timestamp cru seria trocável pelo robô por "agora menos 10 segundos".
- Guardar a hora na sessão seria mais forte, mas a sessão é criada a cada visita (o CSRF já cria uma) e isso daria gravação de sessão em toda página com formulário, sem ganho real contra o robô genérico.
- Hora antiga é aceita de propósito: cache de página, aba aberta de véspera e visitante lento não podem virar bloqueio. Quem limita a validade é o token CSRF, que já existe e já tem a mensagem de sessão expirada.

### Campo isca chamado `wab_website`, escondido por CSS, sem `type="hidden"`

`type="hidden"` é ignorado pelos robôs que só olham campos de formulário visíveis no DOM; um `input` de texto normal, afastado da tela, é o que a armadilha precisa. O nome leva o prefixo `wab_` justamente para o autofill do navegador não reconhecer como "site" e preencher sozinho — autofill preenchendo a isca criaria falso positivo em visitante de verdade. Acompanham `tabindex="-1"`, `aria-hidden="true"`, `autocomplete="off"` e um rótulo escondido, para teclado e leitor de tela ignorarem o campo.

Esconder com `position:absolute;left:-9999px;opacity:0` (e não `display:none`), porque alguns robôs filtram campo com `display:none`.

### Limite por IP no `cache.app`, contando só o que foi aceito

Chave `antispam_ip_<sha1(ip)>`, valor inteiro, TTL de 3600 segundos gravado no primeiro envio aceito daquele IP: é uma janela fixa de uma hora a partir do primeiro contato, não deslizante. Simples, sem dependência (`symfony/rate-limiter` precisaria de instalação e configuração) e suficiente para 3 envios/hora.

Contar só os aceitos é de propósito: se o bloqueado também contasse, um robô insistente gastaria o limite do IP e, num IP compartilhado (operadora móvel, escola, empresa), derrubaria o visitante de verdade logo depois.

O IP vem de `Request::getClientIp()`. O site roda atrás do Apache do RunCloud, sem CDN; se um dia entrar Cloudflare, será preciso configurar `trusted_proxies`, senão o IP de todos vira o do proxy e o limite passa a valer para o site inteiro. Fica anotado como risco.

### Bloqueado vai para o banco com motivo, marcado como lido

Duas colunas novas em `contact_message`, as duas nulas: `spam_reason` (50) e `source_domain` (255). Bloqueado nasce com `spam_reason` preenchido e `isRead = true`, então não entra na contagem de não lidos nem precisa de tratamento especial na contagem.

- `countUnread()` ganha `AND c.spamReason IS NULL`; a lista do `/admin/contact` filtra por `spamReason IS NULL` e, com `?bloqueados=1`, por `spamReason IS NOT NULL`.
- Tabela nova para spam foi descartada: a mesma entidade já tem tenant, data e exclusão no painel, e o volume é pequeno (262 mensagens em toda a vida do sistema até 09/11/2026).
- O e-mail não sai e nada é dito ao visitante: a resposta é a mesma do sucesso, como manda a spec.

### Domínio de origem lido do request, não do tenant

`$request->getHost()` em vez de `$tenant->getDomain()`: é o host que o visitante usou de fato. Em condições normais os dois são iguais (o match de tenant é exato), mas o host do request é o dado de auditoria certo e não some se o domínio do tenant for trocado depois.

### Ordem das conferências

CSRF → campos obrigatórios → antispam → reCAPTCHA (quando há chaves) → grava → e-mail. O antispam vem antes do reCAPTCHA porque é local e de graça: não faz sentido pagar uma chamada HTTP ao Google por um envio que a isca já reprovou.

## Risks / Trade-offs

- **Falso positivo bloqueia um cliente de verdade** → a mensagem fica gravada e visível no filtro de bloqueados do painel, com o motivo; nada se perde. As regras de conteúdo (links, cirílico) são as de maior risco e são as que mais justificam esse filtro.
- **Autofill do navegador preenche a isca** → nome com prefixo `wab_`, que nenhum heurístico de autofill reconhece, mais `autocomplete="off"`.
- **IP compartilhado estoura o limite de 3/hora** (empresa, escola, 4G) → o limite conta só envios aceitos, e três contatos por hora do mesmo IP já é bem acima do uso real destes sites; o bloqueado fica no painel.
- **`cache:clear` no deploy zera os contadores de IP** → aceito: o pior efeito é um robô ganhar 3 envios a mais logo depois de um deploy.
- **Proxy ou CDN na frente faz todo visitante virar um IP só** → hoje não há CDN; se entrar, configurar `trusted_proxies` antes, senão o limite por IP passa a valer para o site inteiro.
- **Robô feito sob medida passa por tudo** → fica para o reCAPTCHA v3, já implementado; esta mudança não o substitui.
- **O parcial muda de papel** (era só reCAPTCHA, passa a ser a proteção do contato) → ele é renomeado para `pub/_contato_protecao.html.twig` e os 7 includes passam a apontar para o nome novo, para o arquivo não mentir sobre o que faz. Um tema novo que esqueça o include fica sem os campos e cai na regra de "campo de hora ausente", ou seja, bloqueia tudo — por isso a tarefa de conferir os 7 formulários depois do deploy.

## Migration Plan

1. Migration com as duas colunas nulas: nada a converter, os 262 registros antigos ficam com `spam_reason` e `source_domain` nulos (contato aceito, origem desconhecida).
2. Deploy padrão do projeto: `git pull`, `./build.sh` (muda template), `bin/console doctrine:migrations:migrate`.
3. Conferir no HTML público de um site que os dois campos chegaram, e enviar um contato de teste de verdade (passando dos 3 segundos) para ver a mensagem no painel com o domínio de origem.
4. Rollback: `git revert` do código; as colunas podem ficar no banco sem efeito (são nulas), e o `down` da migration existe se for preciso.
