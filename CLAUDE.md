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

### Contenu actuel (mis à jour par la marque, prime sur la maquette)
- Identité : « L'ÂME PEÏ est une marque de savates artisanales, inspirée de notre île, de ses couleurs et de son identité réunionnaise. » La marque dit **savates** (pas « tongs »).
- Slogan : **« Dans chaque pas, nana un bout nout île. »** (remplace « Dann chak pa… » de la maquette).
- **Collections** (entité `Drop`), chacune avec sa section sur l'accueil et sa page `/collection/{slug}` :
  - **Fleur Peï** (collection 1) : Capucine, Bougainvilliers — épuisée ;
  - **Éclat Peï** (collection 2) : Zarlor Peï, Soley Glitter (2 photos : nacre et roz), Soley Square.
- Les 7 modèles de la planche (Lagon, Mint Lagoon…) sont **archivés** (invisibles, alertes conservées).
- Prix : 45 € pour tous les modèles.
- **Tailles Havaianas doubles** : 33/34, 35/36, 37/38, 39/40, 41/42 (`Variante::TAILLES`).
- Livraison en main propre à La Réunion uniquement, 24 à 72 h (voir CGV). Sur-mesure : réponse sous 48 h.
- **Catalogue de référence** : `src/Catalogue/CatalogueReference.php` (collections, modèles, palettes, stock). Pour mettre à jour : modifier ce fichier puis `php bin/console app:catalogue:synchroniser` (ne touche ni aux clients, ni aux commandes, ni aux alertes, **ni au stock existant**). `--stock` impose le stock de référence **moins les paires réservées par des commandes en attente** (sinon une paire réservée pourrait être vendue deux fois). Les fixtures utilisent la même référence.
- Photos sources dans `docs/nouveautes/`, versions web (JPEG ≤ 900 px) dans `public/uploads/modeles/`.

## Modèle de données prévu

Les prix sont stockés **en centimes** (`integer`) : 2500 = 25,00 €.

