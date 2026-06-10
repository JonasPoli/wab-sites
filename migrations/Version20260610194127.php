<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610194127 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page_block_team_member ADD summary LONGTEXT DEFAULT NULL, ADD experience LONGTEXT DEFAULT NULL, ADD address VARCHAR(500) DEFAULT NULL, ADD custom_sections JSON DEFAULT NULL, ADD practice_areas JSON DEFAULT NULL, ADD detail_enabled TINYINT(1) DEFAULT 0 NOT NULL, ADD detail_layout VARCHAR(20) DEFAULT \'classic\' NOT NULL, ADD slug VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page_block_team_member DROP summary, DROP experience, DROP address, DROP custom_sections, DROP practice_areas, DROP detail_enabled, DROP detail_layout, DROP slug');
    }
}
