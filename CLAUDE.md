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

- Symfony 8.1 (`symfony new --webapp`), PHP 8.4+
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
- [x] Création du projet Symfony et connexion à la base (MySQL 8.4 WAMP, InnoDB forcé dans `doctrine.yaml`)
- [x] Entités, enums de statut (`src/Enum`), migration initiale
- [x] Fixtures (`src/DataFixtures` : catalogue de la maquette, comptes admin@lamepei.re / admin1234 et client@lamepei.re / client1234, données de démo)
- [x] Layout Twig fidèle à la maquette (`base.html.twig` + `templates/partials/`, CSS dans `assets/styles/app.css`, images dans `assets/images/`, filtre Twig `|prix`, tiroir panier Stimulus `panier_controller.js`)
- [x] Page d'accueil (`HomeController`, modèles chargés depuis la base, champ `Modele::couleur` pour la couleur du titre)
- [x] Fiche produit (`ModeleController`, route `/modele/{slug}`, 404 si inconnu ou archivé, choix de pointure selon le stock, autres modèles) et liens depuis l'accueil
- [x] Panier en session (`App\Service\Panier`, global Twig `panier`, quantités limitées au stock, tiroir ouvert après ajout) et `PanierController` (ajouter / modifier / retirer, jetons CSRF `panier`)
- [x] Commande (`CommandeController`, réservé à ROLE_USER) : formulaire d'adresse (`Dto\AdresseLivraison` + `AdresseLivraisonType`, code postal contrôlé selon Réunion/métropole), `CommandeManager` (transaction + verrou sur les variantes, stock décrémenté, prix figé), page de confirmation visible par son seul propriétaire. Statut `en_attente` jusqu'au paiement Stripe
- [x] Connexion / déconnexion (`SecurityController`, `/connexion`, `/deconnexion`, form_login). Locale par défaut : `fr`. Thème de formulaire : `templates/form/theme.html.twig`
- [x] Formulaire sur-mesure sur l'accueil (`DemandeSurMesureType`, traité par `HomeController` en GET|POST, coloris déduit du modèle par un écouteur `FormEvents::SUBMIT`, préremplissage `?modele=slug` et client connecté)
- [x] Alertes « Me prévenir de sa sortie » sur la fiche des modèles en production (`AlerteType`, traité par `ModeleController::show`, une inscription par e-mail et par modèle)
- Flashes : `success` / `error` affichés en haut de page ; `sur_mesure`, `alerte` affichés dans leur section ; `ouvrir_panier` ouvre le tiroir
- [ ] Plus tard : e-mail à la marque à chaque demande, e-mail aux inscrits à la sortie d'un modèle (Symfony Mailer)
- [x] Authentification : inscription (`/inscription`, connexion automatique via `Security::login()`), « Se souvenir de moi », limitation à 5 essais/minute (`symfony/rate-limiter`), `role_hierarchy` ROLE_ADMIN > ROLE_USER
- [x] Espace client `/compte` (ROLE_USER) : mes commandes + détail (propriétaire uniquement), mes informations, changement de mot de passe (contrainte `UserPassword`)
- [ ] Plus tard (avec Symfony Mailer) : mot de passe oublié, vérification de l'e-mail à l'inscription
- [x] Administration EasyAdmin **5.10** (`src/Controller/Admin/`, `/admin`, ROLE_ADMIN) : tableau de bord (commandes à préparer, demandes, stock faible, alertes), CRUD modèles (photo uploadée, stock par pointure, 7 pointures créées automatiquement), drops, couleurs, matériaux, commandes (statut seul modifiable ; annulation = remise en stock), clients (rôle admin), demandes sur-mesure, alertes
  - EasyAdmin 5 : menu via `MenuItem::linkTo(XxxCrudController::class)`, dashboard via `#[AdminDashboard]`, méthode `configureMenuItems()`
  - Les enums de statut implémentent `TranslatableInterface` (libellés français dans EasyAdmin et les formulaires)
  - Images des modèles et drops dans `public/uploads/` (et plus dans AssetMapper) pour pouvoir les envoyer depuis l'admin
  - `Modele::setMateriaux()` / `setCouleursPerle()` : nécessaires car l'inflecteur anglais de Symfony ne trouve pas `addMateriau()`
- [x] CGV : page `/cgv` (`PageController`, `templates/page/cgv.html.twig`, texte fourni par la marque, ne pas le reformuler), lien dans le pied de page, case obligatoire à la commande (`AdresseLivraison::accepteCgv`), date enregistrée dans `Commande::cgvAccepteesLe`
- [ ] À aligner sur les CGV (en attente de décision) : livraison Réunion uniquement, frais par secteur (Ouest 0 €, Nord 5 €, Sud/Est 8 €), téléphone obligatoire, délais 24–72 h, paiement CB / lien SumUp / espèces en main propre (au lieu de Stripe)
- [ ] Paiement en ligne et mise en ligne

Mettre à jour cette liste au fil du projet.
