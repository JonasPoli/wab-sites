<?php

declare(strict_types=1);

namespace App\Agentes;

use App\Mcp\McpServer;
use App\Oauth\ClientRegistry;
use App\Oauth\TokenService;

/**
 * Documentos de descoberta de cada site em /.well-known/ (spec integracoes-para-agentes):
 * habilidades (Agent Skills Discovery 0.2.0), cartão do servidor MCP, catálogo de API
 * (RFC 9727), catálogo ARD e poppy.json. Tudo sai do tenant do pedido e só anuncia o que
 * existe neste site.
 */
final class DescobertaPorAgentes
{
    public const SKILLS = ['conhecer-o-site', 'falar-com-a-empresa'];

    /** @var array<string, string> */
    private array $cacheDeSkills = [];

    public function __construct(
        private readonly SiteAtual $site,
        private readonly PaginasDoSite $paginas,
        private readonly McpServer $mcp,
    ) {
    }

    /** Texto do SKILL.md, ou null se a habilidade não existe. */
    public function skill(string $nome): ?string
    {
        if (!\in_array($nome, self::SKILLS, true)) {
            return null;
        }

        return $this->cacheDeSkills[$nome] ??= match ($nome) {
            'conhecer-o-site' => $this->skillConhecer(),
            'falar-com-a-empresa' => $this->skillFalar(),
        };
    }

    public function skillsIndex(): array
    {
        $skills = [];
        foreach (self::SKILLS as $nome) {
            $texto = (string) $this->skill($nome);
            preg_match('/^description:\s*(.+)$/m', $texto, $m);
            $skills[] = [
                'name' => $nome,
                'type' => 'skill-md',
                'description' => trim($m[1] ?? ''),
                'url' => $this->site->url("/.well-known/agent-skills/$nome/SKILL.md"),
                'digest' => 'sha256:' . hash('sha256', $texto),
            ];
        }

        return ['$schema' => 'https://schemas.agentskills.io/discovery/0.2.0/schema.json', 'skills' => $skills];
    }

    public function serverCard(): array
    {
        $endpoint = $this->site->url('/mcp');

        return [
            'version' => '1.0',
            'protocolVersion' => McpServer::DEFAULT_PROTOCOL_VERSION,
            'serverInfo' => ['name' => $this->mcp->name(), 'title' => $this->mcp->title(), 'version' => McpServer::VERSION],
            'description' => 'Servidor MCP público e só de leitura do site ' . $this->site->tenant()->getName() . ': dados de contato, páginas em Markdown e notícias.',
            'websiteUrl' => $this->site->url('/'),
            'instructions' => $this->mcp->instructions(),
            'transport' => ['type' => 'streamable-http', 'endpoint' => $endpoint],
            'remotes' => [['type' => 'streamable-http', 'url' => $endpoint]],
            'authentication' => ['required' => false],
            'capabilities' => $this->mcp->capabilities(),
            'tools' => array_values(array_map($this->mcp->describeTool(...), $this->mcp->tools())),
            'resources' => array_map(static fn (array $r) => ['uri' => $r['uri'], 'name' => $r['name'], 'mimeType' => 'text/markdown'], $this->mcp->listResources()),
        ];
    }

    public function apiCatalog(): array
    {
        return ['linkset' => [
            [
                'anchor' => $this->site->url('/mcp'),
                'service-desc' => [['href' => $this->site->url('/.well-known/mcp/server-card.json'), 'type' => 'application/json']],
                'service-doc' => [['href' => $this->site->url('/llms.txt'), 'type' => 'text/plain']],
            ],
            [
                'anchor' => $this->site->url('/api/agentes'),
                'service-desc' => [['href' => $this->site->url('/api/agentes/openapi.json'), 'type' => 'application/vnd.oai.openapi+json']],
                'service-doc' => [['href' => $this->site->url('/auth.md'), 'type' => 'text/markdown']],
                'service-meta' => [['href' => $this->site->url('/.well-known/oauth-protected-resource/api/agentes'), 'type' => 'application/json']],
            ],
            [
                'anchor' => $this->site->url('/a2a'),
                'service-desc' => [['href' => $this->site->url('/.well-known/agent-card.json'), 'type' => 'application/json']],
                'service-doc' => [['href' => $this->site->url('/llms.txt'), 'type' => 'text/plain']],
            ],
        ]];
    }

