<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class WishlistController extends AbstractController
{
    #[Route('/wishlist', name: 'wishlist')]
    #[IsGranted('ROLE_STUDENT')]
    public function index(): Response
    {
        return $this->render('wishlist/wishlist.html.twig');
    }
}
