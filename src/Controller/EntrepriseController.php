<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprises', name: 'entreprise')]
    public function index(CompanyRepository $companyRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $companyRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->getQuery();
        
        $page = $request->query->getInt('page', 1);
        $limit = 5; 
        
        $pagination = $paginator->paginate(
            $query,
            $page,
            $limit
        );
        
        return $this->render('entreprise/index.html.twig', [
            'companies' => $pagination,
            'pagination' => $pagination,
        ]);
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
