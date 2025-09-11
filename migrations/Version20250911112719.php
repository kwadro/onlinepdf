<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250911112719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_access ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_access ADD CONSTRAINT FK_633B3069166D1F9C FOREIGN KEY (project_id) REFERENCES sam_project (id)');
        $this->addSql('CREATE INDEX IDX_633B3069166D1F9C ON user_access (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_access DROP FOREIGN KEY FK_633B3069166D1F9C');
        $this->addSql('DROP INDEX IDX_633B3069166D1F9C ON user_access');
        $this->addSql('ALTER TABLE user_access DROP project_id');
    }
}
