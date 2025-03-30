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
use App\Entity\Address;
use App\Form\CompanyType;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Offer;
use App\Entity\Rating;
use App\Entity\Application;

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
        
        // Définir une valeur par défaut pour numberOfInterns
        $company->setNumberOfInterns(0);
        
        // Créer une adresse vide
        $address = new Address();
        $company->setAddress($address);
        
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

    #[Route('/entreprises/{id}/delete', name: 'entreprise_delete', methods: ['POST'])]
    public function delete(Request $request, Company $company, EntityManagerInterface $entityManager): Response
    {
        // Vérification du token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            try {
                // Remove associated offers
                foreach ($company->getOffers() as $offer) {
                    // Remove associated applications
                    foreach ($offer->getApplications() as $application) {
                        $entityManager->remove($application);
                    }
                    $entityManager->remove($offer);
                }

                // Remove associated ratings
                foreach ($company->getRatings() as $rating) {
                    $entityManager->remove($rating);
                }

                // Remove associated address
                if ($company->getAddress()) {
                    $entityManager->remove($company->getAddress());
                }

                // Supprimer l'entreprise et toutes ses relations en cascade
                $entityManager->remove($company);
                $entityManager->flush();
                
                $this->addFlash('success', 'L\'entreprise a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Impossible de supprimer cette entreprise: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('entreprise');
    }
}
