# Design

## Context

- Tenant 13 `transmai.wab.com.br`, tema cetec, Modo Landing Page; home = página 61.
- Site antigo (Divi/WordPress): cada estado é um `et_pb_toggle` com `h5` (estado) e uma `<table>` cuja primeira linha é o cabeçalho (Cidade, logo, Empresa, Resp. Técnico, Resp. Empresa, Assistência Técnica, Distribuidores). Algumas linhas têm 5 ou 6 células. A célula "Empresa" junta nome, endereço, CEP, CNPJ, e-mail, telefone, WhatsApp e site, separados por `<br>`. Estados sem revenda têm só texto.
- Escrita em produção exige aprovação do Jonas: este pedido ("já deixe cadastrados os registros, num bloco, online") é a aprovação.

## Decisions

### D1. Extração por script local

Script Python (rodado com `-I`) sobre o HTML salvo do site antigo. Para cada linha de dados, as colunas são localizadas pelo texto do cabeçalho da própria tabela (resolve as linhas com 5 ou 6 células). Célula "Empresa": 1ª linha = nome; linhas com `CNPJ`, `E-mail`, `Fone`/`Tel`/`Fax`, `WhatsApp`, `Site`/`www.` vão para os campos; o resto é endereço. Cidade em caixa normal ("Rio Branco", com "de", "da", "do" minúsculos).

HTML do registro:

```html
<h3>J. G. PACHECO &amp; CIA LTDA</h3>
<p>Rio Branco – AC<br>Rua da Dama da Noite, 795 – Bairro: Jardim Tropical<br>CEP 69901-248<br>CNPJ: 08.155.025/0001-76</p>
<p>Fone: <a href="tel:+556832241468">(68) 3224-1468</a><br>WhatsApp: <a href="https://wa.me/5568981131415">(68) 98113-1415</a><br>E-mail: <a href="mailto:…">…</a></p>
<p>Responsável: José G. Pacheco</p>
<p><strong>Assistência técnica · Distribuidor</strong></p>
```

Conferência: 85 registros, 23 UFs, 4 sem revenda, contagem de logos; 5 registros conferidos à mão contra o site antigo.

### D2. Logos

Baixar cada `img src` do site antigo para uma pasta própria no scratchpad, pela versão original quando o `src` tem sufixo de tamanho (`-300x164`) e ela existe; validar o tipo pelo conteúdo (JPG/PNG/WebP/GIF); nomear `midia_transmai_<n>.<ext>`; enviar por `scp` para `public/uploads/page_block_media/` no servidor.

### D3. Gravação

Script PHP rodado no servidor com o PHP 8.3 e o kernel `prod`: busca o tenant pelo domínio, cria a página `Distribuidores e Assistência Técnica` (`slug revendas`, `showInHeader` falso), uma seção com um bloco `text_only` (título e introdução do site antigo) e o bloco `representatives` com o `config` passado por `MapaDeRepresentantes::normalizar()` (mesma regra do painel). Para sem gravar se a página já existir. Sem SQL manual.

## Risks / Trade-offs

- [Campo extraído no lugar errado] → conferência dos totais e revisão visual; o cliente revisa depois.
- [Página fora do menu] → link enviado ao Jonas; pôr no menu é uma marcação no painel.

## Migration Plan

Backup do banco; envio dos logos; script de gravação; conferência (HTTP 200, 85 registros, logos carregando, 27 estados no mapa). Rollback: apagar a página pelo `/admin` do site.
