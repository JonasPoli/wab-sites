<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Imagens enviadas "na hora" pelo painel para blocos que guardam os dados em JSON
 * (imagem na célula da sanfona, logo do mapa de representantes — changes
 * imagem-na-celula-da-sanfona e bloco-mapa-de-representantes).
 *
 * Os arquivos ficam em public/uploads/page_block_media/ e são citados pelo nome no
 * `config` do bloco; `arquivosDoConfig()` lista esses nomes para a exportação do site.
 */
final class MidiaDoBloco
{
    public const PASTA = 'uploads/page_block_media';
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * Grava a imagem e devolve o nome do arquivo.
     *
     * @throws \InvalidArgumentException com a mensagem para o admin quando o arquivo é recusado
     */
    public function gravar(UploadedFile $arquivo): string
    {
        if (!$arquivo->isValid()) {
            throw new \InvalidArgumentException('A imagem não foi enviada: ' . $arquivo->getErrorMessage());
        }
        if ($arquivo->getSize() > self::MAX_BYTES) {
            throw new \InvalidArgumentException('A imagem precisa ter até 5 MB.');
        }
        $tipo = (string) $arquivo->getMimeType(); // detectado pelo conteúdo
        $ext = self::TIPOS[$tipo] ?? null;
        if ($ext === null || @getimagesize($arquivo->getPathname()) === false) {
            throw new \InvalidArgumentException('Só são aceitas imagens JPG, PNG, WebP ou GIF.');
        }
        $nome = 'midia_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
        $arquivo->move($this->projectDir . '/public/' . self::PASTA, $nome);

        return $nome;
    }

    /** Nome de arquivo aceito no `config`: só letras, números, ponto, hífen e sublinhado. */
    public static function nomeValido(mixed $nome): ?string
    {
        return is_string($nome) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,150}$/', $nome) && !str_contains($nome, '..') ? $nome : null;
    }

    /**
     * Arquivos desta pasta citados no `config` de um bloco: imagens das células da
     * sanfona (`panels[].table.rows[][].image`) e logos do mapa de representantes (`reps[].image`).
     *
     * @return list<string>
     */
    public static function arquivosDoConfig(mixed $config): array
    {
        if (!is_array($config)) {
            return [];
        }
        $nomes = [];
        foreach ((array) ($config['panels'] ?? []) as $item) {
            foreach ((array) ($item['table']['rows'] ?? []) as $linha) {
                foreach ((array) $linha as $celula) {
                    if (is_array($celula) && ($n = self::nomeValido($celula['image'] ?? null))) {
                        $nomes[] = $n;
                    }
                }
            }
        }
        foreach ((array) ($config['reps'] ?? []) as $registro) {
            if (is_array($registro) && ($n = self::nomeValido($registro['image'] ?? null))) {
                $nomes[] = $n;
            }
        }

        return array_values(array_unique($nomes));
    }
}
