<p align="center">
  <img src="docs/logo.png" alt="Logo L'ÂME PEÏ" width="220">
</p>

<h1 align="center">L'ÂME PEÏ</h1>
<p align="center"><em>Savates perlées fait main · La Réunion</em></p>

---

## Présentation

**L'ÂME PEÏ** est une marque de savates artisanales, inspirée de La Réunion, de ses couleurs et de son identité réunionnaise : « Dans chaque pas, nana un bout nout île. » Des savates personnalisées : chaque bride est brodée à la main, perle après perle (perles nacrées, rocailles, perles dorées), sur une tong Havaianas. Les coloris s'inspirent des paysages de l'île : hibiscus, bougainvilliers, lagon, terre volcanique.

Ce dépôt contient le **site de vente en ligne** de la marque, développé avec **Symfony**.

> 🎓 Projet réalisé dans le cadre d'un TP Symfony (formation CDA), avec l'objectif de devenir à terme le site officiel de la marque.

## Fonctionnalités prévues

**Boutique**
- Collections (Fleur Peï, Éclat Peï) avec leur page de présentation
- Fiche produit : photos, palette de perles, matériaux, tailles Havaianas (33/34 à 41/42), stock par taille
- Modèles « en production » avec bouton *Me prévenir de sa sortie*
- Panier persistant et tunnel de commande

**Sur-mesure**
- Formulaire de demande personnalisée (coloris, taille, occasion, message)

**Espace client**
- Inscription, connexion, historique des commandes

**Administration**
- Gestion des modèles, collections, stocks par taille, commandes et demandes sur-mesure

## Stack technique

| Élément | Technologie |
|---|---|
| Framework | Symfony 8.1 |
| Langage | PHP 8.4+ |
| Base de données | MySQL 8.4, InnoDB (Doctrine ORM) |
| Templates | Twig |
| Front | AssetMapper, Stimulus |
| Administration | EasyAdmin |
| Environnement local | WAMP (Windows) |

## Installation en local

```bash
git clone https://github.com/chinesekaii/Lame-pei.git
cd Lame-pei
composer install
```

Créer un fichier `.env.local` à la racine :

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/lame_pei?serverVersion=8.4.7&charset=utf8mb4"
```

Puis :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load   # base de démonstration (efface les données !)
# ou, sur une base existante : php bin/console app:catalogue:synchroniser
symfony server:start --no-tls
```

Le site est accessible sur `http://127.0.0.1:8000`.

### E-mails en développement

Les e-mails du site sont interceptés par [Mailpit](https://mailpit.axllent.org/) (fausse boîte mail) :

```bash
docker run -d --name lamepei-mailpit --restart unless-stopped -p 1025:1025 -p 8025:8025 axllent/mailpit
```

Dans `.env.local` : `MAILER_DSN=smtp://127.0.0.1:1025`. Les e-mails sont visibles sur `http://localhost:8025`.

## Identité visuelle

| Couleur | Hex | Usage |
|---|---|---|
| Terre cuite | `#A9502E` | Couleur principale, boutons |
| Brun volcan | `#8C3A1E` | Titres, survols |
| Brun bois | `#6B4634` | Texte |
| Or | `#C79A3E` | Accents, filets |
| Sable | `#EFD9C0` | Fonds de section |
| Crème | `#FFF9F2` | Fond principal |

**Typographies :** Cormorant Garamond (titres) · Jost (texte)

## Avancement

- [x] Maquette du site (Claude Design)
- [x] Projet Symfony et connexion à la base MySQL
- [x] Modèle de données : 10 entités Doctrine, relations, validation, migration
- [x] Données de démonstration (fixtures)
- [x] Mise en page fidèle à la maquette (Twig) et page d'accueil
- [x] Fiches produits
- [x] Panier et commande
- [x] Formulaire sur-mesure et alertes de sortie
- [x] Authentification et espace client
- [x] Avis clients (achat vérifié, modération, note moyenne) et galerie photos clients
- [x] E-mails : confirmation de commande, suivi, sur-mesure, alertes de sortie, mot de passe oublié
- [x] Administration (EasyAdmin)
- [x] Paiement en ligne par carte (Stripe Checkout)
- [x] Mise en ligne de démonstration (Render + TiDB Cloud, voir docs/DEPLOIEMENT.md)

## Auteur

**Kenjy**, développement du site

---

<sub>Le nom, le logo et les photographies L'ÂME PEÏ appartiennent à la marque. Tous droits réservés. Havaianas est une marque déposée de son propriétaire et n'est pas affiliée à ce projet.</sub>
