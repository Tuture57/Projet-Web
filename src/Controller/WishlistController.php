<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WishlistController extends AbstractController
{
    #[Route('/wishlist', name: 'wishlist_index')]
    #[IsGranted('ROLE_STUDENT', message: 'Seuls les étudiants et administrateurs peuvent accéder à cette page')]
    public function index(): Response
    {
        // Si c'est un admin et non un étudiant, vérifier manuellement l'accès
        if (!$this->isGranted('ROLE_STUDENT') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Seuls les étudiants et administrateurs peuvent accéder à cette page');
        }
        
        $user = $this->getUser();
        
        // Récupérer toutes les offres en favoris de l'utilisateur
        $wishlistOffers = $user->getWishListOffers();
        
        return $this->render('wishlist/index.html.twig', [
            'offers' => $wishlistOffers,
        ]);
    }
}
