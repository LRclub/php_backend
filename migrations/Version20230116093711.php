<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230116093711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE promocodes_used (id INT AUTO_INCREMENT NOT NULL, promocode_id INT NOT NULL, user_id INT NOT NULL, activation_time INT DEFAULT NULL, end_time INT DEFAULT NULL, INDEX IDX_DD0F09A4C76C06D9 (promocode_id), INDEX IDX_DD0F09A4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE promocodes_used ADD CONSTRAINT FK_DD0F09A4C76C06D9 FOREIGN KEY (promocode_id) REFERENCES promocodes (id)');
        $this->addSql('ALTER TABLE promocodes_used ADD CONSTRAINT FK_DD0F09A4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE promocodes ADD start_time INT NOT NULL, ADD end_time INT DEFAULT NULL, ADD amount INT DEFAULT NULL, ADD description VARCHAR(255) DEFAULT NULL, ADD is_active TINYINT(1) DEFAULT NULL, ADD is_deleted TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE promocodes_used DROP FOREIGN KEY FK_DD0F09A4C76C06D9');
        $this->addSql('ALTER TABLE promocodes_used DROP FOREIGN KEY FK_DD0F09A4A76ED395');
        $this->addSql('DROP TABLE promocodes_used');
        $this->addSql('ALTER TABLE promocodes DROP start_time, DROP end_time, DROP amount, DROP description, DROP is_active, DROP is_deleted');
    }
}
