# Journal des modifications (Changelog)

Toutes les modifications notables de ce projet seront documentées dans ce fichier.
Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/).

## [1.0.0] - 2024-05-22

### Ajouté
-   **Système de Flash** : Affichage des messages flash (succès, erreur) dans `base.twig`.
-   **Documentation GitHub** : Ajout des fichiers `LICENSE`, `CONTRIBUTING.md` et `CHANGELOG.md`.
-   **Structure de Base** : Mise en place du routeur, des contrôleurs et du moteur de template Twig.
-   **Gestion des Utilisateurs** : Inscription et connexion avec gestion de session.

### Modifié
-   Mise à jour de `base.twig` pour inclure les alertes Bootstrap dynamiques.
-   Amélioration de la sécurité avec `password_hash` et `password_verify`.
-   Nettoyage du dépôt (mise à jour du `.gitignore`).

---
*Ce fichier suit la numérotation des versions [Semantic Versioning](https://semver.org/lang/fr/).*
