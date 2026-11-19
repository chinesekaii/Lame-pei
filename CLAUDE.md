# CLAUDE.md — Contexte du projet L'ÂME PEÏ

Ce fichier donne à Claude Code le contexte du projet. Il est lu automatiquement à chaque session.

## Le projet

Site de vente en ligne de **L'ÂME PEÏ**, marque réunionnaise de tongs Havaianas brodées à la main avec des perles (perles nacrées 6 mm, rocailles, perles dorées 2 mm, fil nylon). L'entreprise appartient à la copine de Kenjy, le développeur.

Double objectif :
1. **TP noté Symfony** (formation CDA, mini-projet sur 2 jours) : le code doit montrer les notions Symfony attendues (Doctrine et relations, formulaires et validation, sécurité et rôles, Twig, admin).
2. **Vrai site de la marque** si le projet aboutit (mise en ligne plus tard, paiement Stripe).

## Développeur et façon de travailler

- Kenjy, développeur en formation (alternance), sous **Windows avec WAMP**, éditeur **VS Code**.
- Répondre **en français**.
- Travailler **étape par étape**, une fonctionnalité à la fois, avec des **commandes prêtes à coller** et un **point de contrôle** à la fin de chaque étape.
- Préférer des solutions complètes qui fonctionnent plutôt que des exercices guidés.
- **Git : ne jamais ajouter de ligne `Co-Authored-By` ni de mention de Claude dans les commits.** Les commits sont signés uniquement par chinesekaii.

## Stack

- Symfony 7 (`symfony new --webapp`), PHP 8.2+
- MySQL via WAMP, Doctrine ORM
- Twig, AssetMapper, Stimulus
- EasyAdmin pour l'administration
- Plus tard : Stripe Checkout, Symfony Mailer, VichUploader

## Maquette et identité visuelle

La maquette validée est dans `docs/maquette/maquette.html` (faite avec Claude Design), avec les images produits dans `docs/maquette/img/`. **Le site doit reproduire cette maquette** : mêmes sections, mêmes textes, même ambiance.

| Rôle | Couleur |
|---|---|
| Terre cuite (principale, boutons) | `#A9502E` |
| Brun volcan (titres, survols) | `#8C3A1E` |
| Brun bois (texte) | `#6B4634` |
| Or (accents, filets) | `#C79A3E` |
| Sable (fonds de section) | `#EFD9C0` |
| Crème (fond principal) | `#FFF9F2` |

Polices (Google Fonts) : **Cormorant Garamond** pour les titres, **Jost** pour le texte.

### Contenu de la maquette
- Accroche : « Dann chak pa, nana un bout nout île. »
- **Modèles disponibles** (photographiés, prêts à partir) : *Capucine* (terre cuite, perles pêche) et *Bougainvilliers* (fuchsia, perles roses).
- **Modèles en production** (bouton « Me prévenir de sa sortie ») : Lagon, Mint Lagoon, Azure Sea, Fleur de sable, Butter Bloom, Coral Kiss, Lavender Shell.
- **Sur-mesure** : formulaire avec coloris, taille, message (mariage, naissance, cadeau…). Réponse sous 48 h.
- Tailles **36 à 42**. Fabrication **8 à 10 jours**. Envoi **La Réunion et France métropolitaine**. Paire disponible : envoi suivi sous 48 h.
- Fiche produit : titre, texte poétique, prix, palette de perles, matériaux, choix de la taille, ajout au panier, autres modèles.
- Panier en tiroir latéral, avec total et bouton « Passer commande ».

## Modèle de données prévu

Les prix sont stockés **en centimes** (`integer`) : 2500 = 25,00 €.

| Entité | Champs principaux | Relations |
|---|---|---|
| `Modele` | nom, slug, accroche, description (texte poétique), prix, image, statut (`disponible` / `en_production` / `archive`), ordre | OneToMany `Variante`, ManyToMany `CouleurPerle`, ManyToOne `Drop` (nullable) |
| `Variante` | pointure (36–42), stock | ManyToOne `Modele` |
| `CouleurPerle` | nom, codeHex | ManyToMany `Modele` (palette affichée sur la fiche) |
| `Materiau` | libellé | ManyToMany `Modele` |
| `Drop` | nom, thème, description, bannière, dateSortie | OneToMany `Modele` — table nommée `drops` (`DROP` est un mot réservé SQL) |
| `Alerte` | email, createdAt, envoyee | ManyToOne `Modele` (« Me prévenir de sa sortie ») |
| `DemandeSurMesure` | nom, email, coloris, taille, message, statut, createdAt | ManyToOne `Modele` (nullable) |
| `User` | email, password, roles, nom, prénom | OneToMany `Commande` |
| `Commande` | référence, createdAt, statut, total, adresseLivraison | ManyToOne `User`, OneToMany `LigneCommande` |
| `LigneCommande` | quantité, prixUnitaire (copié au moment de l'achat) | ManyToOne `Commande`, ManyToOne `Variante` |

## Avancement

- [x] Dépôt GitHub, README, logo
- [x] Maquette (Claude Design)
- [ ] Création du projet Symfony et connexion à la base
- [ ] Entités, migrations, fixtures
- [ ] Layout Twig fidèle à la maquette
- [ ] Accueil, catalogue, fiche produit
- [ ] Panier persistant et commande
- [ ] Formulaire sur-mesure et alertes de sortie
- [ ] Authentification et espace client
- [ ] Administration EasyAdmin
- [ ] Paiement Stripe et mise en ligne

Mettre à jour cette liste au fil du projet.
