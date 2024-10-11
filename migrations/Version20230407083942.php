<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230407083942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE likes_collector ADD material_id INT DEFAULT NULL, ADD stream_id INT DEFAULT NULL, ADD comment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE likes_collector ADD CONSTRAINT FK_AC55E9B9E308AC6F FOREIGN KEY (material_id) REFERENCES materials (id)');
        $this->addSql('ALTER TABLE likes_collector ADD CONSTRAINT FK_AC55E9B9D0ED463E FOREIGN KEY (stream_id) REFERENCES materials (id)');
        $this->addSql('ALTER TABLE likes_collector ADD CONSTRAINT FK_AC55E9B9F8697D13 FOREIGN KEY (comment_id) REFERENCES comments (id)');
        $this->addSql('CREATE INDEX IDX_AC55E9B9E308AC6F ON likes_collector (material_id)');
        $this->addSql('CREATE INDEX IDX_AC55E9B9D0ED463E ON likes_collector (stream_id)');
        $this->addSql('CREATE INDEX IDX_AC55E9B9F8697D13 ON likes_collector (comment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE likes_collector DROP FOREIGN KEY FK_AC55E9B9E308AC6F');
        $this->addSql('ALTER TABLE likes_collector DROP FOREIGN KEY FK_AC55E9B9D0ED463E');
        $this->addSql('ALTER TABLE likes_collector DROP FOREIGN KEY FK_AC55E9B9F8697D13');
        $this->addSql('DROP INDEX IDX_AC55E9B9E308AC6F ON likes_collector');
        $this->addSql('DROP INDEX IDX_AC55E9B9D0ED463E ON likes_collector');
        $this->addSql('DROP INDEX IDX_AC55E9B9F8697D13 ON likes_collector');
        $this->addSql('ALTER TABLE likes_collector DROP material_id, DROP stream_id, DROP comment_id');
    }
}
