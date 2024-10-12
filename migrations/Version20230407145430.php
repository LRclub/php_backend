<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230407145430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_message DROP FOREIGN KEY FK_27E410DC1A9A7125');
        $this->addSql('DROP INDEX IDX_27E410DC1A9A7125 ON feedback_message');
        $this->addSql('ALTER TABLE feedback_message CHANGE chat_id feedback_id INT NOT NULL');
        $this->addSql('ALTER TABLE feedback_message ADD CONSTRAINT FK_27E410DCD249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_27E410DCD249A887 ON feedback_message (feedback_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feedback_message DROP FOREIGN KEY FK_27E410DCD249A887');
        $this->addSql('DROP INDEX IDX_27E410DCD249A887 ON feedback_message');
        $this->addSql('ALTER TABLE feedback_message CHANGE feedback_id chat_id INT NOT NULL');
        $this->addSql('ALTER TABLE feedback_message ADD CONSTRAINT FK_27E410DC1A9A7125 FOREIGN KEY (chat_id) REFERENCES chat (id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_27E410DC1A9A7125 ON feedback_message (chat_id)');
    }
}
