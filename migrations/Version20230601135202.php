<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601135202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS specialists_categories (id INT AUTO_INCREMENT NOT NULL, specialist_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_52E235F07B100C1A (specialist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE specialists_categories ADD CONSTRAINT FK_52E235F07B100C1A FOREIGN KEY (specialist_id) REFERENCES specialists (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialists_categories DROP FOREIGN KEY FK_52E235F07B100C1A');
        $this->addSql('DROP TABLE specialists_categories');
    }
}
