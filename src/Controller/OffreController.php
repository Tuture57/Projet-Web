<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Application;
use App\Entity\User;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

final class OffreController extends AbstractController
{
    #[Route('/offres', name: 'offre')]
    public function index(
        Request $request, 
        OfferRepository $offerRepository, 
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response
    {
        // Créer le queryBuilder de base
        $queryBuilder = $offerRepository->createQueryBuilder('o')
            ->leftJoin('o.company', 'c')
            ->leftJoin('o.skills', 's');
        
        // Récupérer les paramètres de recherche
        $search = $request->query->get('search', '');
        $selectedCompany = $request->query->get('company');
        $selectedSkill = $request->query->get('skill');
        $sortBy = $request->query->get('sortBy', 'creation_desc');
        
        // Appliquer le filtre de recherche textuelle
        if (!empty($search)) {
            $queryBuilder->andWhere('o.title LIKE :search OR c.name LIKE :search OR o.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Appliquer le filtre par entreprise
        if (!empty($selectedCompany)) {
            $queryBuilder->andWhere('c.id = :companyId')
                ->setParameter('companyId', $selectedCompany);
        }
        
        // Appliquer le filtre par compétence
        if (!empty($selectedSkill)) {
            $queryBuilder->andWhere('s.id = :skillId')
                ->setParameter('skillId', $selectedSkill);
        }
        
        // Appliquer le tri
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
        
        // Récupérer les entreprises pour le filtre
        $companies = $entityManager->createQuery(
            'SELECT DISTINCT c.id, c.name 
             FROM App\Entity\Company c 
             JOIN App\Entity\Offer o WITH o.company = c 
             ORDER BY c.name ASC'
        )->getResult();
        
        // Formater pour le template
        $companiesOptions = [];
        foreach ($companies as $company) {
            $companiesOptions[] = [
                'value' => $company['id'],
                'label' => $company['name']
            ];
        }
        
        // Récupérer les compétences pour le filtre
        $skills = $entityManager->createQuery(
            'SELECT DISTINCT s.id, s.name 
             FROM App\Entity\Skill s 
             JOIN s.offers o 
             ORDER BY s.name ASC'
        )->getResult();
        
        // Formater pour le template
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

    #[Route('/offres/{id}', name: 'offre_show', methods: ['GET'])]
    public function show(Offer $offre): Response
    {
        $hasApplied = false;
        $applicationStatus = null;
        $isInWishlist = false;
        
        // Vérifier si l'utilisateur connecté a déjà postulé
        if ($this->getUser() && $this->isGranted('ROLE_STUDENT')) {
            foreach ($offre->getApplications() as $application) {
                if ($application->getUser() === $this->getUser()) {
                    $hasApplied = true;
                    $applicationStatus = $application->getStatus();
                    break;
                }
            }
            
            // Vérifier si l'offre est dans la liste de souhaits de l'utilisateur
            if ($offre->getWishListUsers()->contains($this->getUser())) {
                $isInWishlist = true;
            }
        }
        
        return $this->render('offre/show.html.twig', [
            'offer' => $offre,
            'hasApplied' => $hasApplied,
            'applicationStatus' => $applicationStatus,
            'isInWishlist' => $isInWishlist
        ]);
    }
    
    #[Route('/offres/new', name: 'offre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $offer = new Offer();
        
        // Si l'utilisateur est une entreprise, associer automatiquement
        if ($this->isGranted('ROLE_PILOT') && $this->getUser()->getCompany()) {
            $offer->setCompany($this->getUser()->getCompany());
        }
        
        // Date de création par défaut
        $offer->setCreationDate(new \DateTime());
        $offer->setIsActive(true);
        
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
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
    
    #[Route('/offres/{id}/edit', name: 'offre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut modifier cette offre
        if (!$this->isGranted('ROLE_ADMIN') && 
            (!$this->isGranted('ROLE_PILOT') || $this->getUser()->getCompany()->getId() !== $offer->getCompany()->getId())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre.');
        }
        
        $form = $this->createForm(OfferType::class, $offer);
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
    public function delete(Request $request, Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut supprimer cette offre
        if (!$this->isGranted('ROLE_ADMIN') && 
            (!$this->isGranted('ROLE_PILOT') || $this->getUser()->getCompany()->getId() !== $offer->getCompany()->getId())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette offre.');
        }
        
        // Vérifier le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$offer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($offer);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'offre a été supprimée avec succès.');
        }
        
        return $this->redirectToRoute('offre');
    }
    
    #[Route('/offres/{id}/toggle', name: 'offre_toggle', methods: ['GET'])]
    public function toggle(Offer $offer, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut modifier cette offre
        if (!$this->isGranted('ROLE_ADMIN') && 
            (!$this->isGranted('ROLE_PILOT') || $this->getUser()->getCompany()->getId() !== $offer->getCompany()->getId())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette offre.');
        }
        
        // Inverser l'état actif/inactif
        $offer->setIsActive(!$offer->isActive());
        $entityManager->flush();
        
        $status = $offer->isActive() ? 'activée' : 'désactivée';
        $this->addFlash('success', "L'offre a été {$status} avec succès.");
        
        return $this->redirectToRoute('offre_show', [
            'id' => $offer->getId(),
        ]);
    }
    
    #[Route('/offres/{id}/toggle-wishlist', name: 'offre_toggle_wishlist', methods: ['POST'])]
    public function toggleWishlist(Offer $offer, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est un étudiant
        $this->denyAccessUnlessGranted('ROLE_STUDENT', null, 'Seuls les étudiants peuvent ajouter des offres en favoris.');
        
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('toggle-wishlist', $request->request->get('token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        // Ajouter ou retirer l'utilisateur de la liste des favoris
        if ($offer->getWishListUsers()->contains($user)) {
            $offer->removeWishListUser($user);
            $message = 'L\'offre a été retirée de vos favoris.';
        } else {
            $offer->addWishListUser($user);
            $message = 'L\'offre a été ajoutée à vos favoris.';
        }
        
        $entityManager->flush();
        $this->addFlash('success', $message);
        
        // Rediriger vers la page précédente
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('offre_show', ['id' => $offer->getId()]));
    }
    
    #[Route('/offres/{id}/applications', name: 'offre_applications', methods: ['GET'])]
    public function applications(Offer $offer): Response
    {
        // Vérifier que l'utilisateur peut voir les candidatures de cette offre
        if (!$this->isGranted('ROLE_ADMIN') && 
            (!$this->isGranted('ROLE_PILOT') || $this->getUser()->getCompany()->getId() !== $offer->getCompany()->getId())) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les candidatures de cette offre.');
        }
        
        return $this->render('offre/applications.html.twig', [
            'offer' => $offer,
            'applications' => $offer->getApplications()
        ]);
    }
}