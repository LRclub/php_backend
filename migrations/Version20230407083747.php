<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230407083747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE streams ADD likes_collector_id INT NOT NULL');
        $this->addSql('ALTER TABLE streams ADD CONSTRAINT FK_FFF7AFA3873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
        $this->addSql('CREATE INDEX  IDX_FFF7AFA3873EDDD ON streams (likes_collector_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE streams DROP FOREIGN KEY FK_FFF7AFA3873EDDD');
        $this->addSql('DROP INDEX IDX_FFF7AFA3873EDDD ON streams');
        $this->addSql('ALTER TABLE streams DROP likes_collector_id');
    }
}
