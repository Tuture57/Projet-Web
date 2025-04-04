<?php

namespace App\Controller;

use App\Repository\CompanyRepository;
use App\Repository\OfferRepository;
use App\Repository\RatingRepository;
use App\Entity\Rating;
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
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\ImageUploadService;

class EntrepriseController extends AbstractController
{
    #[Route('/entreprises', name: 'entreprise')]
    #[IsGranted('ROLE_STUDENT')]
    public function index(Request $request, CompanyRepository $companyRepository, PaginatorInterface $paginator): Response
    {
        // Récupération des paramètres de filtrage et recherche
        $searchTerm = $request->query->get('search', '');
        $country = $request->query->get('country');
        $city = $request->query->get('city');
        $minRating = $request->query->get('minRating');
        $sortBy = $request->query->get('sortBy', 'name_asc');
        
        // Redirection si recherche vide
        if ($searchTerm === '' && $request->query->has('search')) {
            return $this->redirectToRoute('entreprise');
        }
        
        // Construction de la requête de base
        $queryBuilder = $companyRepository->createQueryBuilder('c')
            ->leftJoin('c.address', 'a');
            
        // Ajout du filtre de recherche
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('c.name LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        // Application des filtres géographiques
        if (!empty($country)) {
            $queryBuilder->andWhere('a.country = :country')
                ->setParameter('country', $country);
        }
        
        if (!empty($city)) {
            $queryBuilder->andWhere('a.city = :city')
                ->setParameter('city', $city);
        }
        
        // Filtre sur la note minimale
        if (!empty($minRating)) {
            $queryBuilder->andWhere('c.averageRating >= :minRating')
                ->setParameter('minRating', $minRating);
        }
        
        // Application du tri
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
        
        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5
        );
        
        // Obtention des listes pour les filtres
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

    #[Route('/entreprises/new', name: 'entreprise_new')]
    #[IsGranted('ROLE_PILOT')]
    public function new(Request $request, EntityManagerInterface $entityManager, ImageUploadService $imageService): Response
    {
        // Création d'une nouvelle entreprise avec adresse
        $company = new Company();
        $address = new Address();
        $company->setAddress($address);
        
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                try {
                    // Gestion de l'upload d'image
                    $newFilename = $imageService->uploadImage(
                        $imageFile,
                        'company',
                        $this->getParameter('company_logos_directory')
                    );
                    
                    $company->setImageName($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
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

    #[Route('/entreprises/{id}', name: 'entreprise_show')]
    #[IsGranted('ROLE_STUDENT')]
    public function show(int $id, CompanyRepository $companyRepository, OfferRepository $offerRepository): Response
    {
        // Récupération de l'entreprise
        $company = $companyRepository->find($id);
        
        if (!$company) {
            throw $this->createNotFoundException('Entreprise non trouvée');
        }
        
        // Récupération des offres actives
        $offers = $offerRepository->findActiveOffersByCompany($company);
        
        return $this->render('entreprise/show.html.twig', [
            'company' => $company,
            'offers' => $offers,
        ]);
    }

    #[Route('/entreprises/{id}/edit', name: 'entreprise_edit')]
    #[IsGranted('ROLE_PILOT')]
    public function edit(Request $request, Company $company, EntityManagerInterface $entityManager, ImageUploadService $imageService): Response
    {   
        $form = $this->createForm(CompanyType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                try {
                    // Suppression de l'ancienne image
                    if ($company->getImageName()) {
                        $imageService->removeImage(
                            $company->getImageName(),
                            $this->getParameter('company_logos_directory')
                        );
                    }
                    
                    // Upload de la nouvelle image
                    $newFilename = $imageService->uploadImage(
                        $imageFile,
                        'company',
                        $this->getParameter('company_logos_directory')
                    );
                    
                    $company->setImageName($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
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
    #[IsGranted('ROLE_PILOT')]
    public function delete(Request $request, Company $company, EntityManagerInterface $entityManager, ImageUploadService $imageService): Response
    {
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$company->getId(), $request->request->get('_token'))) {
            try {
                // Suppression de l'image associée
                if ($company->getImageName()) {
                    $imageService->removeImage(
                        $company->getImageName(), 
                        $this->getParameter('company_logos_directory')
                    );
                }

                // Suppression des offres et candidatures associées
                foreach ($company->getOffers() as $offer) {
                    foreach ($offer->getApplications() as $application) {
                        $entityManager->remove($application);
                    }
                    $entityManager->remove($offer);
                }

                // Suppression des évaluations associées
                foreach ($company->getRatings() as $rating) {
                    $entityManager->remove($rating);
                }

                // Suppression de l'adresse associée
                if ($company->getAddress()) {
                    $entityManager->remove($company->getAddress());
                }

                // Suppression de l'entreprise
                $entityManager->remove($company);
                $entityManager->flush();
                
                $this->addFlash('success', 'L\'entreprise a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('entreprise');
    }

    #[Route('/{id}/rate', name: 'entreprise_rate', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function rateCompany(
        Request $request, 
        Company $company, 
        EntityManagerInterface $entityManager,
        RatingRepository $ratingRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour noter une entreprise.');
            return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
        }

        // Validation de la note
        $score = (int) $request->request->get('rating');
        if ($score < 1 || $score > 5) {
            $this->addFlash('error', 'La note doit être comprise entre 1 et 5.');
            return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
        }

        // Vérification si l'utilisateur a déjà noté cette entreprise
        $existingRating = $ratingRepository->findOneBy([
            'user' => $user,
            'company' => $company
        ]);

        if ($existingRating) {
            // Mise à jour de la note existante
            $existingRating->setScore($score);
            $this->addFlash('success', 'Votre note a été mise à jour.');
        } else {
            // Création d'une nouvelle note
            $rating = new Rating();
            $rating->setUser($user);
            $rating->setCompany($company);
            $rating->setScore($score);
            $entityManager->persist($rating);
            $this->addFlash('success', 'Votre note a été enregistrée.');
        }
        
        // Enregistrement des modifications
        $entityManager->flush();

        // Calcul de la nouvelle moyenne
        $allRatings = $ratingRepository->findAllScoresByCompany($company);
        $totalRatings = count($allRatings);
        $sum = array_sum(array_column($allRatings, 'score'));
        $manualAverage = $totalRatings > 0 ? round($sum / $totalRatings, 1) : 0;
        
        // Récupération de la moyenne calculée par la méthode du repository
        $averageRating = $ratingRepository->calculateAverageRating($company);
        
        // Correction si l'algorithme donne un résultat erroné
        if (abs($averageRating - $manualAverage) > 0.1) {
            $averageRating = $manualAverage;
            $this->addFlash('info', "Correction de la moyenne : $manualAverage ($totalRatings notes, somme: $sum)");
        }
        
        $company->setAverageRating($averageRating);
        
        // Sauvegarde de la moyenne mise à jour
        $entityManager->flush();

        return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
    }

    #[Route('/{id}/remove-rating', name: 'entreprise_remove_rating', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function removeRating(
        Company $company, 
        EntityManagerInterface $entityManager,
        RatingRepository $ratingRepository
    ): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer une note.');
            return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
        }

        // Vérification si l'utilisateur a noté cette entreprise
        $existingRating = $ratingRepository->findOneBy([
            'user' => $user,
            'company' => $company
        ]);

        if ($existingRating) {
            try {
                // Suppression de la note
                $entityManager->remove($existingRating);
                $entityManager->flush();
                
                // Recalcul de la moyenne
                $allRatings = $ratingRepository->findAllScoresByCompany($company);
                $totalRatings = count($allRatings);
                
                if ($totalRatings > 0) {
                    $sum = array_sum(array_column($allRatings, 'score'));
                    $averageRating = round($sum / $totalRatings, 1);
                } else {
                    $averageRating = 0;
                }
                
                // Mise à jour de la moyenne dans l'entreprise
                $company->setAverageRating($averageRating);
                $entityManager->flush();
                
                $this->addFlash('success', 'Votre note a été supprimée avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Vous n\'avez pas encore noté cette entreprise.');
        }

        return $this->redirectToRoute('entreprise_show', ['id' => $company->getId()]);
    }
    
}