| Entité | Champs principaux | Relations |
|---|---|---|
| `Modele` | nom, slug, accroche, description (texte poétique), prix, image, galerie (JSON), couleur, statut (`disponible` / `en_production` / `archive`), ordre | OneToMany `Variante`, ManyToMany `CouleurPerle`, ManyToOne `Drop` (= collection, nullable) |
| `Variante` | taille (Havaianas : « 35/36 »…), stock | ManyToOne `Modele` |
| `CouleurPerle` | nom, codeHex | ManyToMany `Modele` (palette affichée sur la fiche) |
| `Materiau` | libellé | ManyToMany `Modele` |
| `Drop` (= **collection**) | nom, slug, thème (sous-titre), description, ordre, bannière, dateSortie | OneToMany `Modele` — table nommée `drops` (`DROP` est un mot réservé SQL) ; « Collections » dans l'admin |
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
- [x] E-mails (Symfony Mailer, 20/11/2026) — service `App\Service\Courrier`, templates `templates/emails/` (styles en ligne, logo `assets/images/logo-email.png` via `email.image('@images/…')`) :
  - commande : confirmation au client + notification à la marque ; e-mail au client à chaque changement de statut dans l'admin ;
  - sur-mesure : accusé de réception au client + notification à la marque ;
  - alertes de sortie : action admin « Prévenir les inscrits » sur un modèle disponible (`App\Service\AlertesSortie`, `#[AdminRoute]` POST), alertes marquées envoyées ;
  - mot de passe oublié : `/mot-de-passe-oublie` → lien signé (`UriSigner`, 1 h, usage unique grâce à une empreinte du mot de passe, `App\Security\LienReinitialisation`), sans table en base.
  - Envoi **synchrone** (routage Messenger async commenté dans `messenger.yaml`). Un échec d'envoi est journalisé et ne bloque jamais le client.
  - Variables : `MAILER_DSN`, `MAILER_FROM`, `EMAIL_MARQUE` (valeurs provisoires `@lamepei.example` dans `.env`, **à remplacer**).
  - Dev : **Mailpit** dans Docker (conteneur `lamepei-mailpit`, SMTP 1025, interface http://localhost:8025), `MAILER_DSN=smtp://127.0.0.1:1025` dans `.env.local`.
- Serveur local : `C:\symfony\symfony.exe server:start -d --no-tls` → http://127.0.0.1:8000 (le https local n'est pas reconnu par Windows).
- `cache:clear` dépasse la mémoire CLI par défaut (128 Mo) : utiliser `php -d memory_limit=512M bin/console cache:clear`.
- [x] Authentification : inscription (`/inscription`, connexion automatique via `Security::login()`), « Se souvenir de moi », limitation à 5 essais/minute (`symfony/rate-limiter`), `role_hierarchy` ROLE_ADMIN > ROLE_USER
- [x] Espace client `/compte` (ROLE_USER) : mes commandes + détail (propriétaire uniquement), mes informations, changement de mot de passe (contrainte `UserPassword`)
- [ ] Plus tard : vérification de l'adresse e-mail à l'inscription (mot de passe oublié : fait)
- [x] Administration EasyAdmin **5.10** (`src/Controller/Admin/`, `/admin`, ROLE_ADMIN) : tableau de bord (commandes à préparer, demandes, stock faible, alertes), CRUD modèles (photo uploadée, stock par pointure, 7 pointures créées automatiquement), drops, couleurs, matériaux, commandes (statut seul modifiable ; annulation = remise en stock), clients (rôle admin), demandes sur-mesure, alertes
  - EasyAdmin 5 : menu via `MenuItem::linkTo(XxxCrudController::class)`, dashboard via `#[AdminDashboard]`, méthode `configureMenuItems()`
  - Les enums de statut implémentent `TranslatableInterface` (libellés français dans EasyAdmin et les formulaires)
  - Images des modèles et drops dans `public/uploads/` (et plus dans AssetMapper) pour pouvoir les envoyer depuis l'admin
  - `Modele::setMateriaux()` / `setCouleursPerle()` : nécessaires car l'inflecteur anglais de Symfony ne trouve pas `addMateriau()`
- [x] CGV : page `/cgv` (`PageController`, `templates/page/cgv.html.twig`, texte fourni par la marque, ne pas le reformuler), lien dans le pied de page, case obligatoire à la commande (`AdresseLivraison::accepteCgv`), date enregistrée dans `Commande::cgvAccepteesLe`
- [x] Livraison alignée sur les CGV : **La Réunion uniquement, en main propre par la créatrice**, sous 24 à 72 h. Enum `SecteurLivraison` (communes → secteur → frais : Ouest 0 €, Nord 5 €, Sud/Est 8 €), choix de la commune au checkout, téléphone obligatoire, CP en 974. `Commande` : `secteurLivraison`, `fraisLivraison`, `total` = articles + livraison. Contrôleur Stimulus `livraison_controller.js` pour le total en direct
- [x] Nouveau catalogue (19/11/2026) : collections Fleur Peï et Éclat Peï, pages `/collection/{slug}`, tailles Havaianas (migration `Version20261119122103` qui convertit et fusionne les anciennes pointures sans perdre les commandes), galerie photo, slogan et texte d'identité, anciens modèles archivés. Sauvegarde de la base avant migration dans `var/sauvegardes/`
- [ ] Mentions légales : identité du vendeur fournie (avis de situation INSEE) — EI Djemylla SINOPE, nom commercial L'AME PEI, SIRET 109 390 658 00010, 71 chemin Saint-Alme 97419 La Possession, APE 15.20Z. Manquent : e-mail/téléphone de contact, régime de TVA, hébergeur, médiateur
- [x] Paiement par carte avec **Stripe Checkout** (20/11/2026) — `App\Service\PaiementStripe` :
  - commande créée « en attente » (stock réservé) → redirection vers la page Stripe (`expires_at` 1 h) → retour sur `/commande/{ref}/confirmation?session_id=…` qui vérifie le paiement auprès de Stripe → « Payée » + e-mails de confirmation (client et marque), une seule fois même si Stripe prévient plusieurs fois ;
  - webhook `POST /stripe/webhook` (signature vérifiée) : `checkout.session.completed` / `async_payment_succeeded` → payée, `checkout.session.expired` → annulée + stock libéré (`CommandeManager::annulerCommandeImpayee()`) ;
  - bouton « Payer » sur la confirmation et dans l'espace client tant que la commande est en attente ; `/commande/{ref}/paiement-annule` si le client quitte Stripe ;
  - filet de sécurité : `php bin/console app:commandes:expirer` (annule les commandes impayées depuis plus de 70 min) — à planifier toutes les 15 min en production ;
  - sans `STRIPE_SECRET_KEY` (sk_test_…) dans `.env.local`, la commande reste en attente avec un message ;
  - `Commande` : `stripeSessionId`, `stripePaiementId`, `payeeLe`.
- Dates affichées à l'heure de La Réunion (`twig.date.timezone` et `Crud::setTimezone`), enregistrées en UTC (PHP en UTC).
- [x] Avis clients (20/11/2026) — entité `Avis` (auteur affiché, note 1–5 **ou null**, texte, modèle, `SourceAvis` site/instagram/message/marché, `StatutAvis` en attente/publié/refusé, `achatVerifie`) :
  - client : « Donner mon avis » sur une commande **livrée** (`/compte/avis/{slug}`, un avis par modèle), en attente de validation, e-mail à la marque ;
  - admin « Avis clients » : ajout des retours reçus ailleurs, boutons Publier / Refuser, badge + tuile « Avis à valider » ;
  - affichage : section sur l'accueil (si au moins un avis publié), avis et note sur la fiche produit, page `/avis` avec l'encadré légal sur la modération (Code conso L111-7-2) ;
  - **les retours sans note n'ont pas d'étoiles et ne comptent pas dans la moyenne** (ne jamais inventer de note) ;
  - retours reçus avant le site : `src/Catalogue/RetoursClients.php`, importés **en attente** par `php bin/console app:avis:importer` (sans doublon). Captures sources dans `docs/avis/` (ignoré par Git : données personnelles). À publier par la marque après accord des clientes.
- [x] Galerie photos clients « Elles l'ont adoptée » (20/11/2026) — entité `PhotoClient` (image, légende, modèle, ordre, visible), admin « Photos clients », carrousel sur l'accueil (3 photos visibles, 1 sur téléphone, **défilement automatique toutes les 4 s** avec pause au survol/toucher et bouton ❚❚ / ▶, désactivé si « réduire les animations », flèches, points, glissement — `carrousel_controller.js`) et mosaïque complète sur `/avis`, agrandissement au clic (`visionneuse_controller.js`, `<dialog>`) :
  - **règle de la marque : aucune photo où l'on voit un visage**, ni pseudo ou photo de profil Instagram (recadrer les stories) ;
  - 7 photos issues de `docs/avis/`, listées dans `RetoursClients::PHOTOS`, fichiers dans `public/uploads/clients/` (sans métadonnées), importées par `app:avis:importer` ;
  - `App\Service\ImageWeb` : toute photo envoyée depuis l'admin est redressée, réduite à 900 px et **débarrassée de ses métadonnées (position GPS)**.
- [ ] Tester un vrai paiement Stripe en mode test (clés à créer sur dashboard.stripe.com), puis configurer le webhook (Stripe CLI en local : `stripe listen --forward-to 127.0.0.1:8000/stripe/webhook`)
- [ ] Mise en ligne

Mettre à jour cette liste au fil du projet.
