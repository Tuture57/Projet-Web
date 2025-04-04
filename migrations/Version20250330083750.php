<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250330083750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D4E6F81979B1AD6 ON address
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address DROP company_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company ADD address_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company ADD CONSTRAINT FK_4FBF094FF5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4FBF094FF5B7AF75 ON company (address_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE address ADD company_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address ADD CONSTRAINT FK_D4E6F81979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D4E6F81979B1AD6 ON address (company_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company DROP FOREIGN KEY FK_4FBF094FF5B7AF75
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_4FBF094FF5B7AF75 ON company
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE company DROP address_id
        SQL);
    }
}
