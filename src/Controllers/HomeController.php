<?php

namespace Controllers;

use Core\Attributes\Route;
use Core\AbstractController;

class HomeController extends AbstractController
{
    /**
     * Affiche la page d'accueil
     */
    // #[Route('/', method: 'GET', namespace: '\\StarterKit\\Controllers\\')]
    #[Route('/', method: 'GET')]
    public function index()
    {
        $this->render('standard/home', [
            'title' => 'Mon site de Flashcards',
        ]);
    }


    /**
     * Affiche la page d'accueil [Par exemple, dans un controller UserController]
     */
    #[Route('/dashboard', method: 'GET')]
    public function dashboard()
    {
        $this->denyAccessUnlessGranted();

        $this->render('standard/dashboard', [
            'title' => 'Mon site de Flashcards',
        ]);
    }

}