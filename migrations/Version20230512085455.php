<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230512085455 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials_access DROP FOREIGN KEY FK_E0196061E308AC6F');
        $this->addSql('ALTER TABLE materials_access DROP FOREIGN KEY FK_E0196061A76ED395');
        $this->addSql('DROP TABLE materials_access');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS materials_access (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, material_id INT NOT NULL, INDEX IDX_E0196061E308AC6F (material_id), INDEX IDX_E0196061A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE materials_access ADD CONSTRAINT FK_E0196061E308AC6F FOREIGN KEY (material_id) REFERENCES materials (id)');
        $this->addSql('ALTER TABLE materials_access ADD CONSTRAINT FK_E0196061A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
