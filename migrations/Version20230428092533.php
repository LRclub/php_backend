<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230428092533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE materials_categories (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_E79F1261727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE materials_categories ADD CONSTRAINT FK_E79F1261727ACA70 FOREIGN KEY (parent_id) REFERENCES materials_categories (id)');
        $this->addSql('ALTER TABLE materials ADD preview_image_id INT DEFAULT NULL, ADD category_id INT NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD short_description LONGTEXT DEFAULT NULL, ADD lazy_post INT DEFAULT NULL, ADD access SMALLINT NOT NULL, ADD type VARCHAR(255) NOT NULL, CHANGE is_pay is_show_bill TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B5FAE957CD FOREIGN KEY (preview_image_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B512469DE2 FOREIGN KEY (category_id) REFERENCES materials_categories (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9B1716B5FAE957CD ON materials (preview_image_id)');
        $this->addSql('CREATE INDEX IDX_9B1716B512469DE2 ON materials (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B512469DE2');
        $this->addSql('ALTER TABLE materials_categories DROP FOREIGN KEY FK_E79F1261727ACA70');
        $this->addSql('DROP TABLE materials_categories');
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B5FAE957CD');
        $this->addSql('DROP INDEX UNIQ_9B1716B5FAE957CD ON materials');
        $this->addSql('DROP INDEX IDX_9B1716B512469DE2 ON materials');
        $this->addSql('ALTER TABLE materials DROP preview_image_id, DROP category_id, DROP description, DROP short_description, DROP lazy_post, DROP access, DROP type, CHANGE is_show_bill is_pay TINYINT(1) DEFAULT NULL');
    }
}
