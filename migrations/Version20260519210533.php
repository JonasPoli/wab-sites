<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519210533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_form_field (id INT AUTO_INCREMENT NOT NULL, tenant_id INT NOT NULL, label VARCHAR(255) NOT NULL, type VARCHAR(20) DEFAULT \'text\' NOT NULL, options JSON DEFAULT NULL, required TINYINT(1) DEFAULT 0 NOT NULL, position INT DEFAULT 0 NOT NULL, INDEX IDX_1F0BB80C9033212A (tenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_form_field ADD CONSTRAINT FK_1F0BB80C9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_message ADD phone VARCHAR(50) DEFAULT NULL, ADD extra_data JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_form_field DROP FOREIGN KEY FK_1F0BB80C9033212A');
        $this->addSql('DROP TABLE contact_form_field');
        $this->addSql('ALTER TABLE contact_message DROP phone, DROP extra_data');
    }
}
