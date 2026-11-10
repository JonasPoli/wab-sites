<?php

namespace App\SampleContent;

/** Resultado de um passo do comando app:conteudo-exemplo: linhas e avisos. */
final class SampleReport
{
    /** @var list<string> */
    public array $lines = [];

    /** @var list<string> */
    public array $warnings = [];

    public function line(string $text): static
    {
        $this->lines[] = $text;
        return $this;
    }

    public function warn(string $text): static
    {
        $this->warnings[] = $text;
        return $this;
    }

    public function merge(self $other): static
    {
        array_push($this->lines, ...$other->lines);
        array_push($this->warnings, ...$other->warnings);
        return $this;
    }
}
