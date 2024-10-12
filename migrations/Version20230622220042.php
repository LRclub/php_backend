<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230622220042 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS materials_stream_access (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, material_id INT NOT NULL, user_key VARCHAR(255) NOT NULL, INDEX IDX_49447510A76ED395 (user_id), INDEX IDX_49447510E308AC6F (material_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE materials_stream_access ADD CONSTRAINT FK_49447510A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE materials_stream_access ADD CONSTRAINT FK_49447510E308AC6F FOREIGN KEY (material_id) REFERENCES materials (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials_stream_access DROP FOREIGN KEY FK_49447510A76ED395');
        $this->addSql('ALTER TABLE materials_stream_access DROP FOREIGN KEY FK_49447510E308AC6F');
        $this->addSql('DROP TABLE materials_stream_access');
    }
}
