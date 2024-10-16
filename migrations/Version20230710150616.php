<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230710150616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS phone_restore_history (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, last_phone VARCHAR(180) NOT NULL, new_phone VARCHAR(180) NOT NULL, update_time VARCHAR(255) NOT NULL, INDEX IDX_5B79C641A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE phone_restore_history ADD CONSTRAINT FK_5B79C641A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE phone_restore_history DROP FOREIGN KEY FK_5B79C641A76ED395');
        $this->addSql('DROP TABLE phone_restore_history');
    }
}
