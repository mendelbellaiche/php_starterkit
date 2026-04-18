<?php

namespace StarterKit\Controllers;

use StarterKit\Controllers\Controller;
use StarterKit\Core\Attributes\Route;
use StarterKit\Models\User;

class AuthController extends Controller
{

    /**
     * Empêche l'accès aux pages de login/register si l'utilisateur est connecté
     */
    private function redirectIfLoggedIn(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->addFlash('info', 'Vous êtes déjà connecté.');
            header('Location: /dashboard');
            exit;
        }
    }

    #[Route('/login', method: 'GET')]
    public function showLogin()
    {
        $this->redirectIfLoggedIn();

        // On affiche le formulaire
        $this->render('auth/login');
    }

    #[Route('/login', method: 'POST')]
    public function login()
    {
        $this->redirectIfLoggedIn();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // 1. On cherche l'utilisateur (instance de User)
        $user = User::findByEmail($email);

        // 2. On vérifie si l'utilisateur existe et si le mot de passe est bon
        if ($user && password_verify($password, $user->getPassword())) {

            // 3. On ouvre la session PHP
            session_start();
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_name'] = $user->getName();

            $this->addFlash('success', 'Ravi de vous revoir, ' . $user->getName() . ' !');

            // Redirection vers le dashboard
            header('Location: /dashboard');
            exit;
        } else {
            $this->addFlash('error', 'Identifiants incorrects.');
            header('Location: /login');
            exit;
        }
    }

    #[Route('/register', method: 'GET')]
    public function showRegister()
    {
        $this->redirectIfLoggedIn();
        $this->render('auth/register');
    }

    /**
     * @throws \Exception
     */
    #[Route('/register', method: 'POST')]
    public function register()
    {
        $this->redirectIfLoggedIn();

        // 1. Récupération des données
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // 2. Validations de base
        if (empty($name) || empty($email) || empty($password)) {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            header('Location: /register');
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->addFlash('error', 'Erreur : Les mots de passe ne correspondent pas.');
            header('Location: /register');
            return;
        }

        // 3. Vérifier si l'email existe déjà
        if (User::findByEmail($email)) {
            $this->addFlash('error', 'Cet email est déjà utilisé.');
            header('Location: /register');
            return;
        }

        // 4. Création de l'instance de l'entité User
        $user = new User();
        $user->setName($name)
            ->setEmail($email)
            ->setPassword($password); // Le hachage est automatique dans le setter

        // 5. Sauvegarde en base de données
        if ($user->save()) {
            // Optionnel : Connecter l'utilisateur immédiatement après l'inscription
            if (session_status() === PHP_SESSION_NONE) session_start();

            // On récupère l'ID généré pour le mettre en session (il faut rafraîchir l'objet ou le chercher)
            $loggedUser = User::findByEmail($email);
            // Pour la session, on récupère via le getter
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['user_name'] = $user->getName();

            // Redirection vers le dashboard
            header('Location: /dashboard');
            exit;
        } else {

            $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
        }
    }

    #[Route('/logout', method: 'GET')]
    public function logout()
    {
        // 1. On s'assure que la session est démarrée pour pouvoir la manipuler
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. On vide toutes les variables de session
        $_SESSION = [];

        // 3. On détruit physiquement la session sur le serveur
        session_destroy();

        // 4. Redirection vers la page d'accueil (Landing Page)
        header('Location: /');
        exit;
    }
}