    public function aiCatalog(): array
    {
        $host = $this->site->host();
        $nome = $this->site->tenant()->getName();
        $consultas = $this->consultas();

        return [
            'specVersion' => '1.0',
            'host' => ['displayName' => $nome, 'identifier' => 'did:web:' . $host],
            'entries' => [
                [
                    'identifier' => "urn:air:$host:server:site",
                    'displayName' => 'Servidor MCP de ' . $nome,
                    'description' => 'Dados de contato, páginas do site em Markdown e notícias, só leitura.',
                    'type' => 'application/mcp-server-card+json',
                    'url' => $this->site->url('/.well-known/mcp/server-card.json'),
                    'representativeQueries' => \array_slice($consultas, 0, 3),
                ],
                [
                    'identifier' => "urn:air:$host:agent:a2a",
                    'displayName' => 'Agente A2A de ' . $nome,
                    'description' => 'Responde em português sobre o site: contato, endereço, horário, páginas e notícias.',
                    'type' => 'application/a2a-agent-card+json',
                    'url' => $this->site->url('/.well-known/agent-card.json'),
                    'representativeQueries' => ['como falar com ' . $nome, 'qual o telefone de ' . $nome],
                ],
                [
                    'identifier' => "urn:air:$host:api:agentes",
                    'displayName' => 'API para agentes de ' . $nome,
                    'description' => 'API JSON com OAuth 2.0 (registro anônimo pelo Auth.md): perfil, páginas e notícias.',
                    'type' => 'application/vnd.oai.openapi+json',
                    'url' => $this->site->url('/api/agentes/openapi.json'),
                    'representativeQueries' => ['dados de ' . $nome . ' em JSON', 'páginas do site de ' . $nome],
                ],
                [
                    'identifier' => "urn:air:$host:skills:index",
                    'displayName' => 'Habilidades para agentes de ' . $nome,
                    'description' => 'Como conhecer o site e falar com a empresa.',
                    'type' => 'application/json',
                    'url' => $this->site->url('/.well-known/agent-skills/index.json'),
                    'representativeQueries' => ['contato de ' . $nome, 'o que ' . $nome . ' oferece'],
                ],
            ],
        ];
    }

    /** Personal Agent Protocol: só a descoberta do site. */
    public function poppy(): array
    {
        return [
            'protocol_version' => '0.1',
            'organization' => ['name' => $this->site->tenant()->getName(), 'domain' => (string) preg_replace('/^www\./', '', $this->site->host())],
            'web' => new \stdClass(),
        ];
    }

    /** @return list<string> de 2 a 5 perguntas típicas sobre o site */
    private function consultas(): array
    {
        $nome = $this->site->tenant()->getName();
        $lista = ['o que ' . $nome . ' oferece', 'contato e endereço de ' . $nome];
        foreach ($this->paginas->todas($this->site->tenant()) as $item) {
            if ('pagina' === $item['tipo'] && \count($lista) < 5) {
                $lista[] = $item['titulo'] . ' — ' . $nome;
            }
        }

        return $lista;
    }

    private function skillConhecer(): string
    {
        $tenant = $this->site->tenant();
        $nome = $tenant->getName();
        $base = $this->site->base();
        $dados = $this->site->dados();
        $ferramentas = implode(', ', array_map(static fn (string $n) => '`' . $n . '`', array_keys($this->mcp->tools())));

        $texto = "---\nname: conhecer-o-site\ndescription: Descobrir o que " . $nome . ' faz e o que o site ' . $this->site->host()
            . " publica, lendo as páginas nos formatos feitos para agentes.\n---\n\n"
            . '# Conhecer ' . $nome . "\n\n"
            . (isset($dados['descricao']) ? $dados['descricao'] . "\n\n" : '')
            . "## Onde ler, do mais rápido ao mais completo\n\n"
            . '1. **Servidor MCP** em `' . $base . '/mcp` (Streamable HTTP, sem login, só leitura). Ferramentas: ' . $ferramentas . '. Cartão: ' . $base . "/.well-known/mcp/server-card.json\n"
            . '2. **Índice do site**: ' . $base . "/llms.txt\n"
            . '3. **Uma página em Markdown**: o endereço da página com `.md` (a home é `/index.md`) ou a própria URL com `Accept: text/markdown`.' . "\n"
            . '4. **O site inteiro num arquivo**: ' . $base . "/llms-full.txt\n\n"
            . "## Páginas\n\n";
        foreach ($this->paginas->todas($tenant) as $item) {
            $texto .= '- [' . $item['titulo'] . '](' . $base . PaginasDoSite::enderecoMarkdown($item['caminho']) . ')'
                . ('' !== $item['descricao'] ? ': ' . $item['descricao'] : '') . "\n";
        }

        return $texto . "\n## Ao responder a alguém\n\n- Cite a página pela URL sem `.md`.\n- Para falar com a empresa, use a habilidade `falar-com-a-empresa`.\n";
    }

