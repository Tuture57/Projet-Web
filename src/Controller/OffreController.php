<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OffreController extends AbstractController
{
    #[Route('/offres', name: 'offre')]
    public function index(): Response
    {
        return $this->render('offre/index.html.twig', [
            'controller_name' => 'OffreController',
        ]);
    }

    #[Route('/offres/{id}', name: 'offre_show')]
    public function show(int $id): Response
    {
        // Logique pour afficher une offre spécifique
        return $this->render('offre/show.html.twig', [
            'id' => $id,
        ]);
    }
}
