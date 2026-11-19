# Tasks

## 1. Conteúdo para agentes

- [x] 1.1 Criar `App\Agentes\PaginasDoSite` a partir da lógica do sitemap e fazer o `SitemapController` usá-lo; conferir que o sitemap local dos tenants cetec, noticias e atelie não mudou
- [x] 1.2 Robots com grupos de IA e Content-Signal (D3); conferir local em dois tenants e no domínio sem tenant
- [x] 1.3 Markdown das páginas (`.md` e negociação por `Accept`), `llms.txt`, `llms-full.txt` e cabeçalhos `Link`; conferir local home, página, categoria, notícia e 404 de outro tenant

## 2. Integrações

- [x] 2.1 Servidor MCP em `/mcp` com as quatro ferramentas e recursos; conferir com `initialize`, `tools/list` e `tools/call` locais em dois tenants
- [x] 2.2 Habilidades, cartão do servidor, catálogo de API, ai-catalog e poppy; WebMCP (parcial comum nos cinco temas e atributos nos formulários); conferir digest, CORS e endereços

## 3. Autenticação e A2A

- [x] 3.1 Porte do OAuth com o domínio no issuer (D4), `/auth.md`, metadados RFC 8414 e 9728 e API `/api/agentes` com OpenAPI; conferir o fluxo anônimo local e o token recusado em outro tenant
- [x] 3.2 Agente A2A e cartão (D5); conferir perguntas de contato e de página e os métodos sem suporte

## 4. Publicação

- [x] 4.1 Deploy (escrita): git pull, `./build.sh`; HTTP 200 dos 10 domínios
- [x] 4.2 Nginx (escrita, root no wabsites): backup, arquivo do D7, `nginx-rc -t`, recarregar; documentos em 200 nos 10 domínios, `acme-challenge` 404, `.env` 403
- [x] 4.3 Medir os 10 domínios no isitagentready.com e rodar o fluxo de ponta a ponta em todos

## 5. DNS

- [x] 5.1 Zonas `_agents.<domínio>` dos 10 sites (escrita, root no wab2): backup, criação, delegação e DS nas zonas pai, validação com âncora
- [x] 5.2 DNSSEC das 8 zonas de cliente (escrita, root no wab2): backup, delegação de subzonas, assinatura, teste com âncora, vigia e reassinatura estendidos; DS no registro.br (Jonas para abifer, pkwy, topdrim, transterraararaquara e cemporcentoexpresso; pedido aos clientes de marapossi, imperiodainformatica e petvida)
- [ ] 5.3 Medir de novo; a cada site em 100, print marcado e resposta no ticket dele; registrar em docs e na skill `agentready-100`
