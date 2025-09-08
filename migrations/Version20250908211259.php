<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908211259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_data ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE service_data ADD CONSTRAINT FK_63B0478F166D1F9C FOREIGN KEY (project_id) REFERENCES sam_project (id)');
        $this->addSql('CREATE INDEX IDX_63B0478F166D1F9C ON service_data (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE service_data DROP FOREIGN KEY FK_63B0478F166D1F9C');
        $this->addSql('DROP INDEX IDX_63B0478F166D1F9C ON service_data');
        $this->addSql('ALTER TABLE service_data DROP project_id');
    }
}
