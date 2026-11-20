# Mise en ligne de démonstration — Render + TiDB Cloud

Ce guide met le site en ligne **gratuitement** pour la démonstration du TP :

- **Render** fait tourner le site (conteneur Docker construit à partir du `Dockerfile`) ;
- **TiDB Cloud** héberge la base, **compatible MySQL** (Render ne propose pas MySQL, et Aiven a arrêté son offre MySQL gratuite en novembre 2026).

Durée : environ 30 minutes, dont 10 minutes de construction automatique.

> À savoir sur l'offre gratuite
> - Le site **se met en veille après 15 minutes** sans visite : la première visite suivante attend environ 1 minute.
> - Le disque n'est **pas permanent** : une photo envoyée depuis l'admin disparaît au redémarrage suivant. Les photos du projet (`public/uploads/`) restent, car elles font partie de l'image.
> - **Pas d'e-mails** (`MAILER_DSN=null://null`) et **pas de paiement** (pas de clé Stripe) : les commandes restent « en attente de paiement ».

---

## 1. Créer la base sur TiDB Cloud

1. Créer un compte sur <https://tidbcloud.com> (connexion Google ou GitHub possible, **pas de carte bancaire**).
2. Créer un cluster gratuit : offre **Starter** (anciennement *Serverless*), fournisseur **AWS**, région **Frankfurt (eu-central-1)**, la plus proche de Render. Nom libre, par exemple `lame-pei`.
3. Une fois le cluster prêt, cliquer sur **Connect** :
   - *Connection type* : **Public** ; *Connect With* : **General** ;
   - cliquer sur **Generate Password** et **copier le mot de passe tout de suite** (il n'est affiché qu'une fois) ;
   - noter **Host** (`gateway01.eu-central-1.prod.aws.tidbcloud.com`…), **Port** (`4000`) et **User** (de la forme `2AbCdEfGh.root`).

Le certificat n'est **pas** à télécharger : TiDB Cloud utilise un certificat Let's Encrypt, déjà reconnu par le conteneur.

On compose l'adresse de la base (**DATABASE_URL**) avec ces informations. La base `lame_pei` est **créée automatiquement** au premier démarrage :

```
mysql://UTILISATEUR:MOT_DE_PASSE@HOST:4000/lame_pei?serverVersion=8.0.11&charset=utf8mb4
```

Exemple : `mysql://2AbCdEfGh.root:xYz123@gateway01.eu-central-1.prod.aws.tidbcloud.com:4000/lame_pei?serverVersion=8.0.11&charset=utf8mb4`

> Si le mot de passe contient des caractères spéciaux (`@`, `:`, `/`, `#`…), les remplacer par leur code URL (`@` → `%40`, `#` → `%23`…).

## 2. Créer le site sur Render

1. Créer un compte sur <https://render.com> **avec GitHub** (pour qu'il voie le dépôt `Lame-pei`).
2. **New** → **Blueprint** → choisir le dépôt **chinesekaii/Lame-pei**, branche `main`, *Blueprint Path* vide.
   Render lit le fichier `render.yaml` du projet et prépare le service **lame-pei**.
3. Render demande les valeurs secrètes :

| Variable | Valeur |
|---|---|
| `DATABASE_URL` | l'adresse composée à l'étape 1 |
| `DEFAULT_URI` | `https://lame-pei.onrender.com` (à corriger si Render donne une autre adresse) |
| `ADMIN_EMAIL` | l'e-mail du compte administrateur |
| `ADMIN_PASSWORD` | un mot de passe solide, **12 caractères minimum** |

4. **Apply** : Render construit l'image (5 à 10 minutes la première fois) puis démarre le site.

Au **premier démarrage**, le conteneur (`docker/entrypoint.sh`) :

1. crée la base `lame_pei` si elle n'existe pas (`doctrine:database:create --if-not-exists`) ;
2. crée toutes les tables (`doctrine:migrations:migrate`) ;
3. installe le catalogue, la galerie photos et les avis à valider (`app:installer`) ;
4. crée le compte administrateur à partir de `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

Aux démarrages suivants, rien n'est réinstallé ni écrasé.

## 3. Vérifier

- Ouvrir l'adresse donnée par Render (ex. `https://lame-pei.onrender.com`).
- Se connecter avec `ADMIN_EMAIL` / `ADMIN_PASSWORD`, puis aller sur `/admin`.
- Dans **Avis clients**, publier les avis dont les clientes ont donné leur accord.

