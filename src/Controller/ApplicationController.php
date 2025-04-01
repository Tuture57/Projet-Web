<?php

namespace App\Controller;

use App\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApplicationController extends AbstractController
{
    #[Route('/applications/{id}', name: 'application_show', methods: ['GET'])]
    public function show(Application $application): Response
    {
        // Vérifier que l'utilisateur a le droit de voir cette candidature
        if (!$this->isGranted('ROLE_ADMIN') && 
            !$this->isGranted('ROLE_PILOT') && 
            !($this->isGranted('ROLE_STUDENT') && $application->getUser() === $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
        
        return $this->render('application/show.html.twig', [
            'application' => $application
        ]);
    }
    
    // Vous aurez aussi besoin de la route application_new
    #[Route('/offres/{id}/apply', name: 'application_new', methods: ['GET', 'POST'])]
    public function new(/* paramètres */): Response
    {
        // Code pour créer une nouvelle candidature
    }
}