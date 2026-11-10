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

## 2b. Correção (10/11/2026)

- [x] 2.5 Aceitar o WhatsApp com +55 e com o 0 de discagem (design, decisão 2), e mudar a dica do campo para "Com DDD. Ex.: (16) 98179-1110". Conferir com `php -r`: "+5516981791110", "16 98179-1110", "(16) 3333-4444" e "016981791110" aceitos; "1234", "5516" e "+1 415 555 0100" recusados. No perfil local, o mesmo número salva. Publicar com backup do HEAD, `git pull` e `./build.sh` (muda template), e terminar com HTTP 200 nos 8 domínios de cliente e no ajope. (10/11/2026: o `php -r` aceita "+5516981791110", "+55 (16) 98179-1110", "16 98179-1110", "(16) 3333-4444", "016981791110" e "5516981791110", e recusa "1234", "5516", "+1 415 555 0100", "+5516" e vazio; o perfil local salvou "+5516981791110". Publicado às 20:24 UTC com a autorização do Jonas ("pode publicar"): HEAD 8d662ce anotado em `~/backups/wab-sites-HEAD-antes-20261110-2024.txt`, `git pull` até dcebe3d e `./build.sh`; no servidor, `isValidWhatsapp("+5516981791110")` dá true; HTTP 200 nos 8 domínios de cliente e no ajope; o cadastro do ajope mostra "Com DDD. Ex.: (16) 98179-1110")

## 3. Produção

- [x] 3.1 Deploy junto com `fonte-open-sans`, `tipografia-g1-no-tema-noticias` e `avisos-ao-jornalista` (escrita, com aprovação do Jonas): backup (HEAD e dump), `git pull`, `doctrine:migrations:migrate`, `./build.sh`; HTTP 200 nos 8 domínios de cliente e no ajope. (10/11/2026 19:53 UTC, com a autorização do Jonas ("crie… implemente… publique"): e2e2593 no ar; backup `~/backups/wab-sites-HEAD-antes-20261110-1952.txt` (HEAD 2cdddf5) e `wab-sites-antes-deploy-20261110-1952.sql.gz` (20 tabelas); `git pull`, migration Version20261110194600 e `./build.sh` com nice; HTTP 200 nos 8 domínios de cliente e no ajope)
- [x] 3.2 Conferir no ajope (leitura): `/jornalista/cadastro` com os campos novos, e `/autor/livia-andrade` sem dados privados. (10/11/2026: `/jornalista/cadastro` do ajope 200, com `phone`, `address`, `state` e `spiritistCenter`; `/autor/livia-andrade` sem endereço, WhatsApp nem casa espírita)

## Workflow follow-up

- Arquivar depois do `portal-de-noticias` (os requisitos alterados vêm dele).
