<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908204902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sam_project ADD git_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sam_project ADD CONSTRAINT FK_A197625924D162B5 FOREIGN KEY (git_user_id) REFERENCES git_user (id)');
        $this->addSql('CREATE INDEX IDX_A197625924D162B5 ON sam_project (git_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sam_project DROP FOREIGN KEY FK_A197625924D162B5');
        $this->addSql('DROP INDEX IDX_A197625924D162B5 ON sam_project');
        $this->addSql('ALTER TABLE sam_project DROP git_user_id');
    }
}
