<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230113112615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE subscription_history (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, type VARCHAR(100) NOT NULL, description VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, create_time INT NOT NULL, INDEX IDX_54AF90D02989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription_history_user (subscription_history_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E8F0BAA2DCE0C437 (subscription_history_id), INDEX IDX_E8F0BAA2A76ED395 (user_id), PRIMARY KEY(subscription_history_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subscription_history ADD CONSTRAINT FK_54AF90D02989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE subscription_history_user ADD CONSTRAINT FK_E8F0BAA2DCE0C437 FOREIGN KEY (subscription_history_id) REFERENCES subscription_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription_history_user ADD CONSTRAINT FK_E8F0BAA2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription_history DROP FOREIGN KEY FK_54AF90D02989F1FD');
        $this->addSql('ALTER TABLE subscription_history_user DROP FOREIGN KEY FK_E8F0BAA2DCE0C437');
        $this->addSql('ALTER TABLE subscription_history_user DROP FOREIGN KEY FK_E8F0BAA2A76ED395');
        $this->addSql('DROP TABLE subscription_history');
        $this->addSql('DROP TABLE subscription_history_user');
    }
}
