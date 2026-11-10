<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110194600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'cadastro-de-jornalista-ampliado: endereço, estado e casa espírita do jornalista';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author ADD address VARCHAR(255) DEFAULT NULL, ADD state VARCHAR(2) DEFAULT NULL, ADD spiritist_center VARCHAR(150) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author DROP address, DROP state, DROP spiritist_center');
    }
}
