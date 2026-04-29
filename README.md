# PHP Starter Kit 🚀

Un squelette d'application PHP moderne, léger et structuré, utilisant Twig pour le templating et un routeur basé sur les attributs PHP 8.

## ✨ Caractéristiques

-   **Architecture MVC** : Séparation claire entre la logique métier, les données et l'affichage.
-   **Templating Twig** : Moteur de template puissant et sécurisé.
-   **Routing PHP 8** : Utilisation des attributs pour définir les routes directement dans les contrôleurs.
-   **Système de Flash** : Gestion facile des messages de notification (succès, erreur, etc.).
-   **Gestion des Utilisateurs** : Inscription, connexion et protection de routes intégrées.
-   **Styling Bootstrap 5** : Interface responsive et moderne prête à l'emploi.

## 🛠️ Prérequis

-   PHP 8.1 ou supérieur
-   Composer
-   MySQL ou MariaDB

## 🚀 Installation

1.  **Cloner le dépôt**
    ```bash
    git clone https://github.com/mendelbellaiche/php_starterkit.git
    cd php_starterkit
    ```

2.  **Installer les dépendances (avec Composer)**
    ```bash
    composer install
    ```

3.  **Base de données**
    Créez une base de données MySQL et importez le schéma fourni :
    ```bash
    mysql -u votre_utilisateur -p votre_base_de_donnees < init_tables.sql
    ```

4.  **Configuration**
    Configurez vos accès à la base de données dans le fichier `config.php`.

5.  **Lancer le projet**
    Vous pouvez utiliser le serveur interne de PHP :
    ```bash
    php -S localhost:8000 -t public
    ```
    Ou utiliser le script fourni :
    ```bash
    chmod +x server.sh
    ./server.sh
    ```

## 📂 Structure du projet

-   `src/` : Code source de l'application (Controllers, Models, Core).
-   `public/` : Point d'entrée de l'application (index.php) et fichiers statiques (CSS, JS, Images).
-   `src/Views/` : Fichiers templates Twig.
-   `config.php` : Fichier de configuration globale.
-   `init_tables.sql` : Script de création de la base de données.

## Déploiement

Il faut d'abord installer PHP, MySQL et créer un user MySQL:

```
cd /path/to/project/scripts
./install_php_and_bdd.sh
./create_mysql_user.sh 
```

Il faut installer composer et le lancer:

```
apt install composer
composer install
```

Déplacer ensuite le projet:

```
mv php_starterkit /path/to/site
```

Ensuite, changez les droits du projets web:

```
sudo chown -R www-data:www-data /path/to/site
```

Ensuite, il faut modifier la configuration d'apache2. Exemple:

```
sudo nano /etc/apache2/sites-available/000-default.conf
```

Contenu:
```
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/mysite/public

    <Directory /var/www/mysite/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Il faut activer le **mod_rewrite**:

```
sudo a2enmod rewrite
```

et relancer apache2:

```
sudo systemctl restart apache2
```

Enfin, changer les credentials dans configs.php


## 🤝 Contribution

Les contributions sont les bienvenues ! Consultez le fichier [CONTRIBUTING.md](CONTRIBUTING.md) pour plus de détails.

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus d'informations.

---
Développé avec ❤️ par [Mendel Bellaiche](https://github.com/mendelbellaiche)
