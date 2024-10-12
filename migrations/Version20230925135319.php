<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925135319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_unread_count ADD last_message_id INT NOT NULL, DROP count');
        $this->addSql('ALTER TABLE chat_unread_count ADD CONSTRAINT FK_2A6B4F4BA0E79C3 FOREIGN KEY (last_message_id) REFERENCES chat_message (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_2A6B4F4BA0E79C3 ON chat_unread_count (last_message_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_unread_count DROP FOREIGN KEY FK_2A6B4F4BA0E79C3');
        $this->addSql('DROP INDEX IDX_2A6B4F4BA0E79C3 ON chat_unread_count');
        $this->addSql('ALTER TABLE chat_unread_count ADD count INT DEFAULT NULL, DROP last_message_id');
    }
}
