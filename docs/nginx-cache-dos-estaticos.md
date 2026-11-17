# Cache dos estáticos no Nginx (servidor wabsites)

Trecho para colar no painel do RunCloud, em **Web Application → app-wab-sites → Nginx Config**.

## Por que

`/assets` e `/media/cache` têm impressão digital de conteúdo no nome do arquivo: quando o
conteúdo muda, o nome muda. Servir esses arquivos com 30 dias, como está hoje, faz o
visitante que volta baixar de novo o que não mudou. `/uploads` é diferente — o nome é
mantido quando o cliente troca a imagem pelo painel —, então fica em 30 dias.

> [!NOTE]
> A auditoria `uses-long-cache-ttl` do Lighthouse tem **peso 0** na nota de desempenho: isso
> aqui não muda a nota, melhora a experiência de quem volta ao site.

## Trecho

```nginx
# Conteúdo com impressão digital no nome: 1 ano, imutável.
location ^~ /assets/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
    try_files $uri =404;
}

location ^~ /media/cache/ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
    try_files $uri /index.php$is_args$args;
}

# Imagens enviadas pelo painel: o nome não muda quando o cliente troca o arquivo.
location ^~ /uploads/ {
    expires 30d;
    add_header Cache-Control "public";
    access_log off;
    try_files $uri =404;
}
```

> [!IMPORTANT]
> O `try_files` de `/media/cache/` precisa cair no `index.php`: enquanto a miniatura não
> estiver gravada, quem responde é o LiipImagine. Depois do `build.sh`, que chama
> `app:miniaturas:aquecer`, o arquivo já existe e o Nginx serve direto.

## Como conferir depois

```bash
curl -sI https://marapossi.com.br/assets/styles/app-<hash>.css | grep -i cache-control
# cache-control: public, immutable
# expires: <daqui a um ano>
```

## Por que não foi aplicado pelo SSH

O usuário `runcloud` não tem `sudo` e não lê `/etc/nginx-rc/conf.d/`. A alteração sai pelo
painel do RunCloud ou por um usuário com root.
