<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908193241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE server_data ADD web_url VARCHAR(255) DEFAULT NULL, ADD web_admin_url VARCHAR(255) DEFAULT NULL, ADD web_admin_login VARCHAR(255) DEFAULT NULL, ADD web_admin_password VARCHAR(255) DEFAULT NULL, ADD http_auth_login VARCHAR(255) DEFAULT NULL, ADD http_auth_password VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE server_data DROP web_url, DROP web_admin_url, DROP web_admin_login, DROP web_admin_password, DROP http_auth_login, DROP http_auth_password');
    }
}
