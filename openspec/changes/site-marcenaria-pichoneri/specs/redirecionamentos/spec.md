# Spec Delta

## Purpose

Permite que um site mantenha funcionando os endereços que tinha antes de vir para a plataforma, respondendo com redirecionamento permanente para o endereço novo, sem interferir nas páginas que existem.

## ADDED Requirements

### Requirement: Redirecionamento de endereço antigo
Quando um endereço pedido por GET ou HEAD não existe no site do domínio e há um redirecionamento cadastrado nesse site para o caminho pedido, o sistema SHALL responder 301 para o destino cadastrado; a comparação ignora a barra final e os parâmetros de consulta. Sem cadastro, a resposta continua sendo o 404 de sempre.

#### Scenario: Post antigo do WordPress
- **WHEN** o site tem o redirecionamento `/2023/07/13/crie-um-espaco-acolhedor` → `/blog/crie-um-espaco-acolhedor` e recebe `/2023/07/13/crie-um-espaco-acolhedor/?utm_source=x`
- **THEN** a resposta é 301 com `Location: /blog/crie-um-espaco-acolhedor`

#### Scenario: Endereço sem cadastro
- **WHEN** o site recebe um endereço que não existe e não tem redirecionamento
- **THEN** a resposta é 404, igual à de antes desta mudança

#### Scenario: Envio de formulário
- **WHEN** um POST chega num caminho que tem redirecionamento cadastrado e não tem rota
- **THEN** a resposta é o erro de sempre, sem redirecionar

### Requirement: Página existente tem prioridade
Um redirecionamento MUST NOT ser aplicado a um endereço que o site atende: se o caminho corresponde a uma página, categoria, post ou qualquer rota que responde, vale a página.

#### Scenario: Cadastro em cima de uma página
- **WHEN** o admin cadastra o redirecionamento `/p/sobre` → `/` e a página `sobre` existe
- **THEN** `/p/sobre` continua abrindo a página

### Requirement: Redirecionamento restrito ao site
Cada redirecionamento SHALL pertencer a um site e valer só no domínio dele.

#### Scenario: Mesmo caminho em outro site
- **WHEN** o site A tem o redirecionamento `/home2` → `/` e o site B recebe `/home2`
- **THEN** o site B responde 404

### Requirement: Cadastro de redirecionamentos no painel
O admin do site SHALL listar, incluir e excluir redirecionamentos em `/admin`, vendo quantas vezes cada um foi usado. O endereço antigo e o novo MUST começar com `/`; o novo MUST NOT começar com `//` nem ser igual ao antigo; o endereço antigo é único no site. Editores e revisores não têm acesso.

#### Scenario: Destino externo
- **WHEN** o admin informa o destino `//outro-site.com` ou `https://outro-site.com`
- **THEN** o cadastro é recusado com a mensagem do motivo

#### Scenario: Endereço antigo repetido
- **WHEN** o admin cadastra de novo um endereço antigo que já existe no site, com ou sem barra final
- **THEN** o cadastro é recusado

#### Scenario: Contagem de uso
- **WHEN** um redirecionamento é aplicado três vezes
- **THEN** a lista do painel mostra 3 na coluna de acessos
