<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261006170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add page.published_at (backfilled from updated_at) for news listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page ADD published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE page SET published_at = updated_at');
        $this->addSql('ALTER TABLE page MODIFY published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP published_at');
    }
}
