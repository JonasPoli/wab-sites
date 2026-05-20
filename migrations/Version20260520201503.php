<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520201503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE page_block_team_member (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(255) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, linkedin_url VARCHAR(255) DEFAULT NULL, facebook_url VARCHAR(255) DEFAULT NULL, instagram_url VARCHAR(255) DEFAULT NULL, whatsapp_url VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_53F671CDE9ED820C (block_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE page_block_team_member ADD CONSTRAINT FK_53F671CDE9ED820C FOREIGN KEY (block_id) REFERENCES page_block (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page_block_team_member DROP FOREIGN KEY FK_53F671CDE9ED820C');
        $this->addSql('DROP TABLE page_block_team_member');
    }
}
