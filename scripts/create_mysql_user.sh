#!/usr/bin/env bash

set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  ./create_mysql_user.sh [options]

Options:
  --user <nom>         Nom du user MySQL a creer
  --password <mdp>     Mot de passe du user MySQL
  --host <host>        Host autorise (defaut: localhost)
  --root-user <nom>    User admin MySQL (defaut: root)
  --root-password <x>  Mot de passe admin MySQL (optionnel)
  -h, --help           Affiche cette aide

Exemples:
  ./create_mysql_user.sh --user app_user --password secret123
  ./create_mysql_user.sh --user app_user --host '%' --root-user root
EOF
}

abort() {
  echo "Erreur: $1" >&2
  exit 1
}

is_valid_identifier() {
  [[ "$1" =~ ^[A-Za-z0-9_]+$ ]]
}

is_valid_host() {
  [[ "$1" =~ ^[A-Za-z0-9._%:-]+$ ]]
}

sql_escape() {
  printf "%s" "$1" | sed "s/'/''/g"
}

MYSQL_USER=""
MYSQL_PASSWORD=""
MYSQL_HOST="localhost"
ROOT_USER="root"
ROOT_PASSWORD=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --user)
      [[ $# -ge 2 ]] || abort "--user attend une valeur"
      MYSQL_USER="$2"
      shift 2
      ;;
    --password)
      [[ $# -ge 2 ]] || abort "--password attend une valeur"
      MYSQL_PASSWORD="$2"
      shift 2
      ;;
    --host)
      [[ $# -ge 2 ]] || abort "--host attend une valeur"
      MYSQL_HOST="$2"
      shift 2
      ;;
    --root-user)
      [[ $# -ge 2 ]] || abort "--root-user attend une valeur"
      ROOT_USER="$2"
      shift 2
      ;;
    --root-password)
      [[ $# -ge 2 ]] || abort "--root-password attend une valeur"
      ROOT_PASSWORD="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      abort "argument inattendu: $1"
      ;;
  esac
done

if [[ -z "$MYSQL_USER" ]]; then
  read -r -p "Nom du user MySQL a creer: " MYSQL_USER
fi

if [[ -z "$MYSQL_PASSWORD" ]]; then
  read -r -s -p "Mot de passe du user MySQL: " MYSQL_PASSWORD
  echo
fi

if [[ -z "$ROOT_PASSWORD" ]]; then
  read -r -s -p "Mot de passe du user admin MySQL ($ROOT_USER) (laisser vide si aucun): " ROOT_PASSWORD
  echo
fi

[[ -n "$MYSQL_USER" ]] || abort "nom de user vide"
[[ -n "$MYSQL_PASSWORD" ]] || abort "mot de passe vide"
[[ -n "$ROOT_USER" ]] || abort "user admin vide"

is_valid_identifier "$MYSQL_USER" || abort "nom de user invalide (autorise: lettres, chiffres, underscore)"
is_valid_identifier "$ROOT_USER" || abort "user admin invalide (autorise: lettres, chiffres, underscore)"
is_valid_host "$MYSQL_HOST" || abort "host invalide"

ESCAPED_PASSWORD="$(sql_escape "$MYSQL_PASSWORD")"

echo "Creation du user '$MYSQL_USER'@'$MYSQL_HOST' avec tous les droits..."

if [[ -n "$ROOT_PASSWORD" ]]; then
  MYSQL_PWD="$ROOT_PASSWORD" mysql -u "$ROOT_USER" <<SQL
CREATE USER IF NOT EXISTS '$MYSQL_USER'@'$MYSQL_HOST' IDENTIFIED BY '$ESCAPED_PASSWORD';
GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'$MYSQL_HOST' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL
else
  mysql -u "$ROOT_USER" <<SQL
CREATE USER IF NOT EXISTS '$MYSQL_USER'@'$MYSQL_HOST' IDENTIFIED BY '$ESCAPED_PASSWORD';
GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'$MYSQL_HOST' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL
fi

echo "Termine: user '$MYSQL_USER'@'$MYSQL_HOST' cree avec tous les droits."

