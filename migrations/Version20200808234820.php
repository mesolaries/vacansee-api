<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200808234820 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE categories_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE categories (id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3AF34668989D9B62 ON categories (slug)');
        $this->addSql('TRUNCATE TABLE vacancies');
        $this->addSql('ALTER TABLE vacancies DROP category');
        $this->addSql('ALTER TABLE vacancies ADD category_id INT NOT NULL');
        $this->addSql('ALTER TABLE vacancies ADD CONSTRAINT FK_99165A5912469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_99165A5912469DE2 ON vacancies (category_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE vacancies DROP CONSTRAINT FK_99165A5912469DE2');
        $this->addSql('DROP SEQUENCE categories_id_seq CASCADE');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP INDEX IDX_99165A5912469DE2');
        $this->addSql('ALTER TABLE vacancies ADD category VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE vacancies DROP category_id');
    }
}
