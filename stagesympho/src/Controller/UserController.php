<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/profil', name: 'user_profil')]
    public function profile(): Response
    {
        // Plus tard, vous récupérerez les données de l'utilisateur connecté
        // $user = $this->getUser();
        
        return $this->render('user/profile.html.twig', [
            // Données fictives pour le moment
            'user' => [
                'prenom' => 'John',
                'nom' => 'Doe',
                'email' => 'john.doe@example.com',
                'role' => 'Étudiant',
                'promotion' => 'A2 Informatique',
                'createdAt' => new \DateTime('2024-01-15'),
            ]
        ]);
    }

    #[Route('/profil/notifications', name: 'notifications')]
    public function notifications(): Response
    {
        // Plus tard, vous récupérerez les notifications de l'utilisateur connecté
        // $user = $this->getUser();
        
        return $this->render('user/notifications.html.twig', [
            // Données fictives pour le moment
            'notifications' => [
                'Vous avez un nouveau message de votre tuteur.',
                'Votre stage commence dans 2 semaines.',
                'Nouveau document disponible dans votre espace étudiant.',
            ]
        ]);
    }
}
