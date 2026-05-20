<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520190727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page ADD show_title TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE page_block_image CHANGE filename filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE page_block_partner_logo CHANGE logo_filename logo_filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tenant DROP required_approvals');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE page DROP show_title');
        $this->addSql('ALTER TABLE page_block_image CHANGE filename filename VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE page_block_partner_logo CHANGE logo_filename logo_filename VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tenant ADD required_approvals INT DEFAULT 1 NOT NULL');
    }
}
