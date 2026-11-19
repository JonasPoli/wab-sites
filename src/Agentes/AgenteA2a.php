<?php

declare(strict_types=1);

namespace App\Agentes;

use App\Mcp\McpServer;

/**
 * O "cérebro" do agente A2A de cada site (design D5): classifica a pergunta por palavras e
 * responde com as ferramentas do servidor MCP. Sem modelo de linguagem, sem estado e sem
 * escrita: a resposta é sempre o dado público do site, com os links.
 */
final class AgenteA2a
{
    private const CONTATO = '/contato|telefone|fone|whats|zap|e-?mail|endereco|onde fica|localiza|horario|atendimento|funciona|falar com|ligar|orcament|preco|valor|quanto custa/';
    private const NOTICIAS = '/noticia|novidade|ultimas|post|blog|artigo|publica/';
    private const OFERTA = '/o que (voces|a empresa|o site|ele|ela)? ?(faz|fazem|oferece|oferecem|vende|vendem)|servico|produto|sobre (a|o) /';
    private const PALAVRAS_VAZIAS = ['como', 'qual', 'quais', 'onde', 'quando', 'sobre', 'para', 'pela', 'pelo', 'voces', 'esta', 'este', 'essa', 'esse', 'site', 'pagina', 'favor', 'quero', 'saber', 'tenho', 'fazer'];

    public function __construct(
        private readonly McpServer $mcp,
        private readonly PaginasDoSite $paginas,
        private readonly SiteAtual $site,
    ) {
    }

    /** @return array{text: string, data: array} */
    public function responder(string $pergunta): array
    {
        $q = self::normalizar($pergunta);
        $partes = [];
        $dados = [];
        $tenant = $this->site->tenant();
        $todas = $this->paginas->todas($tenant);

        // Caminho de página citado na pergunta ("leia /p/sobre")
        if (preg_match_all('#(/[a-z0-9/_-]*[a-z0-9_-])(?:\.md)?#i', $pergunta, $m)) {
            foreach ($m[1] as $caminho) {
                $item = $this->paginas->porCaminho($tenant, $caminho);
                if (null !== $item && '/' !== $item['caminho']) {
                    $partes[] = $this->ferramenta('ler_pagina', ['caminho' => $item['caminho']])->text;
                    $dados['pagina'] = $this->link($item);
                    break;
                }
            }
        }

        if (preg_match(self::NOTICIAS, $q)) {
            $r = $this->ferramenta('listar_noticias');
            $partes[] = $r->text;
            $dados += $r->structured ?? [];
        }

        if (preg_match(self::OFERTA, $q)) {
            $d = $this->site->dados();
            $texto = $d['nome'] . (isset($d['descricao']) ? ': ' . $d['descricao'] : '.') . "\n\nPáginas do site:";
            foreach (\array_slice($todas, 0, 12) as $item) {
                $texto .= "\n- " . $item['titulo'] . ': ' . $this->site->url($item['caminho']);
            }
            $partes[] = $texto;
        }

        // Página cujo título combina com a pergunta
        if ([] === $partes && null !== $melhor = $this->paginaParecida($q, $todas)) {
            $partes[] = $melhor['titulo'] . ('' !== $melhor['descricao'] ? ': ' . $melhor['descricao'] : '')
                . "\nPágina: " . $this->site->url($melhor['caminho']) . ' (Markdown: ' . $this->site->url(PaginasDoSite::enderecoMarkdown($melhor['caminho'])) . ')';
            $dados['pagina'] = $this->link($melhor);
        }

        if (preg_match(self::CONTATO, $q)) {
            if (preg_match('/orcament|preco|valor|quanto custa/', $q)) {
                $partes[] = 'Valores e orçamentos são tratados diretamente com a empresa, pelos canais abaixo.';
            }
            $r = $this->ferramenta('dados_do_site');
            $partes[] = $r->text;
            $dados['contato'] = $r->structured;
        }

        if ([] === $partes) {
            $partes[] = 'Sou o agente do site de ' . $tenant->getName() . '. Respondo sobre contato (telefone, WhatsApp, e-mail, endereço e horário), as páginas do site'
                . ('noticias' === $tenant->getTheme() || $tenant->hasBlog() ? ' e as notícias' : '') . '.'
                . "\n\nExemplos: \"Como falo com vocês?\", \"O que a empresa oferece?\", \"Leia a página inicial\"."
                . "\n\nÍndice do site: " . $this->site->url('/llms.txt');
        }

        return ['text' => implode("\n\n", $partes), 'data' => $dados];
    }

    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto);
        if (class_exists(\Transliterator::class)) {
            $texto = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC')?->transliterate($texto) ?? $texto;
        }

        return $texto;
    }

    private function paginaParecida(string $q, array $todas): ?array
    {
        $palavras = array_filter(preg_split('/[^a-z0-9]+/', $q) ?: [], fn ($p) => \strlen($p) >= 4 && !\in_array($p, self::PALAVRAS_VAZIAS, true));
        if ([] === $palavras) {
            return null;
        }
        $melhor = null;
        $pontos = 0;
        foreach ($todas as $item) {
            $alvo = self::normalizar($item['titulo'] . ' ' . $item['descricao']);
            $n = 0;
            foreach ($palavras as $p) {
                $n += str_contains($alvo, $p) ? 1 : 0;
            }
            if ($n > $pontos) {
                $pontos = $n;
                $melhor = $item;
            }
        }

        return $melhor;
    }

    private function link(array $item): array
    {
        return ['titulo' => $item['titulo'], 'url' => $this->site->url($item['caminho']), 'markdown' => $this->site->url(PaginasDoSite::enderecoMarkdown($item['caminho']))];
    }

    private function ferramenta(string $nome, array $argumentos = []): \App\Mcp\Tool\ToolResult
    {
        return $this->mcp->tools()[$nome]->call($argumentos);
    }
}
