# Proposal

## Why

A Transmai (cliente WAB 213, ticket 13505 "Novo site Trasmai") está ganhando site novo em `transmai.wab.com.br` (tenant 13, tema cetec). A relação de revendas e assistências do site antigo (https://www.transmai.com.br/revenda/) precisa ir para o site novo no formato do bloco "Mapa de representantes", com os logos, para o cliente ver.

## What Changes

- Página nova `/p/revendas` no tenant 13 com um bloco "Mapa de representantes" já preenchido: título "Distribuidores e Assistência Técnica", chamada e texto de introdução do site antigo.
- Um registro por revenda do site antigo (85 em 23 estados): logo à esquerda e texto formatado com a empresa em `h3`, cidade, endereço, CNPJ, e-mail, telefones, WhatsApp e site como links, responsáveis e tipos de atendimento (assistência técnica, distribuidor).
- Estados sem revenda (Amapá, Mato Grosso, Piauí e Roraima) usam a mensagem "Entre em contato com a EMAI…" do site antigo.
- Logos baixados do site antigo (autorizado pelo Jonas) e enviados para `public/uploads/page_block_media/` em produção.
- Nada muda na home nem na sanfona da seção 97.

## Capabilities

### New Capabilities

Nenhuma.

### Modified Capabilities

Nenhuma: é conteúdo de um site, sem mudança de comportamento do sistema (`skip_specs: true`).

## Impact

- Produção (escrita, pedida pelo Jonas): uma página, uma seção e dois blocos (texto de introdução e mapa) no tenant 13; 49 imagens em `public/uploads/page_block_media/`. Backup do banco antes.
- Depende de `imagem-na-celula-da-sanfona` e `bloco-mapa-de-representantes` publicados.
- Registro no ticket 13505 do cliente 213 só com confirmação do Jonas (a resposta vai por e-mail ao cliente).
