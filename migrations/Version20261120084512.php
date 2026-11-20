<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261120084512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE photo_client (id INT AUTO_INCREMENT NOT NULL, image VARCHAR(255) NOT NULL, legende VARCHAR(120) DEFAULT NULL, visible TINYINT NOT NULL, ordre INT NOT NULL, created_at DATETIME NOT NULL, modele_id INT DEFAULT NULL, INDEX IDX_92FB737DAC14B70A (modele_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE photo_client ADD CONSTRAINT FK_92FB737DAC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE photo_client DROP FOREIGN KEY FK_92FB737DAC14B70A');
        $this->addSql('DROP TABLE photo_client');
    }
}
