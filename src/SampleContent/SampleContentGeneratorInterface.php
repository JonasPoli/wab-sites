<?php

namespace App\SampleContent;

use App\Entity\Tenant;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Gerador de conteúdo de exemplo de um tema (comando app:conteudo-exemplo).
 * Cada implementação cuida de um tema e só lê ou grava registros do tenant
 * recebido: no console não há filtro de tenant do Doctrine.
 */
#[AutoconfigureTag('app.sample_content_generator')]
interface SampleContentGeneratorInterface
{
    /** O tema que este gerador sabe preencher (Tenant.theme). */
    public function theme(): string;

    /** Nome do conteúdo principal no plural, para as mensagens ("notícias"). */
    public function label(): string;

    /** Maior quantidade aceita em --quantidade. */
    public function maxQuantity(): int;

    /** Quantos itens principais de exemplo o site já tem. */
    public function countSamples(Tenant $tenant): int;

    /** O que generate() faria, sem gravar nem baixar nada. */
    public function plan(Tenant $tenant, int $quantity): SampleReport;

    /** Cria o conteúdo. Deve rodar dentro de uma transação aberta pelo comando. */
    public function generate(Tenant $tenant, int $quantity): SampleReport;

    /** O que remove() apagaria e o que manteria, sem gravar. */
    public function planRemoval(Tenant $tenant): SampleReport;

    /** Apaga só o conteúdo de exemplo. Deve rodar dentro de uma transação. */
    public function remove(Tenant $tenant): SampleReport;

    /** Depois do commit: apaga as fotos dos registros removidos por remove(). */
    public function afterCommit(): void;

    /** Depois de uma falha: apaga as fotos gravadas por generate() e esquece as da remoção. */
    public function afterRollback(): void;
}
