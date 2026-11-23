# Tasks

> Leia antes: `design.md` desta mudança. Depende de `imagem-na-celula-da-sanfona` e `bloco-mapa-de-representantes` publicados.

## 1. Dados

- [x] 1.1 Extrair os registros do HTML do site antigo (D1) para `representantes.json` no scratchpad; verificar: 85 registros, 23 UFs, 4 sem revenda (AP, MT, PI, RR), e 5 registros conferidos à mão
- [x] 1.2 Baixar os logos (D2) para uma pasta própria e validar o tipo de cada arquivo; verificar a contagem contra os registros com logo

## 2. Conferência local

- [x] 2.1 Criar a mesma página no banco local (`cetec.localhost`, slug `revendas`) pelo script de gravação; conferir no navegador em 1280 e 390 px

## 3. Produção (escrita, pedida pelo Jonas)

- [ ] 3.1 Backup do banco de produção
- [ ] 3.2 Enviar os logos e rodar o script de gravação (D3) no servidor
- [ ] 3.3 Conferência: `https://transmai.wab.com.br/p/revendas` responde 200, com 85 registros, logos carregando e 27 estados no mapa; a home continua 200

## 4. Registro

- [ ] 4.1 Com a confirmação do Jonas, registrar no ticket 13505 (cliente 213) e lançar as horas
