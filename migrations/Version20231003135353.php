<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231003135353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice ADD recurrent_parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744B5F04D7E FOREIGN KEY (recurrent_parent_id) REFERENCES invoice (id)');
        $this->addSql('CREATE INDEX  IDX_90651744B5F04D7E ON invoice (recurrent_parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744B5F04D7E');
        $this->addSql('DROP INDEX IDX_90651744B5F04D7E ON invoice');
        $this->addSql('ALTER TABLE invoice DROP recurrent_parent_id');
    }
}
