#!/usr/bin/env bash

set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  ./install_php_and_bdd.sh [version_php]
  ./install_php_and_bdd.sh --php-version 8.3

Exemples:
  ./install_php_and_bdd.sh 8.2
  ./install_php_and_bdd.sh --php-version 8.3
EOF
}

abort() {
  echo "Erreur: $1" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || abort "commande requise introuvable: $1"
}

PHP_VERSION=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --php-version)
      [[ $# -ge 2 ]] || abort "--php-version attend une valeur (ex: 8.3)"
      PHP_VERSION="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      if [[ -z "$PHP_VERSION" ]]; then
        PHP_VERSION="$1"
        shift
      else
        abort "argument inattendu: $1"
      fi
      ;;
  esac
done

if [[ -z "$PHP_VERSION" ]]; then
  read -r -p "Entrez la version PHP souhaitee (ex: 8.3): " PHP_VERSION
fi

[[ "$PHP_VERSION" =~ ^[0-9]+\.[0-9]+$ ]] || abort "format de version invalide (attendu: X.Y, ex: 8.3)"

if [[ -f /etc/os-release ]]; then
  # shellcheck disable=SC1091
  . /etc/os-release
else
  abort "impossible de detecter le systeme (/etc/os-release manquant)"
fi

[[ "${ID:-}" == "ubuntu" || "${ID_LIKE:-}" == *"ubuntu"* ]] || abort "script prevu pour Ubuntu"

require_cmd sudo
require_cmd apt-get

export DEBIAN_FRONTEND=noninteractive

echo "Mise a jour des paquets..."
sudo apt-get update -y

# Garantit la commande add-apt-repository pour le PPA PHP
sudo apt-get install -y software-properties-common ca-certificates lsb-release

if ! grep -Rqs "ondrej/php" /etc/apt/sources.list /etc/apt/sources.list.d 2>/dev/null; then
  echo "Ajout du PPA ondrej/php pour la version PHP demandee..."
  sudo add-apt-repository -y ppa:ondrej/php
  sudo apt-get update -y
fi

echo "Installation de PHP ${PHP_VERSION}, MySQL et extensions..."
sudo apt-get install -y \
  "php${PHP_VERSION}" \
  "php${PHP_VERSION}-cli" \
  "php${PHP_VERSION}-common" \
  "php${PHP_VERSION}-mysql" \
  mysql-server

if [[ -x "/usr/bin/php${PHP_VERSION}" ]]; then
  sudo update-alternatives --set php "/usr/bin/php${PHP_VERSION}" || true
fi

echo "Activation de MySQL..."
sudo systemctl enable --now mysql

PHP_BIN="php${PHP_VERSION}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  abort "binaire introuvable: $PHP_BIN"
fi

if ! "$PHP_BIN" -m | grep -q '^PDO$'; then
  abort "PDO n'est pas active pour $PHP_BIN"
fi

if ! "$PHP_BIN" -m | grep -q '^pdo_mysql$'; then
  abort "pdo_mysql n'est pas active pour $PHP_BIN"
fi

echo ""
echo "Installation terminee avec succes."
echo "- PHP: $($PHP_BIN -v | head -n 1)"
echo "- MySQL: $(mysql --version)"
echo ""
echo "Verification rapide:"
echo "  $PHP_BIN -m | grep -E 'PDO|pdo_mysql'"
echo "  systemctl status mysql --no-pager"
