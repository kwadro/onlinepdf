<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250831195941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE server_data (id INT AUTO_INCREMENT NOT NULL, type_server INT NOT NULL, hostname VARCHAR(255) NOT NULL, port VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, dump_link VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sam_project ADD description VARCHAR(255) NOT NULL, ADD updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE name name VARCHAR(255) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE server_data');
        $this->addSql('ALTER TABLE sam_project DROP type_server, DROP host, DROP port, DROP user, DROP password, DROP dump_link, DROP updated_at, CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
