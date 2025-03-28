<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprises', name: 'entreprise')]
    public function index(): Response
    {
        return $this->render('entreprise/index.html.twig');
    }

    #[Route('/entreprises/{id}', name: 'entreprise_show')]
    public function show(int $id): Response
    {
        return $this->render('entreprise/show.html.twig', [
            'id' => $id,
        ]);
    }

    #[Route('/entreprises/new', name: 'entreprise_new')]
    public function new(): Response
    {
        return $this->render('entreprise/new.html.twig');
    }

    #[Route('/entreprises/stats', name: 'entreprise_stats')]
    public function stats(): Response
    {
        return $this->render('entreprise/stats.html.twig');
    }
}
