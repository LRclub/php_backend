<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230503151104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE files ADD materials_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540593A9FC940 FOREIGN KEY (materials_id) REFERENCES materials (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_63540593A9FC940 ON files (materials_id)');
        $this->addSql('ALTER TABLE materials ADD audio_id INT DEFAULT NULL, ADD video_id INT DEFAULT NULL, ADD preview_image_id INT NOT NULL');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B53A3123C7 FOREIGN KEY (audio_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B529C1004E FOREIGN KEY (video_id) REFERENCES files (id)');
        $this->addSql('ALTER TABLE materials ADD CONSTRAINT FK_9B1716B5FAE957CD FOREIGN KEY (preview_image_id) REFERENCES files (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9B1716B53A3123C7 ON materials (audio_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9B1716B529C1004E ON materials (video_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9B1716B5FAE957CD ON materials (preview_image_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540593A9FC940');
        $this->addSql('DROP INDEX IDX_63540593A9FC940 ON files');
        $this->addSql('ALTER TABLE files DROP materials_id');
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B53A3123C7');
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B529C1004E');
        $this->addSql('ALTER TABLE materials DROP FOREIGN KEY FK_9B1716B5FAE957CD');
        $this->addSql('DROP INDEX IDX_9B1716B53A3123C7 ON materials');
        $this->addSql('DROP INDEX IDX_9B1716B529C1004E ON materials');
        $this->addSql('DROP INDEX IDX_9B1716B5FAE957CD ON materials');
        $this->addSql('ALTER TABLE materials DROP audio_id, DROP video_id, DROP preview_image_id');
    }
}
