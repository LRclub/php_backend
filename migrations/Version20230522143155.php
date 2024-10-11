<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522143155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tracker_actions (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, action_id INT NOT NULL, status VARCHAR(255) NOT NULL, completion_date INT NOT NULL, INDEX IDX_52178C03A76ED395 (user_id), INDEX IDX_52178C039D32F035 (action_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tracker_actions ADD CONSTRAINT FK_52178C03A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tracker_actions ADD CONSTRAINT FK_52178C039D32F035 FOREIGN KEY (action_id) REFERENCES tracker (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tracker_actions DROP FOREIGN KEY FK_52178C03A76ED395');
        $this->addSql('ALTER TABLE tracker_actions DROP FOREIGN KEY FK_52178C039D32F035');
        $this->addSql('DROP TABLE tracker_actions');
    }
}
