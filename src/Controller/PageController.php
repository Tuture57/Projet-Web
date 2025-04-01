<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        if (!$this->getUser()) {
            return $this->render('page/welcome.html.twig');
        } else {
            return $this->render('page/home.html.twig');
        }
    }

    #[Route('/aide', name: 'aide')]
    public function aide(): Response
    {
        return $this->render('page/aide.html.twig');
    }
}