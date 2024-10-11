<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230717124246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials_categories ADD code VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE `materials_categories` SET `code`="meditation" WHERE `slug`="meditation"');
        $this->addSql('UPDATE `materials_categories` SET `code`="podcast" WHERE `slug`="podcast"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials_categories DROP code');
    }
}
