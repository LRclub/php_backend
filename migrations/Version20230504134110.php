<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230504134110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS materials_categories_favorites (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_2541FA95A76ED395 (user_id), INDEX IDX_2541FA959777D11E (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE materials_categories_favorites ADD CONSTRAINT FK_2541FA95A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE materials_categories_favorites ADD CONSTRAINT FK_2541FA959777D11E FOREIGN KEY (category_id) REFERENCES materials_categories (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE materials_categories_favorites DROP FOREIGN KEY FK_2541FA95A76ED395');
        $this->addSql('ALTER TABLE materials_categories_favorites DROP FOREIGN KEY FK_2541FA959777D11E');
        $this->addSql('DROP TABLE materials_categories_favorites');
    }
}
