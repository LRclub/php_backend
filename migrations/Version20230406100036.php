<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230406100036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE courses DROP FOREIGN KEY FK_A9A55A4CF4EDC0EF');
        $this->addSql('DROP TABLE courses');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS courses (id INT AUTO_INCREMENT NOT NULL, comments_collector_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, views_count INT DEFAULT NULL, is_pay TINYINT(1) DEFAULT NULL, INDEX IDX_A9A55A4CF4EDC0EF (comments_collector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE courses ADD CONSTRAINT FK_A9A55A4CF4EDC0EF FOREIGN KEY (comments_collector_id) REFERENCES comments_collector (id)');
    }
}
