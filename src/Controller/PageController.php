<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // Affichage d'une page différente selon que l'utilisateur est connecté ou non
        if (!$this->getUser()) {
            return $this->render('page/welcome.html.twig');
        } else {
            return $this->render('page/home.html.twig');
        }
    }

    #[Route('/aide', name: 'aide')]
    #[IsGranted('ROLE_STUDENT')]
    public function aide(): Response
    {
        // Page d'aide accessible uniquement aux étudiants
        return $this->render('page/aide.html.twig');
    }

    #[Route('/mentions-legales', name: 'mentions_legales')]
    public function mentionsLegales(): Response
    {
        // Page des mentions légales
        return $this->render('page/mentions_legales.html.twig');
    }

    #[Route('/conditions-generales-utilisation', name: 'cgu')]
    public function cgu(): Response
    {
        // Page des conditions générales d'utilisation
        return $this->render('page/cgu.html.twig');
    }
}