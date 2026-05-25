<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260525175554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tenant ADD bg_color_light1 VARCHAR(7) DEFAULT \'#ffffff\', ADD bg_color_light2 VARCHAR(7) DEFAULT \'#f8fafc\', ADD bg_color_dark1 VARCHAR(7) DEFAULT \'#0d0f1a\', ADD bg_color_dark2 VARCHAR(7) DEFAULT \'#131625\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tenant DROP bg_color_light1, DROP bg_color_light2, DROP bg_color_dark1, DROP bg_color_dark2');
    }
}
