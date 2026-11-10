# Tarefas: cadastro-de-jornalista-ampliado

## 1. Dados

- [x] 1.1 Acrescentar `address`, `state`, `spiritistCenter` e a constante `STATES` (27 UFs) a `Author`; gerar a migration por `doctrine:migrations:diff` (só três `ADD` anuláveis em `author`) e aplicar em localhost. Conferir com `doctrine:schema:validate`. (10/11/2026: `Version20261110194600` só com os três `ADD` anuláveis em `author`, aplicada localmente; schema em sincronia)

## 2. Telas

- [x] 2.1 Cadastro: campos novos, WhatsApp no lugar do telefone, validação e gravação. Conferir em `noticias.localhost/jornalista/cadastro`: (10/11/2026, Playwright em `noticias.localhost`: envio completo gravou WhatsApp "(16) 99999-1234", endereço, SP e a casa espírita, com o autor pendente; WhatsApp "1234", estado "XX" e endereço vazio voltaram com as mensagens e não gravaram nada)
  - envio completo grava os campos;
  - WhatsApp "1234" volta com "Informe o WhatsApp com DDD.";
  - estado "XX" volta com "Escolha o estado.";
  - endereço vazio volta com erro;
  - nada é gravado nos casos de erro.
- [x] 2.2 Perfil (`/jornalista/perfil`) com os mesmos campos e regras. Conferir com a jornalista aprovada das fixtures: troca de estado de SP para MG gravada; WhatsApp inválido recusado. (10/11/2026: a jornalista aprovada das fixtures gravou SP e depois MG, endereço e casa espírita; o WhatsApp "12" voltou com "Informe o WhatsApp com DDD." e o número antigo ficou)
- [x] 2.3 Ficha no `/admin/jornalistas/{id}` com WhatsApp, endereço, estado e casa espírita. Conferir na ficha do cadastro da 2.1, e que `/autor/{slug}` não mostra nenhum desses dados. (10/11/2026: a ficha `/admin/jornalistas/62` mostra WhatsApp, endereço, "São Paulo" e a casa espírita; `/autor/ana-souza` não tem endereço, estado, WhatsApp nem casa espírita)
- [x] 2.4 Exportação e importação com as chaves novas. Conferir exportando `noticias.localhost` e lendo o `metadata.json` (as chaves aparecem com os valores da 2.1). (10/11/2026: o ZIP de `noticias.localhost` traz `address`, `state` e `spiritistCenter` nos dois autores preenchidos; a importação lê as mesmas chaves, e um ZIP antigo fica com elas vazias)

## 3. Produção

- [ ] 3.1 Deploy junto com `fonte-open-sans`, `tipografia-g1-no-tema-noticias` e `avisos-ao-jornalista` (escrita, com aprovação do Jonas): backup (HEAD e dump), `git pull`, `doctrine:migrations:migrate`, `./build.sh`; HTTP 200 nos 8 domínios de cliente e no ajope.
- [ ] 3.2 Conferir no ajope (leitura): `/jornalista/cadastro` com os campos novos, e `/autor/livia-andrade` sem dados privados.

## Workflow follow-up

- Arquivar depois do `portal-de-noticias` (os requisitos alterados vêm dele).
