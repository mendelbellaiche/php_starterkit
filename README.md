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

2.  **Installer les dépendances**
    ```bash
    composer install
    ```

3.  **Base de données**
    Créez une base de données MySQL et importez le schéma fourni :
    ```bash
    mysql -u votre_utilisateur -p votre_base_de_donnees < db.sql
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
    chmod +x launch.sh
    ./launch.sh
    ```

## 📂 Structure du projet

-   `src/` : Code source de l'application (Controllers, Models, Core).
-   `public/` : Point d'entrée de l'application (index.php) et fichiers statiques (CSS, JS, Images).
-   `src/Views/` : Fichiers templates Twig.
-   `config.php` : Fichier de configuration globale.
-   `db.sql` : Script de création de la base de données.

## 🤝 Contribution

Les contributions sont les bienvenues ! Consultez le fichier [CONTRIBUTING.md](CONTRIBUTING.md) pour plus de détails.

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus d'informations.

---
Développé avec ❤️ par [Mendel Bellaiche](https://github.com/mendelbellaiche)
