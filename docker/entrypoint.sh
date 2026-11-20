#!/bin/sh
# Démarrage du conteneur de production (Render) :
#   1. Apache écoute sur le port fourni par Render ($PORT) ;
#   2. certificat pour la connexion chiffrée à la base MySQL (TiDB Cloud) ;
#   3. création de la base si besoin, migrations, puis initialisation si la base est neuve (app:installer) ;
#   4. lancement d'Apache.
set -e

PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Certificat de l'autorité qui signe le serveur MySQL :
#  - MYSQL_SSL_CA : contenu d'un certificat fourni par l'hébergeur (variable d'environnement, facultatif) ;
#  - sinon un « secret file » Render nommé mysql-ca.pem ;
#  - sinon les autorités habituelles du système (cas de TiDB Cloud, certificat Let's Encrypt).
if [ -n "$MYSQL_SSL_CA" ]; then
    printf '%s\n' "$MYSQL_SSL_CA" > /tmp/mysql-ca.pem
elif [ -f /etc/secrets/mysql-ca.pem ]; then
    cp /etc/secrets/mysql-ca.pem /tmp/mysql-ca.pem
else
    cp /etc/ssl/certs/ca-certificates.crt /tmp/mysql-ca.pem
fi
export MYSQL_SSL_CA_PATH=/tmp/mysql-ca.pem

cd /var/www/html

# Les commandes tournent sous l'utilisateur d'Apache pour que le cache lui appartienne
console() {
    su -s /bin/sh www-data -c "MYSQL_SSL_CA_PATH=$MYSQL_SSL_CA_PATH php bin/console $*"
}

console cache:clear --no-interaction
console doctrine:database:create --if-not-exists --no-interaction
console doctrine:migrations:migrate --no-interaction --allow-no-migration
console app:installer --no-interaction

exec apache2-foreground
