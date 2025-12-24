<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251224113826 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE component (id INT AUTO_INCREMENT NOT NULL, ingredient_id INT DEFAULT NULL, unit_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, quantity INT DEFAULT NULL, updated_at DATE DEFAULT NULL, created_at DATE DEFAULT NULL, INDEX IDX_49FEA157933FE08C (ingredient_id), INDEX IDX_49FEA157F8BD700D (unit_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE component_recipe (component_id INT NOT NULL, recipe_id INT NOT NULL, INDEX IDX_691BA4DE2ABAFFF (component_id), INDEX IDX_691BA4D59D8A214 (recipe_id), PRIMARY KEY(component_id, recipe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_form_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, message VARCHAR(1255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE git_user (id INT AUTO_INCREMENT NOT NULL, user_name VARCHAR(255) DEFAULT NULL, user_password VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ingredient (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, sku VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, price VARCHAR(255) DEFAULT NULL, updated_at DATE DEFAULT NULL, created_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE map_import (id INT AUTO_INCREMENT NOT NULL, entity VARCHAR(255) DEFAULT NULL, old_id INT DEFAULT NULL, new_id INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, position INT DEFAULT NULL, is_active VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, short_description VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, cuisine VARCHAR(255) DEFAULT NULL, prep_time_min INT DEFAULT NULL, cook_time_min INT DEFAULT NULL, servings INT DEFAULT NULL, notes VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, image1 VARCHAR(255) DEFAULT NULL, image2 VARCHAR(255) DEFAULT NULL, updated_at DATE DEFAULT NULL, created_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, position INT DEFAULT NULL, is_active VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description VARCHAR(255) DEFAULT NULL, updated_at DATE DEFAULT NULL, created_at DATE DEFAULT NULL, INDEX IDX_70DCBC5F727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_category_recipe (recipe_category_id INT NOT NULL, recipe_id INT NOT NULL, INDEX IDX_BC142E20C6B4D386 (recipe_category_id), INDEX IDX_BC142E2059D8A214 (recipe_id), PRIMARY KEY(recipe_category_id, recipe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipe_step (id INT AUTO_INCREMENT NOT NULL, recipe_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, position INT DEFAULT NULL, question VARCHAR(255) DEFAULT NULL, answer VARCHAR(255) DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, updated_at DATE DEFAULT NULL, created_at DATE DEFAULT NULL, INDEX IDX_3CA2A4E359D8A214 (recipe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sam_project (id INT AUTO_INCREMENT NOT NULL, git_user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', git_url VARCHAR(255) NOT NULL, INDEX IDX_A197625924D162B5 (git_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE server_data (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, hostname VARCHAR(255) NOT NULL, port VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, dump_link VARCHAR(255) DEFAULT NULL, type_server INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', web_url VARCHAR(255) DEFAULT NULL, web_admin_url VARCHAR(255) DEFAULT NULL, web_admin_login VARCHAR(255) DEFAULT NULL, web_admin_password VARCHAR(255) DEFAULT NULL, http_auth_login VARCHAR(255) DEFAULT NULL, http_auth_password VARCHAR(255) DEFAULT NULL, php_version VARCHAR(255) NOT NULL, framework_version VARCHAR(255) NOT NULL, INDEX IDX_26738283166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE server_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service_data (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, url VARCHAR(255) DEFAULT NULL, user VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_63B0478F166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE unit (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, short_name VARCHAR(255) DEFAULT NULL, updated_at DATE DEFAULT NULL, created_at DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_access (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, project_id INT DEFAULT NULL, server_type JSON NOT NULL COMMENT \'(DC2Type:json)\', service TINYINT(1) NOT NULL, INDEX IDX_633B3069A76ED395 (user_id), INDEX IDX_633B3069166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE component ADD CONSTRAINT FK_49FEA157933FE08C FOREIGN KEY (ingredient_id) REFERENCES ingredient (id)');
        $this->addSql('ALTER TABLE component ADD CONSTRAINT FK_49FEA157F8BD700D FOREIGN KEY (unit_id) REFERENCES unit (id)');
        $this->addSql('ALTER TABLE component_recipe ADD CONSTRAINT FK_691BA4DE2ABAFFF FOREIGN KEY (component_id) REFERENCES component (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE component_recipe ADD CONSTRAINT FK_691BA4D59D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_category ADD CONSTRAINT FK_70DCBC5F727ACA70 FOREIGN KEY (parent_id) REFERENCES recipe_category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE recipe_category_recipe ADD CONSTRAINT FK_BC142E20C6B4D386 FOREIGN KEY (recipe_category_id) REFERENCES recipe_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_category_recipe ADD CONSTRAINT FK_BC142E2059D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipe_step ADD CONSTRAINT FK_3CA2A4E359D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id)');
        $this->addSql('ALTER TABLE sam_project ADD CONSTRAINT FK_A197625924D162B5 FOREIGN KEY (git_user_id) REFERENCES git_user (id)');
        $this->addSql('ALTER TABLE server_data ADD CONSTRAINT FK_26738283166D1F9C FOREIGN KEY (project_id) REFERENCES sam_project (id)');
        $this->addSql('ALTER TABLE service_data ADD CONSTRAINT FK_63B0478F166D1F9C FOREIGN KEY (project_id) REFERENCES sam_project (id)');
        $this->addSql('ALTER TABLE user_access ADD CONSTRAINT FK_633B3069A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_access ADD CONSTRAINT FK_633B3069166D1F9C FOREIGN KEY (project_id) REFERENCES sam_project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE component DROP FOREIGN KEY FK_49FEA157933FE08C');
        $this->addSql('ALTER TABLE component DROP FOREIGN KEY FK_49FEA157F8BD700D');
        $this->addSql('ALTER TABLE component_recipe DROP FOREIGN KEY FK_691BA4DE2ABAFFF');
        $this->addSql('ALTER TABLE component_recipe DROP FOREIGN KEY FK_691BA4D59D8A214');
        $this->addSql('ALTER TABLE recipe_category DROP FOREIGN KEY FK_70DCBC5F727ACA70');
        $this->addSql('ALTER TABLE recipe_category_recipe DROP FOREIGN KEY FK_BC142E20C6B4D386');
        $this->addSql('ALTER TABLE recipe_category_recipe DROP FOREIGN KEY FK_BC142E2059D8A214');
        $this->addSql('ALTER TABLE recipe_step DROP FOREIGN KEY FK_3CA2A4E359D8A214');
        $this->addSql('ALTER TABLE sam_project DROP FOREIGN KEY FK_A197625924D162B5');
        $this->addSql('ALTER TABLE server_data DROP FOREIGN KEY FK_26738283166D1F9C');
        $this->addSql('ALTER TABLE service_data DROP FOREIGN KEY FK_63B0478F166D1F9C');
        $this->addSql('ALTER TABLE user_access DROP FOREIGN KEY FK_633B3069A76ED395');
        $this->addSql('ALTER TABLE user_access DROP FOREIGN KEY FK_633B3069166D1F9C');
        $this->addSql('DROP TABLE component');
        $this->addSql('DROP TABLE component_recipe');
        $this->addSql('DROP TABLE contact_form_type');
        $this->addSql('DROP TABLE git_user');
        $this->addSql('DROP TABLE ingredient');
        $this->addSql('DROP TABLE map_import');
        $this->addSql('DROP TABLE recipe');
        $this->addSql('DROP TABLE recipe_category');
        $this->addSql('DROP TABLE recipe_category_recipe');
        $this->addSql('DROP TABLE recipe_step');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE sam_project');
        $this->addSql('DROP TABLE server_data');
        $this->addSql('DROP TABLE server_type');
        $this->addSql('DROP TABLE service_data');
        $this->addSql('DROP TABLE unit');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_access');
        $this->addSql('DROP TABLE messenger_messages');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
