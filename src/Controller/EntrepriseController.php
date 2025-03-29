<?php

namespace App\Controller;

use App\Repository\CompanyRepository;
use App\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Company;
use App\Form\CompanyType;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

    // Les routes statiques doivent être AVANT les routes avec paramètres
    #[Route('/entreprises/new', name: 'entreprise_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($company);
            $entityManager->flush();

            $this->addFlash('success', 'L\'entreprise a été créée avec succès.');
            return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
        }

        return $this->render('entreprise/new.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/entreprises/stats', name: 'entreprise_stats')]
    public function stats(): Response
    {
        return $this->render('entreprise/stats.html.twig');
    }

    // Les routes avec paramètres doivent être APRÈS les routes statiques
    #[Route('/entreprises/{id}', name: 'entreprise_show')]
    public function show(int $id, CompanyRepository $companyRepository, OfferRepository $offerRepository): Response
    {
        // Récupérer l'entreprise
        $company = $companyRepository->find($id);
        
        if (!$company) {
            throw $this->createNotFoundException('Entreprise non trouvée');
        }
        
        // Récupérer les offres actives de cette entreprise
        $offers = $offerRepository->findActiveOffersByCompany($company);
        
        return $this->render('entreprise/show.html.twig', [
            'company' => $company,
            'offers' => $offers,
        ]);
    }

    #[Route('/entreprises/{id}/edit', name: 'entreprise_edit')]
    public function edit(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {   
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'entreprise a été modifiée avec succès.');
            return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
        }

        return $this->render('entreprise/edit.html.twig', [
            'company' => $company,
            'form' => $form->createView(),
        ]);
    }
}
