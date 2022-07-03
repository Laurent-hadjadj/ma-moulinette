<?php

declare(strict_types=1);

namespace MigrationsSecondary;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220703002830 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE repartition (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL, component CLOB NOT NULL, type VARCHAR(16) NOT NULL, severity VARCHAR(8) NOT NULL, setup INTEGER NOT NULL, date_enregistrement DATETIME NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE repartition');
    }
}
