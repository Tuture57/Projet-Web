<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/profil', name: 'user_profil')]
    public function profile(): Response
    {        
        return $this->render('user/profile.html.twig');
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
