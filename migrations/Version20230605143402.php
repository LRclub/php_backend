<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230605143402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialists_requests ADD specialist_id INT NOT NULL');
        $this->addSql('ALTER TABLE specialists_requests ADD CONSTRAINT FK_B79291E57B100C1A FOREIGN KEY (specialist_id) REFERENCES specialists (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_B79291E57B100C1A ON specialists_requests (specialist_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialists_requests DROP FOREIGN KEY FK_B79291E57B100C1A');
        $this->addSql('DROP INDEX IDX_B79291E57B100C1A ON specialists_requests');
        $this->addSql('ALTER TABLE specialists_requests DROP specialist_id');
    }
}
