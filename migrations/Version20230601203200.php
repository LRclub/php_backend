<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601203200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE consultations (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, is_deleted TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE specialists_categories ADD consultation_id INT NOT NULL, DROP name');
        $this->addSql('ALTER TABLE specialists_categories ADD CONSTRAINT FK_52E235F062FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultations (id)');
        $this->addSql('CREATE INDEX IDX_52E235F062FF6CDF ON specialists_categories (consultation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialists_categories DROP FOREIGN KEY FK_52E235F062FF6CDF');
        $this->addSql('DROP TABLE consultations');
        $this->addSql('DROP INDEX IDX_52E235F062FF6CDF ON specialists_categories');
        $this->addSql('ALTER TABLE specialists_categories ADD name VARCHAR(255) NOT NULL, DROP consultation_id');
    }
}
