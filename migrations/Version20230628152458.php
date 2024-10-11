<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230628152458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE formatted_video (id INT AUTO_INCREMENT NOT NULL, file_id INT NOT NULL, file_path VARCHAR(255) NOT NULL, convertation_status SMALLINT DEFAULT NULL, type VARCHAR(100) NOT NULL, INDEX IDX_6273A2F993CB796C (file_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE formatted_video ADD CONSTRAINT FK_6273A2F993CB796C FOREIGN KEY (file_id) REFERENCES files (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formatted_video DROP FOREIGN KEY FK_6273A2F993CB796C');
        $this->addSql('DROP TABLE formatted_video');
    }
}
