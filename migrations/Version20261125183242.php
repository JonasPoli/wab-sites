<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Logo maior na abertura da página (change logo-maior-na-abertura): tenant.header_logo,
 * {large, height}. Nulo = desligado, então nenhum site muda no deploy.
 */
final class Version20261125183242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Logo maior na abertura (tenant.header_logo)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant ADD header_logo JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant DROP header_logo');
    }
}
