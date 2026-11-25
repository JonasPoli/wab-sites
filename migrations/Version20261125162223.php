<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Botão flutuante do site (change botao-flutuante-do-site).
 *
 * Nulo = WhatsApp, o comportamento de antes. Os temas moderno, wab e noticias não tinham
 * botão flutuante: começam com "Nenhum", para nenhum site mudar de aparência no deploy.
 */
final class Version20261125162223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Botão flutuante do site (tenant.floating_button); Nenhum para os temas moderno, wab e noticias';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant ADD floating_button JSON DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE tenant SET floating_button = '{"mode":"none","pageId":null,"label":"","icon":""}'
            WHERE theme IN ('moderno', 'wab', 'noticias')
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenant DROP floating_button');
    }
}
