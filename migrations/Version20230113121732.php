<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230113121732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription_history_user DROP FOREIGN KEY FK_E8F0BAA2A76ED395');
        $this->addSql('ALTER TABLE subscription_history_user DROP FOREIGN KEY FK_E8F0BAA2DCE0C437');
        $this->addSql('DROP TABLE subscription_history_user');
        $this->addSql('ALTER TABLE subscription_history ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE subscription_history ADD CONSTRAINT FK_54AF90D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX  IDX_54AF90D0A76ED395 ON subscription_history (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS subscription_history_user (subscription_history_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E8F0BAA2DCE0C437 (subscription_history_id), INDEX IDX_E8F0BAA2A76ED395 (user_id), PRIMARY KEY(subscription_history_id, user_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE subscription_history_user ADD CONSTRAINT FK_E8F0BAA2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription_history_user ADD CONSTRAINT FK_E8F0BAA2DCE0C437 FOREIGN KEY (subscription_history_id) REFERENCES subscription_history (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscription_history DROP FOREIGN KEY FK_54AF90D0A76ED395');
        $this->addSql('DROP INDEX IDX_54AF90D0A76ED395 ON subscription_history');
        $this->addSql('ALTER TABLE subscription_history DROP user_id');
    }
}
