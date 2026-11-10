<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110184714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'conteudo-de-exemplo: marca sample em article, author e category';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article ADD sample TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE author ADD sample TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE category ADD sample TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP sample');
        $this->addSql('ALTER TABLE author DROP sample');
        $this->addSql('ALTER TABLE category DROP sample');
    }
}
