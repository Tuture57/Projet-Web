<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250403084736 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE application CHANGE cv cv VARCHAR(255) DEFAULT NULL, CHANGE cover_letter cover_letter LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user DROP FOREIGN KEY FK_8D93D649979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8D93D649979B1AD6 ON user
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD school VARCHAR(255) NOT NULL, DROP company_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE application CHANGE cv cv VARCHAR(255) NOT NULL, CHANGE cover_letter cover_letter VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD company_id INT DEFAULT NULL, DROP school
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8D93D649979B1AD6 ON user (company_id)
        SQL);
    }
}
