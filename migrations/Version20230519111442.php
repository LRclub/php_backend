<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230519111442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice ADD promocode_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744C76C06D9 FOREIGN KEY (promocode_id) REFERENCES promocodes (id)');
        $this->addSql('CREATE INDEX IDX_90651744C76C06D9 ON invoice (promocode_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744C76C06D9');
        $this->addSql('DROP INDEX IDX_90651744C76C06D9 ON invoice');
        $this->addSql('ALTER TABLE invoice DROP promocode_id');
    }
}
