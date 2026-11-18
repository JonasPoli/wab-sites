<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261118220044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Redirecionamentos de endereços antigos por site (site-marcenaria-pichoneri)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE redirect (id INT AUTO_INCREMENT NOT NULL, tenant_id INT NOT NULL, from_path VARCHAR(255) NOT NULL, to_path VARCHAR(500) NOT NULL, hits INT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C30C9E2B9033212A (tenant_id), UNIQUE INDEX uniq_redirect_tenant_from (tenant_id, from_path), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE redirect ADD CONSTRAINT FK_C30C9E2B9033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE redirect DROP FOREIGN KEY FK_C30C9E2B9033212A');
        $this->addSql('DROP TABLE redirect');
    }
}
