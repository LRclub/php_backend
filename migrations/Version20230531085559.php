<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230531085559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE files ADD specialist_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE files ADD CONSTRAINT FK_63540597B100C1A FOREIGN KEY (specialist_id) REFERENCES specialists (id)');
        $this->addSql('CREATE INDEX  IDX_63540597B100C1A ON files (specialist_id)');
        $this->addSql('ALTER TABLE specialists ADD is_deleted TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE files DROP FOREIGN KEY FK_63540597B100C1A');
        $this->addSql('DROP INDEX IDX_63540597B100C1A ON files');
        $this->addSql('ALTER TABLE files DROP specialist_id');
        $this->addSql('ALTER TABLE specialists DROP is_deleted');
    }
}
