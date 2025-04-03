<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240605000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modifier la colonne cover_letter de VARCHAR à TEXT';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application MODIFY cover_letter TEXT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application MODIFY cover_letter VARCHAR(255)');
    }
}
