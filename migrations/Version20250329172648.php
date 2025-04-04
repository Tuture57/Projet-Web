<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250329172648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE address_company DROP FOREIGN KEY FK_145AFED7979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address_company DROP FOREIGN KEY FK_145AFED7F5B7AF75
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE address_company
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address ADD company_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address ADD CONSTRAINT FK_D4E6F81979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D4E6F81979B1AD6 ON address (company_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE address_company (address_id INT NOT NULL, company_id INT NOT NULL, INDEX IDX_145AFED7F5B7AF75 (address_id), INDEX IDX_145AFED7979B1AD6 (company_id), PRIMARY KEY(address_id, company_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address_company ADD CONSTRAINT FK_145AFED7979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address_company ADD CONSTRAINT FK_145AFED7F5B7AF75 FOREIGN KEY (address_id) REFERENCES address (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address DROP FOREIGN KEY FK_D4E6F81979B1AD6
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D4E6F81979B1AD6 ON address
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE address DROP company_id
        SQL);
    }
}
