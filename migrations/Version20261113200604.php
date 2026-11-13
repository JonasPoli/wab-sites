<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * antispam-no-contato: motivo do bloqueio e domínio de origem no contato.
 */
final class Version20261113200604 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'contact_message: colunas spam_reason e source_domain (antispam do contato)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact_message ADD spam_reason VARCHAR(50) DEFAULT NULL, ADD source_domain VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact_message DROP spam_reason, DROP source_domain');
    }
}
