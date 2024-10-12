<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230606154743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS journal_answers (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, question VARCHAR(255) NOT NULL, answer LONGTEXT NOT NULL, date DATE NOT NULL, question_type VARCHAR(255) NOT NULL, INDEX IDX_806B88E3A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE journal_answers ADD CONSTRAINT FK_806B88E3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE journal_answers DROP FOREIGN KEY FK_806B88E3A76ED395');
        $this->addSql('DROP TABLE journal_answers');
    }
}
