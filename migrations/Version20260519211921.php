<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260519211921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE page_block_partner_logo (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, logo_filename VARCHAR(255) NOT NULL, url VARCHAR(500) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F0EB8DB4E9ED820C (block_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page_block_testimonial (id INT AUTO_INCREMENT NOT NULL, block_id INT NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(255) DEFAULT NULL, text LONGTEXT NOT NULL, rating SMALLINT DEFAULT 5 NOT NULL, avatar VARCHAR(255) DEFAULT NULL, position INT DEFAULT 0 NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B3B4019BE9ED820C (block_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE page_block_partner_logo ADD CONSTRAINT FK_F0EB8DB4E9ED820C FOREIGN KEY (block_id) REFERENCES page_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE page_block_testimonial ADD CONSTRAINT FK_B3B4019BE9ED820C FOREIGN KEY (block_id) REFERENCES page_block (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE study DROP FOREIGN KEY FK_E67F974912469DE2');
        $this->addSql('ALTER TABLE study DROP FOREIGN KEY FK_E67F97499033212A');
        $this->addSql('ALTER TABLE study DROP FOREIGN KEY FK_E67F9749F675F31B');
        $this->addSql('ALTER TABLE study_material DROP FOREIGN KEY FK_DF37601CE7B003E9');
        $this->addSql('ALTER TABLE video_material DROP FOREIGN KEY FK_D43C472929C1004E');
        $this->addSql('ALTER TABLE video_support DROP FOREIGN KEY FK_65E9020812469DE2');
        $this->addSql('ALTER TABLE video_support DROP FOREIGN KEY FK_65E902089033212A');
        $this->addSql('ALTER TABLE video_support DROP FOREIGN KEY FK_65E90208F675F31B');
        $this->addSql('DROP TABLE study');
        $this->addSql('DROP TABLE study_material');
        $this->addSql('DROP TABLE video_material');
        $this->addSql('DROP TABLE video_support');
        $this->addSql('ALTER TABLE page_section ADD bg_type VARCHAR(20) DEFAULT \'none\' NOT NULL, ADD bg_color VARCHAR(100) DEFAULT NULL, ADD bg_gradient VARCHAR(500) DEFAULT NULL, ADD bg_image VARCHAR(255) DEFAULT NULL, ADD bg_image_opacity INT DEFAULT 100 NOT NULL, ADD bg_image_position VARCHAR(20) DEFAULT \'center\' NOT NULL, ADD bg_video VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE study (id INT AUTO_INCREMENT NOT NULL, tenant_id INT NOT NULL, category_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, cover_image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cover_image_updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, materials_html LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_E67F974912469DE2 (category_id), INDEX IDX_E67F97499033212A (tenant_id), INDEX IDX_E67F9749F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE study_material (id INT AUTO_INCREMENT NOT NULL, study_id INT NOT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, extension VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', position INT DEFAULT 0 NOT NULL, INDEX IDX_DF37601CE7B003E9 (study_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE video_material (id INT AUTO_INCREMENT NOT NULL, video_id INT NOT NULL, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, filename VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, extension VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', position INT DEFAULT 0 NOT NULL, INDEX IDX_D43C472929C1004E (video_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE video_support (id INT AUTO_INCREMENT NOT NULL, tenant_id INT NOT NULL, category_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, youtube_id VARCHAR(30) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, materials_html LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_65E9020812469DE2 (category_id), INDEX IDX_65E902089033212A (tenant_id), INDEX IDX_65E90208F675F31B (author_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE study ADD CONSTRAINT FK_E67F974912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE study ADD CONSTRAINT FK_E67F97499033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE study ADD CONSTRAINT FK_E67F9749F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE study_material ADD CONSTRAINT FK_DF37601CE7B003E9 FOREIGN KEY (study_id) REFERENCES study (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_material ADD CONSTRAINT FK_D43C472929C1004E FOREIGN KEY (video_id) REFERENCES video_support (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_support ADD CONSTRAINT FK_65E9020812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE video_support ADD CONSTRAINT FK_65E902089033212A FOREIGN KEY (tenant_id) REFERENCES tenant (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE video_support ADD CONSTRAINT FK_65E90208F675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('ALTER TABLE page_block_partner_logo DROP FOREIGN KEY FK_F0EB8DB4E9ED820C');
        $this->addSql('ALTER TABLE page_block_testimonial DROP FOREIGN KEY FK_B3B4019BE9ED820C');
        $this->addSql('DROP TABLE page_block_partner_logo');
        $this->addSql('DROP TABLE page_block_testimonial');
        $this->addSql('ALTER TABLE page_section DROP bg_type, DROP bg_color, DROP bg_gradient, DROP bg_image, DROP bg_image_opacity, DROP bg_image_position, DROP bg_video, DROP updated_at');
    }
}
