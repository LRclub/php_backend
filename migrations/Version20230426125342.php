<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230426125342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE likes_collector DROP FOREIGN KEY FK_AC55E9B9D0ED463E');
        $this->addSql('ALTER TABLE streams DROP FOREIGN KEY FK_FFF7AFA3873EDDD');
        $this->addSql('ALTER TABLE streams DROP FOREIGN KEY FK_FFF7AFAF4EDC0EF');
        $this->addSql('DROP TABLE streams');
        $this->addSql('DROP INDEX IDX_AC55E9B9D0ED463E ON likes_collector');
        $this->addSql('ALTER TABLE likes_collector DROP stream_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS streams (id INT AUTO_INCREMENT NOT NULL, comments_collector_id INT NOT NULL, likes_collector_id INT NOT NULL, title VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, views_count INT DEFAULT NULL, INDEX IDX_FFF7AFA3873EDDD (likes_collector_id), INDEX IDX_FFF7AFAF4EDC0EF (comments_collector_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE streams ADD CONSTRAINT FK_FFF7AFA3873EDDD FOREIGN KEY (likes_collector_id) REFERENCES likes_collector (id)');
        $this->addSql('ALTER TABLE streams ADD CONSTRAINT FK_FFF7AFAF4EDC0EF FOREIGN KEY (comments_collector_id) REFERENCES comments_collector (id)');
        $this->addSql('ALTER TABLE likes_collector ADD stream_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE likes_collector ADD CONSTRAINT FK_AC55E9B9D0ED463E FOREIGN KEY (stream_id) REFERENCES streams (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_AC55E9B9D0ED463E ON likes_collector (stream_id)');
    }
}
