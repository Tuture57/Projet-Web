<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Application;
use App\Entity\User;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use App\Repository\ApplicationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class OffreController extends AbstractController
{
    #[Route('/offres', name: 'offre')]
    #[IsGranted('ROLE_STUDENT')]
    public function index(
        Request $request, 
        OfferRepository $offerRepository, 
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response
    {
        // Création du queryBuilder de base
        $queryBuilder = $offerRepository->createQueryBuilder('o')
            ->leftJoin('o.company', 'c')
            ->leftJoin('o.skills', 's');
        
        // Filtrage des offres inactives pour les étudiants
        if ($this->isGranted('ROLE_STUDENT') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PILOT')) {
            $queryBuilder->andWhere('o.isActive = :isActive')
                ->setParameter('isActive', true);
        }
        
        // Récupération des paramètres de recherche
        $search = $request->query->get('search', '');
        $selectedCompany = $request->query->get('company');
        $selectedSkill = $request->query->get('skill');
        $sortBy = $request->query->get('sortBy', 'creation_desc');
        
        // Filtrage par texte
        if (!empty($search)) {
            $queryBuilder->andWhere('o.title LIKE :search OR c.name LIKE :search OR o.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Filtrage par entreprise
        if (!empty($selectedCompany)) {
            $queryBuilder->andWhere('c.id = :companyId')
                ->setParameter('companyId', $selectedCompany);
        }
        
        // Filtrage par compétence
        if (!empty($selectedSkill)) {
            $queryBuilder->andWhere('s.id = :skillId')
                ->setParameter('skillId', $selectedSkill);
        }
        
        // Application du tri
        switch ($sortBy) {
            case 'title_asc':
                $queryBuilder->orderBy('o.title', 'ASC');
                break;
            case 'title_desc':
                $queryBuilder->orderBy('o.title', 'DESC');
                break;
            case 'company_asc':
                $queryBuilder->orderBy('c.name', 'ASC');
                break;
            case 'company_desc':
                $queryBuilder->orderBy('c.name', 'DESC');
                break;
            case 'creation_asc':
                $queryBuilder->orderBy('o.creationDate', 'ASC');
                break;
            case 'remuneration_desc':
                $queryBuilder->orderBy('o.remuneration', 'DESC');
                break;
            case 'remuneration_asc':
                $queryBuilder->orderBy('o.remuneration', 'ASC');
                break;
            case 'creation_desc':
            default:
                $queryBuilder->orderBy('o.creationDate', 'DESC');
        }
        
        // Récupération des entreprises pour le filtre
        $companies = $entityManager->createQuery(
            'SELECT DISTINCT c.id, c.name 
             FROM App\Entity\Company c 
             JOIN App\Entity\Offer o WITH o.company = c 
             ORDER BY c.name ASC'
        )->getResult();
        
        // Formatage pour le template
        $companiesOptions = [];
        foreach ($companies as $company) {
            $companiesOptions[] = [
                'value' => $company['id'],
                'label' => $company['name']
            ];
        }
        
        // Récupération des compétences pour le filtre
        $skills = $entityManager->createQuery(
            'SELECT DISTINCT s.id, s.name 
             FROM App\Entity\Skill s 
             JOIN s.offers o 
             ORDER BY s.name ASC'
        )->getResult();
        
        // Formatage pour le template
        $skillsOptions = [];
        foreach ($skills as $skill) {
            $skillsOptions[] = [
                'value' => $skill['id'],
                'label' => $skill['name']
            ];
        }
        
        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5 // Nombre d'offres par page
        );
        
        return $this->render('offre/index.html.twig', [
            'offers' => $pagination,
            'companies' => $companiesOptions,
            'skills' => $skillsOptions,
            'search' => $search,
            'selectedCompany' => $selectedCompany,
            'selectedSkill' => $selectedSkill,
            'sortBy' => $sortBy
        ]);
    }
    
    #[Route('/offres/new', name: 'offre_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PILOT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offer = new Offer();
        
        // Initialisation des valeurs par défaut
        $offer->setCreationDate(new \DateTime());
        $offer->setIsActive(true);
        
        // Détermination des rôles
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isPilot = $this->isGranted('ROLE_PILOT');
        
        // Récupération de l'entreprise du pilote
        $pilotCompany = null;
        if ($isPilot && $this->getUser() && method_exists($this->getUser(), 'getCompany')) {
            $pilotCompany = $this->getUser()->getCompany();
            $offer->setCompany($pilotCompany);
        }
        
        $form = $this->createForm(OfferType::class, $offer, [
            'is_admin' => $isAdmin,
            'is_pilot' => $isPilot,
            'pilot_company' => $pilotCompany,
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Attribution de l'entreprise pour un pilote
            if ($isPilot && !$isAdmin && $pilotCompany) {
                $offer->setCompany($pilotCompany);
            }
            
            $entityManager->persist($offer);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été créée avec succès.');
            
            return $this->redirectToRoute('offre_show', [
                'id' => $offer->getId(),
            ]);
        }
        
        return $this->render('offre/new.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/offres/{id}', name: 'offre_show', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function show(Offer $offre): Response
    {
        $hasApplied = false;
        $applicationStatus = null;
        $applicationId = null;
        $isInWishlist = false;
        
        // Vérification si l'utilisateur a déjà postulé
        if ($this->getUser() && $this->isGranted('ROLE_STUDENT')) {
            foreach ($offre->getApplications() as $application) {
                if ($application->getUser() === $this->getUser()) {
                    $hasApplied = true;
                    $applicationStatus = $application->getStatus();
                    $applicationId = $application->getId();
                    break;
                }
            }
            
            // Vérification si l'offre est dans la liste de souhaits
            if ($offre->getWishListUsers()->contains($this->getUser())) {
                $isInWishlist = true;
            }
        }
        
        return $this->render('offre/show.html.twig', [
            'offer' => $offre,
            'hasApplied' => $hasApplied,
            'applicationStatus' => $applicationStatus,
            'applicationId' => $applicationId,
            'isInWishlist' => $isInWishlist
        ]);
    }

    #[Route('/offres/{id}/edit', name: 'offre_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PILOT')]
    public function edit(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Détermination des rôles
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isPilot = $this->isGranted('ROLE_PILOT');
        
        // Vérification des droits d'accès
        if (!$isAdmin && !$isPilot) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre.');
        }
        
        // Récupération de l'entreprise du pilote
        $pilotCompany = null;
        if ($isPilot && $this->getUser() && method_exists($this->getUser(), 'getCompany')) {
            $pilotCompany = $this->getUser()->getCompany();
        }
        
        $form = $this->createForm(OfferType::class, $offer, [
            'is_admin' => $isAdmin,
            'is_pilot' => $isPilot,
            'pilot_company' => $pilotCompany,
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été modifiée avec succès.');
            
            return $this->redirectToRoute('offre_show', [
                'id' => $offer->getId(),
            ]);
        }
        
        return $this->render('offre/edit.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/offres/{id}/delete', name: 'offre_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PILOT')]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Vérification des droits d'accès
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PILOT')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette offre.');
        }
        
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$offer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($offer);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été supprimée avec succès.');
        }
        
        return $this->redirectToRoute('offre');
    }
    
    #[Route('/offres/{id}/toggle', name: 'offre_toggle', methods: ['GET'])]
    #[IsGranted('ROLE_PILOT')]
    public function toggle(Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Vérification des droits d'accès
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PILOT')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre.');
        }
        
        // Inversion de l'état actif
        $offer->setIsActive(!$offer->isActive());
        $entityManager->flush();
        
        $status = $offer->isActive() ? 'activée' : 'désactivée';
        $this->addFlash('success', "L'offre a été {$status} avec succès.");
        
        return $this->redirectToRoute('offre_show', [
            'id' => $offer->getId(),
        ]);
    }
    
    #[Route('/toggle-wishlist/{id}', name: 'offre_toggle_wishlist')]
    #[IsGranted('ROLE_USER')]
    public function toggleWishlist(
        Offer $offer,
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response {
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('toggle-wishlist', $request->request->get('token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
        
        // Récupération de l'utilisateur courant
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérification du rôle
        if (!$this->isGranted('ROLE_STUDENT') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Seuls les étudiants et administrateurs peuvent ajouter des offres à leurs favoris.');
            return $this->redirectToRoute('offre_show', ['id' => $offer->getId()]);
        }
        
        // Restriction pour les pilotes
        if ($this->isGranted('ROLE_PILOT') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Les pilotes ne peuvent pas ajouter d\'offres à leurs favoris.');
            return $this->redirectToRoute('offre_show', ['id' => $offer->getId()]);
        }
        
        // Vérification si l'offre est déjà dans la liste de souhaits
        $isInWishlist = false;
        foreach ($user->getWishListOffers() as $wishlistOffer) {
            if ($wishlistOffer->getId() === $offer->getId()) {
                $isInWishlist = true;
                break;
            }
        }
        
        // Modification de la liste de souhaits
        if ($isInWishlist) {
            $user->removeWishListOffer($offer);
            $this->addFlash('success', 'Offre retirée de vos favoris.');
        } else {
            $user->addWishListOffer($offer);
            $this->addFlash('success', 'Offre ajoutée à vos favoris.');
        }
        
        // Sauvegarde des modifications
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Redirection
        $referrer = $request->headers->get('referer');
        if (str_contains($referrer, 'wishlist')) {
            return $this->redirectToRoute('wishlist_index');
        }
        
        return $this->redirectToRoute('offre_show', ['id' => $offer->getId()]);
    }

    #[Route('/offres/{id}/apply', name: 'offre_apply', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function apply(Offer $offer): Response
    {
        // Redirection vers le contrôleur de candidature
        return $this->redirectToRoute('candidature_postuler', [
            'id' => $offer->getId(),
        ]);
    }

    #[Route('/offres/{id}/apply', name: 'offre_apply', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function applyPost(Offer $offer, EntityManagerInterface $entityManager, ApplicationRepository $applicationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérification si l'utilisateur a déjà postulé
        $existingApplication = $applicationRepository->findOneBy([
            'user' => $user,
            'offer' => $offer
        ]);
        
        if ($existingApplication) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('offre_show', ['id' => $offer->getId()]);
        }
        
        // Création d'une nouvelle candidature
        $application = new Application();
        $application->setUser($user);
        $application->setOffer($offer);
        $application->setStatus('Pending');
        $application->setApplicationDate(new \DateTime());
        
        $entityManager->persist($application);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre candidature a été envoyée avec succès.');
        
        return $this->redirectToRoute('candidature_index');
    }
    
}