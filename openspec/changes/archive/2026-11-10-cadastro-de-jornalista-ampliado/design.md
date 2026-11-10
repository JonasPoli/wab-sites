# Design: cadastro-de-jornalista-ampliado

## Context

Motivação em `proposal.md`; requisitos em `specs/noticias/spec.md`.

- `Author` tem hoje `phone` (40), `professionalRegistration`, `outlet`, `city` (120) e `bio`.
- O cadastro (`JournalistSignupController`) lê os campos à mão, sem Symfony Form, e grava usuário e autor numa transação.
- O perfil (`JournalistAreaController::profile`) segue o mesmo padrão.

## Decisions

1. **Colunas novas e WhatsApp em `phone`.** Entram `address` (255), `state` (2) e `spiritist_center` (150), todas anuláveis, por `doctrine:migrations:diff`. O WhatsApp fica em `phone`, porque o dado é o mesmo (um número de contato) e assim não há migração de dado. Só o rótulo muda nas telas. A ficha e a exportação continuam lendo `phone`.
   - **Alternativa descartada:** renomear a coluna para `whatsapp`. Isso exige migration de rename e muda a chave da exportação, sem ganho para quem usa.
2. **Validação.**
   - **WhatsApp:** `preg_replace('/\D/', '', …)`, sem o `55` à frente quando sobram 12 ou 13 dígitos (código do país, por exemplo "+5516981791110") e sem o `0` à frente quando sobram 11 ou 12 (discagem, por exemplo "016 98179-1110"), precisa dar 10 ou 11 dígitos. O valor é gravado como foi digitado. A regra do código do país foi acrescentada em 10/11/2026, depois que o Jonas não conseguiu salvar "+5516981791110" no perfil.
   - **Estado:** precisa ser uma das 27 UFs, numa constante em `Author::STATES` (sigla → nome), que também monta o `<select>`.
   - **Endereço:** obrigatório, até 255 caracteres.
   - **Casa espírita:** opcional, até 150 caracteres.
   - **Usuários já cadastrados:** quem já existe sem esses dados continua válido. O perfil só exige os campos ao salvar.
3. **Telas.**
   - **Cadastro (só no tema `noticias`):** WhatsApp no lugar do telefone, com `inputmode="tel"`. Endereço numa linha inteira, e cidade e estado lado a lado. A casa espírita fica depois do veículo.
   - **Perfil e ficha:** os mesmos campos.
   - **Página do autor:** continua mostrando só veículo, cidade e minibio.
4. **Exportação e importação.** As chaves novas são `address`, `state` e `spiritistCenter`. Um ZIP antigo importa com elas vazias.
5. **Isolamento.** O cadastro grava no tenant do domínio (`TenantContext`), e o perfil só altera o autor do usuário logado. Nada muda no isolamento.

## Risks / Trade-offs

- **[Formulário mais longo]** O cadastro fica com mais campos, mas os campos novos ficam juntos dos de contato.
- **[Endereço é dado pessoal]** Ele fica fora da página pública, e o aceite de uso dos dados já cobre "a análise do meu cadastro".

## Migration Plan

1. Local: migration, código e conferência em `noticias.localhost`.
2. Produção (escrita, com aprovação do Jonas): deploy junto com os outros changes, com backup, `git pull`, `doctrine:migrations:migrate` e `./build.sh`.
