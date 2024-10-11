<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230605143202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialists_requests ADD invoice_id INT NOT NULL');
        $this->addSql('ALTER TABLE specialists_requests ADD CONSTRAINT FK_B79291E52989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B79291E52989F1FD ON specialists_requests (invoice_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialists_requests DROP FOREIGN KEY FK_B79291E52989F1FD');
        $this->addSql('DROP INDEX UNIQ_B79291E52989F1FD ON specialists_requests');
        $this->addSql('ALTER TABLE specialists_requests DROP invoice_id');
    }
}
