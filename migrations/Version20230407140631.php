<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230407140631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059537A1329');
        $this->addSql('CREATE TABLE IF NOT EXISTS feedback_message (id INT AUTO_INCREMENT NOT NULL, chat_id INT NOT NULL, user_id INT NOT NULL, comment LONGTEXT DEFAULT NULL, create_time INT NOT NULL, update_time INT NOT NULL, is_read TINYINT(1) DEFAULT NULL, notification_sended TINYINT(1) DEFAULT NULL, is_admin TINYINT(1) DEFAULT NULL, INDEX IDX_27E410DC1A9A7125 (chat_id), INDEX IDX_27E410DCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE feedback_message ADD CONSTRAINT FK_27E410DC1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE feedback_message ADD CONSTRAINT FK_27E410DCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F1A9A7125');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA76ED395');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP INDEX IDX_6354059537A1329 ON files');
        $this->addSql('ALTER TABLE files CHANGE message_id feedback_message_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059F773D73 FOREIGN KEY (feedback_message_id) REFERENCES feedback_message (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_6354059F773D73 ON files (feedback_message_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_6354059F773D73');
        $this->addSql('CREATE TABLE IF NOT EXISTS message (id INT AUTO_INCREMENT NOT NULL, chat_id INT NOT NULL, user_id INT NOT NULL, comment LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, create_time INT NOT NULL, update_time INT NOT NULL, is_read TINYINT(1) DEFAULT NULL, notification_sended TINYINT(1) DEFAULT NULL, is_admin TINYINT(1) DEFAULT NULL, INDEX IDX_B6BD307F1A9A7125 (chat_id), INDEX IDX_B6BD307FA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE feedback_message DROP FOREIGN KEY FK_27E410DC1A9A7125');
        $this->addSql('ALTER TABLE feedback_message DROP FOREIGN KEY FK_27E410DCA76ED395');
        $this->addSql('DROP TABLE feedback_message');
        $this->addSql('DROP INDEX IDX_6354059F773D73 ON files');
        $this->addSql('ALTER TABLE files CHANGE feedback_message_id message_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_6354059537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_6354059537A1329 ON files (message_id)');
    }
}
