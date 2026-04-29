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

has_php_module() {
  local php_bin="$1"
  local module="$2"
  "$php_bin" -m | grep -qi "^${module}$"
}

repair_pdo_mysql() {
  local version="$1"
  local pdo_so=""

  echo "Tentative de reparation de pdo_mysql pour PHP ${version}..."

  sudo apt-get install --reinstall -y "php${version}-mysql" \
    || sudo apt-get install --reinstall -y php-mysql \
    || true

  if command -v phpenmod >/dev/null 2>&1; then
    sudo phpenmod -v "$version" -s cli pdo pdo_mysql || true
  fi

  # Certains environnements ont le .so present mais pas le fichier ini CLI
  pdo_so="$(find /usr/lib/php -name pdo_mysql.so 2>/dev/null | head -n 1 || true)"
  if [[ -n "$pdo_so" && ! -f "/etc/php/${version}/mods-available/pdo_mysql.ini" ]]; then
    echo "extension=pdo_mysql" | sudo tee "/etc/php/${version}/mods-available/pdo_mysql.ini" >/dev/null
    command -v phpenmod >/dev/null 2>&1 && sudo phpenmod -v "$version" -s cli pdo_mysql || true
  fi
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
  "php${PHP_VERSION}-intl" \
  "php${PHP_VERSION}-xml" \
  "php${PHP_VERSION}-mbstring" \
  "php${PHP_VERSION}-curl" \
  mysql-server

echo "Installation du package MySQL pour PHP ${PHP_VERSION}..."
if ! sudo apt-get install -y "php${PHP_VERSION}-mysql"; then
  echo "Package php${PHP_VERSION}-mysql indisponible, tentative avec php-mysql..."
  sudo apt-get install -y php-mysql
fi

if [[ -x "/usr/bin/php${PHP_VERSION}" ]]; then
  sudo update-alternatives --set php "/usr/bin/php${PHP_VERSION}" || true
fi

echo "Activation de MySQL..."
sudo systemctl enable --now mysql

PHP_BIN="php${PHP_VERSION}"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  abort "binaire introuvable: $PHP_BIN"
fi

if ! has_php_module "$PHP_BIN" "PDO"; then
  abort "PDO n'est pas active pour $PHP_BIN"
fi

if ! has_php_module "$PHP_BIN" "pdo_mysql"; then
  echo "pdo_mysql non detectee, tentative d'activation..."
  repair_pdo_mysql "$PHP_VERSION"
fi

if ! has_php_module "$PHP_BIN" "pdo_mysql"; then
  echo "Diagnostic extensions PHP pour $PHP_BIN:"
  "$PHP_BIN" --ini | sed 's/^/  /'
  "$PHP_BIN" -m | grep -Ei 'pdo|mysql' | sed 's/^/  /' || true
  dpkg -l | grep -E "php${PHP_VERSION}-(mysql|cli|common)|php-mysql" | sed 's/^/  /' || true
  abort "pdo_mysql n'est pas active pour $PHP_BIN. Verifiez les paquets php${PHP_VERSION}-mysql / php-mysql"
fi

echo ""
echo "Installation terminee avec succes."
echo "- PHP: $($PHP_BIN -v | head -n 1)"
echo "- MySQL: $(mysql --version)"
echo ""
echo "Verification rapide:"
echo "  $PHP_BIN -m | grep -E 'PDO|pdo_mysql'"
echo "  systemctl status mysql --no-pager"
