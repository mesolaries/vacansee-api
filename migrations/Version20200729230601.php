<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200729230601 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE providers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE vacancies_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE categories (id INT NOT NULL, name VARCHAR(255) NOT NULL, is_default BOOLEAN NOT NULL, url VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE category_provider (category_id INT NOT NULL, provider_id INT NOT NULL, PRIMARY KEY(category_id, provider_id))');
        $this->addSql('CREATE INDEX IDX_25BEF7E712469DE2 ON category_provider (category_id)');
        $this->addSql('CREATE INDEX IDX_25BEF7E7A53A8AA ON category_provider (provider_id)');
        $this->addSql('CREATE TABLE providers (id INT NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, has_categories BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE vacancies (id INT NOT NULL, category_id INT NOT NULL, title VARCHAR(255) NOT NULL, company VARCHAR(255) NOT NULL, salary VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, url VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_99165A5912469DE2 ON vacancies (category_id)');
        $this->addSql('ALTER TABLE category_provider ADD CONSTRAINT FK_25BEF7E712469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE category_provider ADD CONSTRAINT FK_25BEF7E7A53A8AA FOREIGN KEY (provider_id) REFERENCES providers (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE vacancies ADD CONSTRAINT FK_99165A5912469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE category_provider DROP CONSTRAINT FK_25BEF7E712469DE2');
        $this->addSql('ALTER TABLE vacancies DROP CONSTRAINT FK_99165A5912469DE2');
        $this->addSql('ALTER TABLE category_provider DROP CONSTRAINT FK_25BEF7E7A53A8AA');
        $this->addSql('DROP SEQUENCE categories_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE providers_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE vacancies_id_seq CASCADE');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE category_provider');
        $this->addSql('DROP TABLE providers');
        $this->addSql('DROP TABLE vacancies');
    }
}
