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
use Symfony\Component\String\Slugger\SluggerInterface;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprise', name: 'entreprise')]
    public function index(Request $request, CompanyRepository $companyRepository, PaginatorInterface $paginator): Response
    {
        // Récupérer le terme de recherche et les paramètres de filtre
        $searchTerm = $request->query->get('search', '');
        $country = $request->query->get('country');
        $city = $request->query->get('city');
        $minRating = $request->query->get('minRating');
        $sortBy = $request->query->get('sortBy', 'name_asc');
        
        // Redirection si search est vide mais présent
        if ($searchTerm === '' && $request->query->has('search')) {
            return $this->redirectToRoute('entreprise');
        }
        
        // Créer la requête de base (version optimisée)
        $queryBuilder = $companyRepository->createQueryBuilder('c')
            ->leftJoin('c.address', 'a'); // Jointure avec l'adresse pour filtrer
            
        // Ajouter la recherche si présente
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('c.name LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        // Ajouter les filtres
        if (!empty($country)) {
            $queryBuilder->andWhere('a.country = :country')
                ->setParameter('country', $country);
        }
        
        if (!empty($city)) {
            $queryBuilder->andWhere('a.city = :city')
                ->setParameter('city', $city);
        }
        
        if (!empty($minRating)) {
            $queryBuilder->andWhere('c.averageRating >= :minRating')
                ->setParameter('minRating', $minRating);
        }
        
        // Appliquer le tri
        switch ($sortBy) {
            case 'name_desc':
                $queryBuilder->orderBy('c.name', 'DESC');
                break;
            case 'rating_desc':
                $queryBuilder->orderBy('c.averageRating', 'DESC');
                break;
            case 'rating_asc':
                $queryBuilder->orderBy('c.averageRating', 'ASC');
                break;
            case 'name_asc':
            default:
                $queryBuilder->orderBy('c.name', 'ASC');
        }
        
        // Paginer les résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5 // Nombre d'éléments par page
        );
        
        // Obtenir la liste des pays et villes pour les filtres
        $countries = $companyRepository->findUniqueCountries();
        $cities = $companyRepository->findUniqueCities($country);
        
        return $this->render('entreprise/index.html.twig', [
            'companies' => $pagination,
            'pagination' => $pagination,
            'countries' => $countries,
            'cities' => $cities,
            'filters' => [
                'search' => $searchTerm,
                'country' => $country,
                'city' => $city,
                'minRating' => $minRating,
                'sortBy' => $sortBy
            ]
        ]);
    }

    // Les routes statiques doivent être AVANT les routes avec paramètres
    #[Route('/entreprises/new', name: 'entreprise_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
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
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logoFile')->getData();
            
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();
                
                try {
                    $logoFile->move(
                        $this->getParameter('company_logos_directory'),
                        $newFilename
                    );
                    
                    $company->setLogoName($newFilename);
                } catch (FileException $e) {
                    // Gérer l'exception
                }
            }
            
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
    public function edit(Request $request, Company $company, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {   
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logoFile')->getData();
            
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();
                
                try {
                    $logoFile->move(
                        $this->getParameter('company_logos_directory'),
                        $newFilename
                    );
                    
                    // Update the logo name in the database
                    $company->setLogoName($newFilename);
                } catch (FileException $e) {
                    // Handle exception
                }
            }
            
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
