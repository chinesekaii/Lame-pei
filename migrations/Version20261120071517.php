<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261120071517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, auteur VARCHAR(60) NOT NULL, note SMALLINT DEFAULT NULL, texte LONGTEXT NOT NULL, statut VARCHAR(20) NOT NULL, source VARCHAR(20) NOT NULL, achat_verifie TINYINT NOT NULL, created_at DATETIME NOT NULL, publie_le DATETIME DEFAULT NULL, modele_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX idx_avis_statut (statut), INDEX IDX_8F91ABF0AC14B70A (modele_id), INDEX IDX_8F91ABF0A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0AC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0AC14B70A');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A76ED395');
        $this->addSql('DROP TABLE avis');
    }
}