    private function skillFalar(): string
    {
        $nome = $this->site->tenant()->getName();
        $dados = $this->site->dados();

        return "---\nname: falar-com-a-empresa\ndescription: Orientar uma pessoa a entrar em contato com " . $nome
            . " (telefone, WhatsApp, e-mail, endereço, horário ou formulário do site).\n---\n\n"
            . '# Falar com ' . $nome . "\n\n"
            . "Este site não recebe mensagens de agentes: quem envia é a pessoa, pelo canal que preferir.\n\n"
            . "## Canais\n\n" . DadosDoSite::emMarkdown($dados) . "\n\n"
            . "## Formulário do site\n\n"
            . 'O formulário fica em ' . $dados['formulario'] . ' e pede nome, e-mail, telefone (opcional) e a mensagem. '
            . 'Num navegador com WebMCP, ele aparece como ferramenta `enviar_contato`; a pessoa confere e envia.' . "\n\n"
            . "## Antes de indicar o contato\n\n- Ajude a pessoa a dizer o que precisa, com prazo e referências.\n- Não envie dados pessoais por conta própria.\n";
    }
    // ------------------------------------------------------------------
    // Autenticação de agentes e A2A (spec autenticacao-de-agentes)

    /** Metadados RFC 8414 com o bloco agent_auth do Auth.md. */
    public function authorizationServer(): array
    {
        $issuer = $this->site->base();

        return [
            'issuer' => $issuer,
            'authorization_endpoint' => $this->site->url('/oauth/authorize'),
            'token_endpoint' => $this->site->url('/oauth/token'),
            'jwks_uri' => $this->site->url('/oauth/jwks.json'),
            'registration_endpoint' => $this->site->url('/oauth/register'),
            'revocation_endpoint' => $this->site->url('/oauth/revoke'),
            'scopes_supported' => [TokenService::SCOPE],
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'client_credentials', TokenService::GRANT_JWT_BEARER],
            'token_endpoint_auth_methods_supported' => ClientRegistry::AUTH_METHODS,
            'revocation_endpoint_auth_methods_supported' => ['none'],
            'code_challenge_methods_supported' => ['S256'],
            'authorization_response_iss_parameter_supported' => true,
            'service_documentation' => $this->site->url('/auth.md'),
            'ui_locales_supported' => ['pt-BR'],
            'resource' => $this->site->url('/api/agentes'),
            'authorization_servers' => [$issuer],
            'bearer_methods_supported' => ['header'],
            'agent_auth' => [
                'skill' => $this->site->url('/auth.md'),
                // Nomes da versão atual do Auth.md e da 0.1 (register_uri e claim_uri), que o
                // isitagentready.com ainda confere.
                'identity_endpoint' => $this->site->url('/agent/identity'),
                'register_uri' => $this->site->url('/agent/identity'),
                'claim_endpoint' => $this->site->url('/agent/identity/claim'),
                'claim_uri' => $this->site->url('/agent/identity/claim'),
                'identity_types_supported' => ['anonymous'],
                'anonymous' => ['credential_types_supported' => ['access_token'], 'scopes' => [TokenService::SCOPE], 'claim_supported' => false],
                'events_supported' => [],
            ],
        ];
    }

    /** Metadados RFC 9728: na raiz o site inteiro; no sufixo /api/agentes, a API. */
    public function protectedResource(string $sufixo): array
    {
        return [
            'resource' => $this->site->url($sufixo),
            'resource_name' => 'API para agentes de ' . $this->site->tenant()->getName(),
            'resource_documentation' => $this->site->url('/auth.md'),
            'authorization_servers' => [$this->site->base()],
            'scopes_supported' => [TokenService::SCOPE],
            'bearer_methods_supported' => ['header'],
            'resource_signing_alg_values_supported' => ['RS256'],
        ];
    }

    /** Cartão A2A com os campos do protocolo 1.0 e os da 0.3 (url, preferredTransport). */
    public function agentCard(): array
    {
        $endpoint = $this->site->url('/a2a');
        $nome = $this->site->tenant()->getName();

        return [
            'name' => 'Agente de ' . $nome,
            'description' => 'Agente do site ' . $this->site->host() . ': responde em português sobre ' . $nome . ' (contato, endereço, horário, páginas e notícias). Só leitura; não envia mensagens.',
            'supportedInterfaces' => [
                ['url' => $endpoint, 'protocolBinding' => 'JSONRPC', 'protocolVersion' => '1.0'],
                ['url' => $endpoint, 'protocolBinding' => 'JSONRPC', 'protocolVersion' => '0.3'],
            ],
            'provider' => ['organization' => $nome, 'url' => $this->site->url('/')],
            'version' => '1.0.0',
            'documentationUrl' => $this->site->url('/llms.txt'),
            'capabilities' => ['streaming' => false, 'pushNotifications' => false, 'extendedAgentCard' => false, 'stateTransitionHistory' => false],
            'defaultInputModes' => ['text/plain'],
            'defaultOutputModes' => ['text/plain', 'application/json'],
            'skills' => [
                ['id' => 'contato', 'name' => 'Contato', 'description' => 'Telefone, WhatsApp, e-mail, endereço, horário e formulário do site.', 'tags' => ['contato', 'endereço', 'horário'], 'examples' => ['Como falo com ' . $nome . '?', 'Qual o endereço?']],
                ['id' => 'paginas', 'name' => 'Páginas do site', 'description' => 'Encontra a página que trata do assunto perguntado e devolve o resumo e o link.', 'tags' => ['conteúdo'], 'examples' => ['O que ' . $nome . ' oferece?', 'Leia a página inicial']],
                ['id' => 'noticias', 'name' => 'Notícias e posts', 'description' => 'Lista as notícias ou posts mais recentes do site.', 'tags' => ['notícias', 'blog'], 'examples' => ['Quais as últimas notícias?']],
            ],
            'protocolVersion' => '0.3.0',
            'url' => $endpoint,
            'preferredTransport' => 'JSONRPC',
            'additionalInterfaces' => [['url' => $endpoint, 'transport' => 'JSONRPC']],
        ];
    }

    /** OpenAPI 3.1 da API para agentes. */
    public function openApi(): array
    {
        $json = fn (string $d) => ['description' => $d, 'content' => ['application/json' => ['schema' => ['type' => 'object']]]];
        $erros = ['401' => $json('Sem token ou token inválido; WWW-Authenticate traz resource_metadata.'), '403' => $json('Token sem o escopo api.read.')];
        $get = fn (string $id, string $resumo, array $extra = []) => ['get' => $extra + ['operationId' => $id, 'summary' => $resumo, 'responses' => ['200' => $json('OK')] + $erros]];
        $escopo = [TokenService::SCOPE => 'Ler os dados públicos do site'];

        return [
            'openapi' => '3.1.0',
            'info' => ['title' => 'API para agentes de ' . $this->site->tenant()->getName(), 'version' => '1.0.0', 'description' => 'Dados públicos do site em JSON, para agentes de IA. Como obter o token: ' . $this->site->url('/auth.md')],
            'servers' => [['url' => $this->site->base()]],
            'security' => [['oauth2' => [TokenService::SCOPE]]],
            'components' => ['securitySchemes' => ['oauth2' => [
                'type' => 'oauth2',
                'description' => 'Agentes sem cliente registrado usam o registro anônimo do Auth.md (POST /agent/identity e concessão jwt-bearer).',
                'flows' => [
                    'clientCredentials' => ['tokenUrl' => $this->site->url('/oauth/token'), 'scopes' => $escopo],
                    'authorizationCode' => ['authorizationUrl' => $this->site->url('/oauth/authorize'), 'tokenUrl' => $this->site->url('/oauth/token'), 'scopes' => $escopo],
                ],
            ]]],
            'paths' => [
                '/api/agentes' => $get('indice', 'Índice da API'),
                '/api/agentes/perfil' => $get('perfil', 'Dados e contato do site'),
                '/api/agentes/paginas' => $get('paginas', 'Lista as páginas ou devolve uma em Markdown', ['parameters' => [['name' => 'caminho', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']]]]),
                '/api/agentes/noticias' => $get('noticias', 'Notícias ou posts do site', ['parameters' => [['name' => 'busca', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']]]]),
            ],
        ];
    }

    /** Texto do /auth.md (perfil Auth.md), com H1 que contém "auth.md". */
    public function authMd(): string
    {
        $b = $this->site->base();
        $nome = $this->site->tenant()->getName();
        $jwt = TokenService::GRANT_JWT_BEARER;

        return <<<MD
# auth.md de {$nome}

Você é um agente. Este site aceita **registro de agentes**: descobrir → registrar → trocar por um access_token → chamar a API → revogar quando terminar.

> For agents: this document is in Brazilian Portuguese. Endpoints and field names follow the Auth.md profile (github.com/workos/auth.md), RFC 8414, RFC 9728, RFC 7591, RFC 7523 and RFC 7009.

A API dá os dados públicos de {$nome} em JSON: contato, páginas em Markdown e notícias. Escopo único: `api.read`, só leitura. Não há contas de usuário: o registro anônimo já dá o acesso completo.

Para só ler o site, não precisa de token: use o servidor MCP em {$b}/mcp, o agente A2A em {$b}/a2a ou o índice {$b}/llms.txt.

## Passo 1 — Descobrir

- Metadados do recurso (RFC 9728): {$b}/.well-known/oauth-protected-resource/api/agentes
- Metadados do servidor de autorização (RFC 8414, com `agent_auth`): {$b}/.well-known/oauth-authorization-server
- Uma chamada à API sem token responde 401 com `WWW-Authenticate: Bearer resource_metadata="…"`.

## Passo 2 — Registro anônimo

```http
POST {$b}/agent/identity
Content-Type: application/json

{ "type": "anonymous" }
```

A resposta traz `identity_assertion` (JWT do site, válido por 30 dias). Os métodos `identity_assertion` e `service_auth` não estão habilitados. Não existe posse (claim): `{$b}/agent/identity/claim` responde `claim_not_supported`.

## Passo 3 — Trocar a asserção por um access_token

```http
POST {$b}/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type={$jwt}&assertion=<identity_assertion>
```

Resposta: `{ "access_token": "<JWT>", "token_type": "Bearer", "expires_in": 3600, "scope": "api.read" }`. O token só vale neste domínio. Para renovar, repita este passo; `invalid_grant` significa asserção vencida: volte ao passo 2.

Também há registro de cliente (`POST {$b}/oauth/register`, RFC 7591), `client_credentials` e `authorization_code` com PKCE S256 (consentimento em `{$b}/oauth/authorize`). Chaves públicas: {$b}/oauth/jwks.json.

## Passo 4 — Usar o access_token

Cabeçalho `Authorization: Bearer <access_token>`.

| Endereço | O que devolve |
|---|---|
| `GET {$b}/api/agentes` | índice da API |
| `GET {$b}/api/agentes/perfil` | contato, endereço e horário |
| `GET {$b}/api/agentes/paginas` | lista das páginas; com `?caminho=/` devolve uma em Markdown |
| `GET {$b}/api/agentes/noticias?busca=…` | notícias ou posts |

Descrição OpenAPI 3.1: {$b}/api/agentes/openapi.json

## Passo 5 — Revogar

`POST {$b}/oauth/revoke` com `token=<access_token>`. Responde 200 sempre (RFC 7009).

## Erros

| Código | Onde | O que fazer |
|---|---|---|
| `invalid_request` | `/agent/identity`, `/oauth/*` | corrija o corpo |
| `issuer_not_enabled`, `service_auth_not_enabled` | `/agent/identity` | use `{"type": "anonymous"}` |
| `claim_not_supported` | `/agent/identity/claim` | siga sem posse |
| `invalid_grant` | `/oauth/token` | asserção ou código inválido, vencido ou já usado |
| 401 `invalid_token` | `/api/agentes/*` | peça um token novo |

MD;
    }
}
