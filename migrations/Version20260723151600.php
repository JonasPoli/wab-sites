<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260723151600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add newsletter_enabled column to tenant table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant ADD newsletter_enabled TINYINT(1) NOT NULL DEFAULT 1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant DROP COLUMN newsletter_enabled');
    }
}
