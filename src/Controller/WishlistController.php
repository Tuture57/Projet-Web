<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WishlistController extends AbstractController
{
    #[Route('/wishlist', name: 'wishlist_index')]
    #[IsGranted('ROLE_STUDENT')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Récupérer toutes les offres en favoris de l'utilisateur
        $wishlistOffers = $user->getWishListOffers();
        
        return $this->render('wishlist/index.html.twig', [
            'offers' => $wishlistOffers,
        ]);
    }
}
