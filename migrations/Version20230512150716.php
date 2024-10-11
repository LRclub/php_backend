<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230512150716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recosting (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, is_vip TINYINT(1) DEFAULT NULL, subscription_from INT NOT NULL, subscription_to INT NOT NULL, tariff_price DOUBLE PRECISION NOT NULL, total_price DOUBLE PRECISION DEFAULT NULL, remaining_price DOUBLE PRECISION DEFAULT NULL, INDEX IDX_D62A557CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE recosting ADD CONSTRAINT FK_D62A557CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recosting DROP FOREIGN KEY FK_D62A557CA76ED395');
        $this->addSql('DROP TABLE recosting');
    }
}
