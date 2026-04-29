<?php

namespace Controllers;

use Random\RandomException;
use Core\Attributes\Route;
use Core\AuthThrottle;
use Core\AbstractController;
use Core\CsrfHelper;
use Models\User;

class AuthController extends AbstractController
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

        // Validation CSRF
        if (!CsrfHelper::validate($_POST['_csrf_token'] ?? null)) {
            $this->addFlash('error', 'Requête invalide. Veuillez réessayer.');
            header('Location: /login');
            exit;
        }

        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $throttle = new AuthThrottle();

        // 1. Vérifier d'abord le blocage global par IP
        if ($throttle->isIpBlocked($ip)) {
            usleep(random_int(300000, 500000));
            $this->addFlash('error', 'Identifiants incorrects.');
            header('Location: /login');
            exit;
        }

        // 2. Vérifier ensuite le blocage par couple email+IP
        if ($throttle->isBlocked($email, $ip)) {
            usleep(random_int(300000, 500000));
            $this->addFlash('error', 'Identifiants incorrects.');
            header('Location: /login');
            exit;
        }

        // 3. On cherche l'utilisateur (instance de User)
        $user = User::findByEmail($email);

        // 4. On vérifie si l'utilisateur existe et si le mot de passe est bon
        if ($user && password_verify($password, $user->getPassword())) {

            $throttle->clear($email, $ip);
            $throttle->clearIp($ip);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // anti session fixation
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user->getId();
            $_SESSION['user_name'] = $user->getName();

            $this->addFlash('success', 'Ravi de vous revoir, ' . $user->getName() . ' !');
            header('Location: /dashboard');
            exit;
        }

        $throttle->registerFailure($email, $ip);
        $throttle->registerIpFailure($ip);
        try {
            usleep(random_int(300000, 500000));
        } catch (RandomException $e) {
            // TODO: logger l'erreur
        }
        $this->addFlash('error', 'Identifiants incorrects.');
        header('Location: /login');
        exit;
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

        // Validation CSRF
        if (!CsrfHelper::validate($_POST['_csrf_token'] ?? null)) {
            $this->addFlash('error', 'Requête invalide. Veuillez réessayer.');
            header('Location: /register');
            exit;
        }

        // 1. Récupération des données
        $name            = $_POST['name'] ?? '';
        $email           = $_POST['email'] ?? '';
        $password        = $_POST['password'] ?? '';
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
            $this->addFlash('error', 'Identifiant incorrects.');
            header('Location: /register');
            return;
        }

        // 4. Création de l'instance de l'entité User
        $user = new User();
        $user->setName($name)
            ->setEmail($email)
            ->setPassword($password);

        // 5. Sauvegarde en base de données
        if ($user->save()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            session_regenerate_id(true);

            $_SESSION['user_id']   = $user->getId();
            $_SESSION['user_name'] = $user->getName();

            header('Location: /dashboard');
            exit;
        } else {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'inscription.');
        }
    }

    #[Route('/logout', method: 'POST')]
    public function logout()
    {
        // Validation CSRF
        if (!CsrfHelper::validate($_POST['_csrf_token'] ?? null)) {
            header('Location: /');
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        header('Location: /');
        exit;
    }
}