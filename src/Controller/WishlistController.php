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
        // Vérification manuelle des droits d'accès pour les administrateurs
        if (!$this->isGranted('ROLE_STUDENT') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Seuls les étudiants et administrateurs peuvent accéder à cette page');
        }
        
        $user = $this->getUser();
        
        // Récupération des offres en favoris
        $wishlistOffers = $user->getWishListOffers();
        
        return $this->render('wishlist/index.html.twig', [
            'offers' => $wishlistOffers,
        ]);
    }
}