Le compte administrateur existant n'est jamais modifié par `ADMIN_PASSWORD` : pour changer son mot de passe, passer par l'espace client.

## 4. Mettre à jour le site

Chaque `git push` sur la branche `main` redéploie automatiquement le site (`autoDeploy: true`).
Les nouvelles migrations sont appliquées au démarrage. Si une migration échoue, le nouveau conteneur
ne démarre pas et **l'ancienne version reste en ligne**.

> TiDB est compatible MySQL mais pas à 100 % : par exemple `CREATE TABLE … SELECT` n'existe pas
> (utiliser `CREATE TABLE` puis `INSERT … SELECT`). Une migration écrite à la main se teste avant
> d'être poussée (voir « Tester l'image en local »).

## 5. En cas de problème

Les journaux sont dans Render → service **lame-pei** → **Logs**.

| Message | Cause probable |
|---|---|
| `Access denied for user` | mot de passe ou utilisateur faux dans `DATABASE_URL` (l'utilisateur contient un préfixe : `xxxx.root`) |
| `Connections using insecure transport are prohibited` | connexion sans TLS : vérifier que `APP_ENV=prod` |
| `SSL connection error` / `certificate verify failed` | hébergeur avec certificat privé : coller son certificat dans une variable `MYSQL_SSL_CA` |
| `ADMIN_PASSWORD doit faire au moins 12 caractères` | mot de passe admin trop court : le site démarre, mais sans compte admin |
| Page « Oups, un petit souci » à la première visite | site en train de se réveiller : réessayer après une minute |

## Tester l'image en local (facultatif)

Avec Docker Desktop, on peut reproduire Render et TiDB Cloud sur son PC (TiDB avec TLS et clé primaire obligatoire) :

```bash
mkdir tidb-conf && printf '[security]\nauto-tls = true\n' > tidb-conf/tidb.toml
docker network create lamepei-test
docker run -d --name lamepei-tidb-test --network lamepei-test -v "$PWD/tidb-conf:/conf" pingcap/tidb:v8.5.1 --config=/conf/tidb.toml
docker run --rm --network lamepei-test mysql:8.4 mysql -h lamepei-tidb-test -P 4000 -u root --ssl-mode=REQUIRED -e "SET GLOBAL sql_require_primary_key=ON; CREATE USER 'app.root'@'%' IDENTIFIED BY 'motdepasse'; GRANT ALL ON *.* TO 'app.root'@'%';"
docker build -t lame-pei:test .
docker run -d --name lamepei-app-test --network lamepei-test -p 8090:10000 -e APP_SECRET=un-secret-de-test -e "DATABASE_URL=mysql://app.root:motdepasse@lamepei-tidb-test:4000/lame_pei?serverVersion=8.0.11&charset=utf8mb4" -e MYSQL_SSL_VERIFY=0 -e ADMIN_EMAIL=admin@example.com -e ADMIN_PASSWORD=UnMotDePasseLong lame-pei:test
```

Le site répond sur <http://localhost:8090>. `MYSQL_SSL_VERIFY=0` uniquement en local (certificat auto-signé du TiDB de test).

---

## Avant un vrai lancement commercial

Cette mise en ligne est une démonstration. Pour vendre réellement, il faudra en plus :

- **mentions légales** (identité du vendeur, contact, TVA, hébergeur) et **politique de confidentialité** ;
- **médiateur de la consommation** indiqué dans les CGV ;
- un **nom de domaine** et un hébergement avec disque permanent (photos envoyées depuis l'admin) ;
- un **service d'envoi d'e-mails** (Brevo, Mailjet…) et les vraies adresses `MAILER_FROM` / `EMAIL_MARQUE` ;
- les **clés Stripe** réelles et le **webhook** `https://<domaine>/stripe/webhook` ;
- une **tâche planifiée** `php bin/console app:commandes:expirer` toutes les 15 minutes.
