# Mise en ligne de démonstration — Render + Aiven

Ce guide met le site en ligne **gratuitement** pour la démonstration du TP :

- **Render** fait tourner le site (conteneur Docker construit à partir du `Dockerfile`) ;
- **Aiven** héberge la base **MySQL** (Render ne propose pas MySQL).

Durée : environ 30 minutes, dont 10 minutes de construction automatique.

> À savoir sur l'offre gratuite
> - Le site **se met en veille après 15 minutes** sans visite : la première visite suivante attend environ 1 minute.
> - Le disque n'est **pas permanent** : une photo envoyée depuis l'admin disparaît au redémarrage suivant. Les photos du projet (`public/uploads/`) restent, car elles font partie de l'image.
> - **Pas d'e-mails** (`MAILER_DSN=null://null`) et **pas de paiement** (pas de clé Stripe) : les commandes restent « en attente de paiement ».

---

## 1. Créer la base MySQL sur Aiven

1. Créer un compte sur <https://aiven.io> (connexion Google ou GitHub possible).
2. **Create service** → **MySQL** → offre **Free** → région en Europe → **Create**.
3. Attendre que le service passe au statut **Running** (2 à 5 minutes).
4. Dans l'onglet **Overview**, noter les informations de connexion :
   - **Host**, **Port**, **User** (`avnadmin`), **Password**, **Database name** (`defaultdb`) ;
   - la **version de MySQL** affichée (ex. `8.0.35`).
5. Toujours dans **Overview**, cliquer sur **CA certificate** → **Download** : on obtient un fichier `ca.pem`. L'ouvrir avec le Bloc-notes : il commence par `-----BEGIN CERTIFICATE-----`.

On compose l'adresse de la base (**DATABASE_URL**) avec ces informations :

```
mysql://avnadmin:MOT_DE_PASSE@HOST:PORT/defaultdb?serverVersion=8.0.35&charset=utf8mb4
```

> Si le mot de passe contient des caractères spéciaux (`@`, `:`, `/`, `#`…), les remplacer par leur code URL (`@` → `%40`, `#` → `%23`…).

## 2. Créer le site sur Render

1. Créer un compte sur <https://render.com> **avec GitHub** (pour qu'il voie le dépôt `Lame-pei`).
2. **New** → **Blueprint** → choisir le dépôt **chinesekaii/Lame-pei**.
   Render lit le fichier `render.yaml` du projet et prépare le service **lame-pei**.
3. Render demande les valeurs secrètes :

| Variable | Valeur |
|---|---|
| `DATABASE_URL` | l'adresse composée à l'étape 1 |
| `MYSQL_SSL_CA` | **tout le contenu** du fichier `ca.pem` (copier-coller, lignes BEGIN/END comprises) |
| `DEFAULT_URI` | `https://lame-pei.onrender.com` (à corriger si Render donne une autre adresse) |
| `ADMIN_EMAIL` | l'e-mail du compte administrateur |
| `ADMIN_PASSWORD` | un mot de passe solide, **12 caractères minimum** |

4. **Apply** : Render construit l'image (5 à 10 minutes la première fois) puis démarre le site.

Au **premier démarrage**, le conteneur (`docker/entrypoint.sh`) :

1. crée toutes les tables (`doctrine:migrations:migrate`) ;
2. installe le catalogue, la galerie photos et les avis à valider (`app:installer`) ;
3. crée le compte administrateur à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

Aux démarrages suivants, rien n'est réinstallé ni écrasé.

## 3. Vérifier

- Ouvrir l'adresse donnée par Render (ex. `https://lame-pei.onrender.com`).
- Se connecter avec `ADMIN_EMAIL` / `ADMIN_PASSWORD`, puis aller sur `/admin`.
- Dans **Avis clients**, publier les avis dont les clientes ont donné leur accord.

Une fois connecté, **supprimer `ADMIN_PASSWORD`** des variables Render n'est pas nécessaire (le compte existant n'est jamais modifié), mais changer le mot de passe depuis l'espace client est une bonne habitude.

## 4. Mettre à jour le site

Chaque `git push` sur la branche `main` redéploie automatiquement le site (`autoDeploy: true`).
Les nouvelles migrations sont appliquées au démarrage. Si une migration échoue, le nouveau conteneur
ne démarre pas et **l'ancienne version reste en ligne**.

## 5. En cas de problème

Les journaux sont dans Render → service **lame-pei** → **Logs**.

| Message | Cause probable |
|---|---|
| `SSL connection error` / `certificate verify failed` | `MYSQL_SSL_CA` incomplet (copier tout le fichier `ca.pem`) |
| `Access denied for user` | mot de passe ou utilisateur faux dans `DATABASE_URL` |
| `Unknown database` | nom de base faux (`defaultdb` par défaut chez Aiven) |
| `ADMIN_PASSWORD doit faire au moins 12 caractères` | mot de passe admin trop court : le site démarre, mais sans compte admin |
| Page « Oups, un petit souci » à la première visite | site en train de se réveiller : réessayer après une minute |

## Tester l'image en local (facultatif)

Avec Docker Desktop, on peut reproduire Render sur son PC :

```powershell
docker network create lamepei-test
docker run -d --name lamepei-mysql-test --network lamepei-test -e MYSQL_ROOT_PASSWORD=racine -e MYSQL_DATABASE=defaultdb -e MYSQL_USER=avnadmin -e MYSQL_PASSWORD=motdepasse mysql:8.4 --sql-require-primary-key=ON
docker build -t lame-pei:test .
docker run -d --name lamepei-app-test --network lamepei-test -p 8090:10000 -e APP_SECRET=un-secret-de-test -e "DATABASE_URL=mysql://avnadmin:motdepasse@lamepei-mysql-test:3306/defaultdb?serverVersion=8.4&charset=utf8mb4" -e MYSQL_SSL_VERIFY=0 -e ADMIN_EMAIL=admin@example.com -e ADMIN_PASSWORD=UnMotDePasseLong lame-pei:test
```

Le site répond sur <http://localhost:8090>. `MYSQL_SSL_VERIFY=0` uniquement en local (certificat auto-signé du MySQL de test).

---

## Avant un vrai lancement commercial

Cette mise en ligne est une démonstration. Pour vendre réellement, il faudra en plus :

- **mentions légales** (identité du vendeur, contact, TVA, hébergeur) et **politique de confidentialité** ;
- **médiateur de la consommation** indiqué dans les CGV ;
- un **nom de domaine** et un hébergement avec disque permanent (photos envoyées depuis l'admin) ;
- un **service d'envoi d'e-mails** (Brevo, Mailjet…) et les vraies adresses `MAILER_FROM` / `EMAIL_MARQUE` ;
- les **clés Stripe** réelles et le **webhook** `https://<domaine>/stripe/webhook` ;
- une **tâche planifiée** `php bin/console app:commandes:expirer` toutes les 15 minutes.
