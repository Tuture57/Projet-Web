<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PiloteController extends AbstractController
{
    #[Route('/pilote', name: 'app_pilote')]
    public function index(): Response
    {
        return $this->render('pilote/index.html.twig', [
            'controller_name' => 'PiloteController',
        ]);
    }
}
