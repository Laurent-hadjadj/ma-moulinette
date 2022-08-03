<?php

declare(strict_types=1);

namespace MigrationsDefault;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220801120147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ma_moulinette (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, version VARCHAR(8) NOT NULL, version_date VARCHAR(8) NOT NULL, is_default BOOLEAN DEFAULT DEFAULT NOT NULL, date_enregistrement DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ma_moulinette');
    }
}
