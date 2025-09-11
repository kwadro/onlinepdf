<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250911081250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_project (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_project_sam_project (user_project_id INT NOT NULL, sam_project_id INT NOT NULL, INDEX IDX_439BFAD0B10AD970 (user_project_id), INDEX IDX_439BFAD03C8CDF6C (sam_project_id), PRIMARY KEY(user_project_id, sam_project_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_project_sam_project ADD CONSTRAINT FK_439BFAD0B10AD970 FOREIGN KEY (user_project_id) REFERENCES user_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_project_sam_project ADD CONSTRAINT FK_439BFAD03C8CDF6C FOREIGN KEY (sam_project_id) REFERENCES sam_project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_project_sam_project DROP FOREIGN KEY FK_439BFAD0B10AD970');
        $this->addSql('ALTER TABLE user_project_sam_project DROP FOREIGN KEY FK_439BFAD03C8CDF6C');
        $this->addSql('DROP TABLE user_project');
        $this->addSql('DROP TABLE user_project_sam_project');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }
}
