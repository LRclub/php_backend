<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230925125902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS chat_unread_count (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, chat_id INT NOT NULL, count INT DEFAULT NULL, update_time INT NOT NULL, INDEX IDX_2A6B4F4A76ED395 (user_id), INDEX IDX_2A6B4F41A9A7125 (chat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chat_unread_count ADD CONSTRAINT FK_2A6B4F4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chat_unread_count ADD CONSTRAINT FK_2A6B4F41A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_unread_count DROP FOREIGN KEY FK_2A6B4F4A76ED395');
        $this->addSql('ALTER TABLE chat_unread_count DROP FOREIGN KEY FK_2A6B4F41A9A7125');
        $this->addSql('DROP TABLE chat_unread_count');
    }
}
