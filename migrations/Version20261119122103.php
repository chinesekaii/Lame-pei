<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Passage aux tailles Havaianas (35/36, 37/38…) + collections (slug, ordre) + galerie photo.
 *
 * Migration écrite à la main pour CONSERVER les données :
 *  - chaque ancienne pointure est convertie en taille double ;
 *  - deux pointures qui donnent la même taille (ex. 37 et 38 → 37/38) sont fusionnées :
 *    stock additionné, lignes de commande rattachées à la variante conservée.
 */
final class Version20261119122103 extends AbstractMigration
{
    /** Conversion d'une pointure simple vers la taille Havaianas correspondante. */
    private const CONVERSION = "CASE
        WHEN %1\$s <= 34 THEN '33/34'
        WHEN %1\$s <= 36 THEN '35/36'
        WHEN %1\$s <= 38 THEN '37/38'
        WHEN %1\$s <= 40 THEN '39/40'
        ELSE '41/42' END";

    public function getDescription(): string
    {
        return 'Tailles Havaianas (avec conversion et fusion des pointures), slug et ordre des collections, galerie des modèles';
    }

    /** Les ALTER TABLE de MySQL valident la transaction : on enchaîne les requêtes sans transaction globale. */
    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // --- Demandes sur-mesure : 38 → '37/38'
        $this->addSql('ALTER TABLE demande_sur_mesure ADD taille_havaianas VARCHAR(5) DEFAULT NULL');
        $this->addSql('UPDATE demande_sur_mesure SET taille_havaianas = '.sprintf(self::CONVERSION, 'taille'));
        $this->addSql('ALTER TABLE demande_sur_mesure DROP taille');
        $this->addSql('ALTER TABLE demande_sur_mesure CHANGE taille_havaianas taille VARCHAR(5) NOT NULL');

        // --- Collections : slug (adresse de la page) et ordre d'affichage
        $this->addSql('ALTER TABLE drops ADD slug VARCHAR(120) DEFAULT NULL, ADD ordre INT DEFAULT 0 NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE1E9BEA989D9B62 ON drops (slug)');

        // --- Galerie photo des modèles (JSON ne peut pas avoir de valeur par défaut sous MySQL)
        $this->addSql('ALTER TABLE modele ADD galerie JSON DEFAULT NULL');
        $this->addSql("UPDATE modele SET galerie = '[]'");
        $this->addSql('ALTER TABLE modele CHANGE galerie galerie JSON NOT NULL');

        // --- Variantes : pointure → taille, puis fusion des doublons
        $this->addSql('ALTER TABLE variante ADD taille VARCHAR(5) DEFAULT NULL');
        $this->addSql('UPDATE variante SET taille = '.sprintf(self::CONVERSION, 'pointure'));

        // Table temporaire : pour chaque (modèle, taille), la variante gardée et le stock cumulé.
        // Clé primaire obligatoire : certains hébergeurs MySQL imposent sql_require_primary_key.
        // Création puis INSERT … SELECT (TiDB n'accepte pas CREATE TABLE … SELECT).
        $this->addSql('CREATE TEMPORARY TABLE variante_fusion (
                modele_id INT NOT NULL,
                taille VARCHAR(5) NOT NULL,
                garde INT NOT NULL,
                total INT NOT NULL,
                PRIMARY KEY (modele_id, taille)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->addSql('INSERT INTO variante_fusion (modele_id, taille, garde, total)
            SELECT modele_id, taille, MIN(id), SUM(stock)
            FROM variante GROUP BY modele_id, taille');
        $this->addSql('UPDATE variante v JOIN variante_fusion f ON f.garde = v.id SET v.stock = f.total');
        $this->addSql('UPDATE ligne_commande l
            JOIN variante v ON v.id = l.variante_id
            JOIN variante_fusion f ON f.modele_id = v.modele_id AND f.taille = v.taille
            SET l.variante_id = f.garde');
        $this->addSql('DELETE v FROM variante v
            JOIN variante_fusion f ON f.modele_id = v.modele_id AND f.taille = v.taille
            WHERE v.id <> f.garde');
        $this->addSql('DROP TEMPORARY TABLE variante_fusion');

        $this->addSql('DROP INDEX uniq_modele_pointure ON variante');
        $this->addSql('ALTER TABLE variante DROP pointure, CHANGE taille taille VARCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_modele_taille ON variante (modele_id, taille)');
    }

    public function down(Schema $schema): void
    {
        // Retour approximatif : '37/38' → 38 (la fusion des pointures ne peut pas être annulée)
        $this->addSql('DROP INDEX uniq_modele_taille ON variante');
        $this->addSql('ALTER TABLE variante ADD pointure SMALLINT DEFAULT NULL');
        $this->addSql("UPDATE variante SET pointure = CAST(SUBSTRING_INDEX(taille, '/', -1) AS UNSIGNED)");
        $this->addSql('ALTER TABLE variante DROP taille, CHANGE pointure pointure SMALLINT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_modele_pointure ON variante (modele_id, pointure)');

        $this->addSql('ALTER TABLE modele DROP galerie');
        $this->addSql('DROP INDEX UNIQ_CE1E9BEA989D9B62 ON drops');
        $this->addSql('ALTER TABLE drops DROP slug, DROP ordre');

        $this->addSql('ALTER TABLE demande_sur_mesure ADD pointure SMALLINT DEFAULT NULL');
        $this->addSql("UPDATE demande_sur_mesure SET pointure = CAST(SUBSTRING_INDEX(taille, '/', -1) AS UNSIGNED)");
        $this->addSql('ALTER TABLE demande_sur_mesure DROP taille, CHANGE pointure taille SMALLINT NOT NULL');
    }
}
