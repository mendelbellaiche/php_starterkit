#!/bin/bash

# Demande le user de mysql
read -p "Entrez le nom d'utilisateur MySQL : " MYSQL_USER

# Demande le nom de base de donnée
read -p "Entrez le nom de la base de données : " DB_NAME

# Supprime si besoin une base de données avec le nom et créé la nouvelle base de donnée
echo "Suppression (si existante) et création de la base de données $DB_NAME..."
mysql -u "$MYSQL_USER" -p -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\`;"

# Lance le fichier init_tables.sql en forçant la base cible via USE
echo "Initialisation des tables à partir de init_tables.sql..."
{
  echo "USE \`$DB_NAME\`;"
  cat "$(dirname "$0")/init_tables.sql"
} | mysql -u "$MYSQL_USER" -p

echo "Terminé !"
