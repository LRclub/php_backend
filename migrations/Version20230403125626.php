<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230403125626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS comments (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, comments_collector_id INT NOT NULL, likes_collector_id INT NOT NULL, reply_id INT DEFAULT NULL, moderation_status SMALLINT DEFAULT NULL, text LONGTEXT NOT NULL, is_deleted TINYINT(1) DEFAULT NULL, create_time INT NOT NULL, update_time INT DEFAULT NULL, INDEX IDX_5F9E962AA76ED395 (user_id), INDEX IDX_5F9E962AF4EDC0EF (comments_collector_id), INDEX IDX_5F9E962A3873EDDD (likes_collector_id), INDEX IDX_5F9E962A8A0E4E7F (reply_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS comments_collector (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS courses (id INT AUTO_INCREMENT NOT NULL, comments_collector_id INT NOT NULL, likes_collector_id INT NOT NULL, title VARCHAR(255) NOT NULL, views_count INT DEFAULT NULL, INDEX IDX_A9A55A4CF4EDC0EF (comments_collector_id), INDEX IDX_A9A55A4C3873EDDD (likes_collector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS likes (id INT AUTO_INCREMENT NOT NULL, likes_collector_id INT NOT NULL, user_id INT NOT NULL, is_like TINYINT(1) DEFAULT NULL, INDEX IDX_49CA4E7D3873EDDD (likes_collector_id), INDEX IDX_49CA4E7DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS likes_collector (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS materials (id INT AUTO_INCREMENT NOT NULL, comments_collector_id INT NOT NULL, likes_collector_id INT NOT NULL, title VARCHAR(255) NOT NULL, views_count INT DEFAULT NULL, INDEX IDX_9B1716B5F4EDC0EF (comments_collector_id), INDEX IDX_9B1716B53873EDDD (likes_collector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS streams (id INT AUTO_INCREMENT NOT NULL, comments_collector_id INT NOT NULL, likes_collector_id INT NOT NULL, title VARCHAR(255) NOT NULL, views_count INT DEFAULT NULL, INDEX IDX_FFF7AFAF4EDC0EF (comments_collector_id), INDEX IDX_FFF7AFA3873EDDD (likes_collector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AF4EDC0EF FOREIGN KEY (comments_collector_id) REFERENCES comments_collector (id)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A3873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A8A0E4E7F FOREIGN KEY (reply_id) REFERENCES comments_collector (id)');
        $this->addSql('ALTER TABLE courses ADD CONSTRAINT FK_A9A55A4CF4EDC0EF FOREIGN KEY (comments_collector_id) REFERENCES comments_collector (id)');
        $this->addSql('ALTER TABLE courses ADD CONSTRAINT FK_A9A55A4C3873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7D3873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
        $this->addSql('ALTER TABLE likes ADD CONSTRAINT FK_49CA4E7DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B5F4EDC0EF FOREIGN KEY (comments_collector_id) REFERENCES comments_collector (id)');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B53873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
        $this->addSql('ALTER TABLE streams ADD CONSTRAINT FK_FFF7AFAF4EDC0EF FOREIGN KEY (comments_collector_id) REFERENCES comments_collector (id)');
        $this->addSql('ALTER TABLE streams ADD CONSTRAINT FK_FFF7AFA3873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962AA76ED395');
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962AF4EDC0EF');
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A3873EDDD');
        $this->addSql('ALTER TABLE comments DROP FOREIGN KEY FK_5F9E962A8A0E4E7F');
        $this->addSql('ALTER TABLE courses DROP FOREIGN KEY FK_A9A55A4CF4EDC0EF');
        $this->addSql('ALTER TABLE courses DROP FOREIGN KEY FK_A9A55A4C3873EDDD');
        $this->addSql('ALTER TABLE likes DROP FOREIGN KEY FK_49CA4E7D3873EDDD');
        $this->addSql('ALTER TABLE likes DROP FOREIGN KEY FK_49CA4E7DA76ED395');
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B5F4EDC0EF');
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B53873EDDD');
        $this->addSql('ALTER TABLE streams DROP FOREIGN KEY FK_FFF7AFAF4EDC0EF');
        $this->addSql('ALTER TABLE streams DROP FOREIGN KEY FK_FFF7AFA3873EDDD');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE comments_collector');
        $this->addSql('DROP TABLE courses');
        $this->addSql('DROP TABLE likes');
        $this->addSql('DROP TABLE likes_collector');
        $this->addSql('DROP TABLE materials');
        $this->addSql('DROP TABLE streams');
    }
}
