<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250819190047 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sam_project ADD type_server INT NOT NULL, ADD host VARCHAR(255) NULL, ADD port VARCHAR(255) NULL, ADD user VARCHAR(255) NULL, ADD password VARCHAR(255) NULL, ADD dump_link VARCHAR(255) NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sam_project DROP type_server, DROP host, DROP port, DROP user, DROP password, DROP dump_link');
    }
}
