<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230410094313 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS chat (id INT AUTO_INCREMENT NOT NULL, first_user_id INT DEFAULT NULL, second_user_id INT DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, INDEX IDX_659DF2AAB4E2BF69 (first_user_id), INDEX IDX_659DF2AAB02C53F8 (second_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS chat_message (id INT AUTO_INCREMENT NOT NULL, chat_id INT NOT NULL, user_id INT NOT NULL, comment LONGTEXT NOT NULL, create_time INT NOT NULL, update_time INT DEFAULT NULL, is_read TINYINT(1) DEFAULT NULL, is_deleted TINYINT(1) DEFAULT NULL, is_admin TINYINT(1) DEFAULT NULL, INDEX IDX_FAB3FC161A9A7125 (chat_id), INDEX IDX_FAB3FC16A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT FK_659DF2AAB4E2BF69 FOREIGN KEY (first_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT FK_659DF2AAB02C53F8 FOREIGN KEY (second_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC161A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY FK_659DF2AAB4E2BF69');
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY FK_659DF2AAB02C53F8');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC161A9A7125');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16A76ED395');
        $this->addSql('DROP TABLE chat');
        $this->addSql('DROP TABLE chat_message');
    }
}
