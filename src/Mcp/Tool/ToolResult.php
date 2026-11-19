<?php

namespace App\Mcp\Tool;

final class ToolResult
{
    private function __construct(
        public readonly string $text,
        public readonly ?array $structured,
        public readonly bool $isError,
    ) {
    }

    public static function ok(string $text, ?array $structured = null): self
    {
        return new self($text, $structured, false);
    }

    /** Erro de execução da ferramenta: vai ao modelo como resultado, não como erro de protocolo. */
    public static function error(string $text): self
    {
        return new self($text, null, true);
    }

    public function toArray(): array
    {
        $result = ['content' => [['type' => 'text', 'text' => $this->text]], 'isError' => $this->isError];
        if (null !== $this->structured) {
            $result['structuredContent'] = $this->structured;
        }

        return $result;
    }
}
