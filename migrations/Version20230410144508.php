<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230410144508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message CHANGE comment message LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE files ADD chat_message_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059948B568F FOREIGN KEY (chat_message_id) REFERENCES chat_message (id)');
        $this->addSql('CREATE INDEX IDX_6354059948B568F ON files (chat_message_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message CHANGE message comment LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059948B568F');
        $this->addSql('DROP INDEX IDX_6354059948B568F ON files');
        $this->addSql('ALTER TABLE files DROP chat_message_id');
    }
}
