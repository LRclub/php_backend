<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230503144212 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B5FAE957CD');
        $this->addSql('DROP INDEX UNIQ_9B1716B5FAE957CD ON materials');
        $this->addSql('ALTER TABLE materials ADD stream LONGTEXT DEFAULT NULL, ADD is_stream_finished TINYINT(1) DEFAULT NULL, CHANGE preview_image_id stream_start INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials DROP stream, DROP is_stream_finished, CHANGE stream_start preview_image_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B5FAE957CD FOREIGN KEY (preview_image_id) REFERENCES files (id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_9B1716B5FAE957CD ON materials (preview_image_id)');
    }
}
