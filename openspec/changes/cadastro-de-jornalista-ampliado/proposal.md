# Proposta: cadastro-de-jornalista-ampliado

## Why

O Jonas pediu em 10/11/2026 que o cadastro de jornalista do portal da AJoPE (https://ajope.wab.com.br/jornalista/cadastro) peça mais dados:

- endereço;
- estado;
- WhatsApp, no lugar do telefone;
- a casa espírita que a pessoa frequenta.

Hoje o formulário tem nome, e-mail, senha, telefone, registro profissional, veículo, cidade, minibio e foto.

## What Changes

- No cadastro público, entram **Endereço** (obrigatório), **Estado** (obrigatório, lista das 27 UFs) e **Casa espírita que frequenta** (opcional).
- O campo **Telefone** vira **WhatsApp**, obrigatório. Ele aceita números brasileiros com DDD: 10 ou 11 dígitos depois de tirar a pontuação, o código do país (+55) e o 0 de discagem à frente. O número é gravado como foi digitado.
- O perfil do jornalista (`/jornalista/perfil`) passa a editar os mesmos campos.
- A ficha do jornalista no `/admin/jornalistas` passa a mostrar esses campos.
- A exportação e a importação do site levam os campos novos.
- Endereço, estado, WhatsApp e casa espírita **não aparecem** na página pública do autor.

## Capabilities

### New Capabilities

(nenhuma)

### Modified Capabilities
- `noticias`: muda o requisito "Cadastro do jornalista candidato" (campos novos, WhatsApp no lugar do telefone) e "Perfil do jornalista" (os mesmos campos), e entra o requisito dos dados do jornalista na ficha e fora da página pública. Os dois requisitos alterados vêm do `portal-de-noticias`, que precisa ser arquivado antes.

## Impact

- **Banco:** uma migration com três colunas anuláveis em `author` (`address`, `state`, `spiritist_center`). O WhatsApp continua na coluna `phone`, sem migração de dado.
- **Código:**
  - `Author` (três campos);
  - `JournalistSignupController`, `JournalistAreaController` (perfil);
  - `TenantExporter` e `TenantImporter`.
- **Templates:**
  - `themes/noticias/journalist_signup.html.twig`, que só existe no tema `noticias`;
  - `journalist/profile.html.twig`;
  - `admin/author/show.html.twig`.
- **O campo "Casa espírita" é próprio da AJoPE**, mas aparece em todo portal do tema `noticias`. Hoje só existe esse portal.
- **Mensagem de cadastro recebido:** muda pelo change `avisos-ao-jornalista`.
