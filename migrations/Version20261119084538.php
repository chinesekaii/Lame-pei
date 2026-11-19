<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261119084538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alerte (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL, envoyee TINYINT NOT NULL, modele_id INT NOT NULL, UNIQUE INDEX uniq_alerte_email_modele (email, modele_id), INDEX IDX_3AE753AAC14B70A (modele_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, reference VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, total INT NOT NULL, adresse_livraison LONGTEXT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_6EEAA67DAEA34913 (reference), INDEX IDX_6EEAA67DA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE couleur_perle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(60) NOT NULL, code_hex VARCHAR(7) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE demande_sur_mesure (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, coloris VARCHAR(150) NOT NULL, taille SMALLINT NOT NULL, message LONGTEXT NOT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, modele_id INT DEFAULT NULL, INDEX IDX_673D1AB6AC14B70A (modele_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE drops (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, theme VARCHAR(100) DEFAULT NULL, description LONGTEXT DEFAULT NULL, banniere VARCHAR(255) DEFAULT NULL, date_sortie DATE DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ligne_commande (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire INT NOT NULL, commande_id INT NOT NULL, variante_id INT NOT NULL, INDEX IDX_3170B74B82EA2E54 (commande_id), INDEX IDX_3170B74BD45162B6 (variante_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE materiau (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_18D5DD55A4D60759 (libelle), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modele (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, slug VARCHAR(120) NOT NULL, accroche VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, prix INT NOT NULL, image VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, ordre INT NOT NULL, drop_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_10028558989D9B62 (slug), INDEX IDX_100285584D224760 (drop_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modele_couleur_perle (modele_id INT NOT NULL, couleur_perle_id INT NOT NULL, INDEX IDX_C40B8A72AC14B70A (modele_id), INDEX IDX_C40B8A728ADCAB3F (couleur_perle_id), PRIMARY KEY (modele_id, couleur_perle_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modele_materiau (modele_id INT NOT NULL, materiau_id INT NOT NULL, INDEX IDX_700DE357AC14B70A (modele_id), INDEX IDX_700DE357CE19B47A (materiau_id), PRIMARY KEY (modele_id, materiau_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE variante (id INT AUTO_INCREMENT NOT NULL, pointure SMALLINT NOT NULL, stock INT NOT NULL, modele_id INT NOT NULL, UNIQUE INDEX uniq_modele_pointure (modele_id, pointure), INDEX IDX_474CE6B0AC14B70A (modele_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE alerte ADD CONSTRAINT FK_3AE753AAC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE demande_sur_mesure ADD CONSTRAINT FK_673D1AB6AC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BD45162B6 FOREIGN KEY (variante_id) REFERENCES variante (id)');
        $this->addSql('ALTER TABLE modele ADD CONSTRAINT FK_100285584D224760 FOREIGN KEY (drop_id) REFERENCES drops (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE modele_couleur_perle ADD CONSTRAINT FK_C40B8A72AC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modele_couleur_perle ADD CONSTRAINT FK_C40B8A728ADCAB3F FOREIGN KEY (couleur_perle_id) REFERENCES couleur_perle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modele_materiau ADD CONSTRAINT FK_700DE357AC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modele_materiau ADD CONSTRAINT FK_700DE357CE19B47A FOREIGN KEY (materiau_id) REFERENCES materiau (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE variante ADD CONSTRAINT FK_474CE6B0AC14B70A FOREIGN KEY (modele_id) REFERENCES modele (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alerte DROP FOREIGN KEY FK_3AE753AAC14B70A');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA76ED395');
        $this->addSql('ALTER TABLE demande_sur_mesure DROP FOREIGN KEY FK_673D1AB6AC14B70A');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BD45162B6');
        $this->addSql('ALTER TABLE modele DROP FOREIGN KEY FK_100285584D224760');
        $this->addSql('ALTER TABLE modele_couleur_perle DROP FOREIGN KEY FK_C40B8A72AC14B70A');
        $this->addSql('ALTER TABLE modele_couleur_perle DROP FOREIGN KEY FK_C40B8A728ADCAB3F');
        $this->addSql('ALTER TABLE modele_materiau DROP FOREIGN KEY FK_700DE357AC14B70A');
        $this->addSql('ALTER TABLE modele_materiau DROP FOREIGN KEY FK_700DE357CE19B47A');
        $this->addSql('ALTER TABLE variante DROP FOREIGN KEY FK_474CE6B0AC14B70A');
        $this->addSql('DROP TABLE alerte');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE couleur_perle');
        $this->addSql('DROP TABLE demande_sur_mesure');
        $this->addSql('DROP TABLE drops');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE materiau');
        $this->addSql('DROP TABLE modele');
        $this->addSql('DROP TABLE modele_couleur_perle');
        $this->addSql('DROP TABLE modele_materiau');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE variante');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
