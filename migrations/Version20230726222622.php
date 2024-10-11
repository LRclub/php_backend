<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230726222622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification_flags DROP FOREIGN KEY FK_CDB1A443A76ED395');
        $this->addSql('DROP TABLE notification_flags');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE notification_flags (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, send_mail TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_CDB1A443A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE notification_flags ADD CONSTRAINT FK_CDB1A443A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
