<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PiloteType;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PiloteController extends AbstractController
{
    #[Route('/pilotes', name: 'pilote')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        PaginatorInterface $paginator
    ): Response
    {
        // Vérification des droits d'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupération du rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        // Création du queryBuilder pour les pilotes
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', $pilotRole);
        
        // Récupération des filtres de recherche
        $search = $request->query->get('search', '');
        
        // Application du filtre de recherche
        if (!empty($search)) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR u.school LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Application du filtre par école
        $selectedSchool = $request->query->get('school');
        if (!empty($selectedSchool)) {
            $queryBuilder->andWhere('u.school = :selectedSchool')
                ->setParameter('selectedSchool', $selectedSchool);
        }
        
        // Application du tri
        $sortBy = $request->query->get('sortBy', 'lastName_asc');
        switch ($sortBy) {
            case 'lastName_desc':
                $queryBuilder->orderBy('u.lastName', 'DESC');
                break;
            case 'firstName_asc':
                $queryBuilder->orderBy('u.firstName', 'ASC');
                break;
            case 'firstName_desc':
                $queryBuilder->orderBy('u.firstName', 'DESC');
                break;
            case 'school_asc':
                $queryBuilder->orderBy('u.school', 'ASC');
                break;
            case 'school_desc':
                $queryBuilder->orderBy('u.school', 'DESC');
                break;
            default:
                $queryBuilder->orderBy('u.lastName', 'ASC');
        }
        
        // Récupération des écoles pour les filtres
        $schoolsData = $userRepository->createQueryBuilder('u')
            ->select('DISTINCT u.school')
            ->where('u.role = :role')
            ->setParameter('role', $pilotRole)
            ->orderBy('u.school', 'ASC')
            ->getQuery()
            ->getResult();

        // Transformation en format pour le template
        $schools = [];
        foreach ($schoolsData as $data) {
            $schools[] = [
                'value' => $data['school'],
                'label' => $data['school']
            ];
        }
        
        // Pagination des résultats
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5 // Nombre de pilotes par page
        );
        
        return $this->render('pilote/index.html.twig', [
            'pilots' => $pagination,
            'schools' => $schools
        ]);
    }

    #[Route('/pilotes/new', name: 'pilote_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository,
        ImageUploadService $imageService
    ): Response
    {
        // Vérification des droits d'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Création d'un nouvel utilisateur
        $user = new User();
        
        // Récupération du rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        if (!$pilotRole) {
            throw $this->createNotFoundException('Le rôle "pilot" n\'existe pas.');
        }
        
        // Attribution du rôle
        $user->setRole($pilotRole);
        
        // Création du formulaire
        $form = $this->createForm(PiloteType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            
            // Gestion de l'upload d'image
            if ($form->has('imageFile') && $form->get('imageFile')->getData()) {
                $imageFile = $form->get('imageFile')->getData();
                
                try {
                    $newFilename = $imageService->uploadImage(
                        $imageFile,
                        'pilot',
                        $this->getParameter('pilot_photos_directory')
                    );
                    
                    $user->setImageName($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }
            
            // Persistance des données
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le pilote a été créé avec succès.');
            
            return $this->redirectToRoute('pilote');
        }
        
        return $this->render('pilote/new.html.twig', [
            'pilot' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/pilotes/{id}', name: 'pilote_show')]
    #[IsGranted('ROLE_ADMIN')]
    public function show(
        User $user, 
        RoleRepository $roleRepository
    ): Response
    {
        // Vérification du rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        if ($user->getRole() !== $pilotRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un pilote');
        }
        
        return $this->render('pilote/show.html.twig', [
            'pilot' => $user,
        ]);
    }

    #[Route('/pilotes/{id}/edit', name: 'pilote_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        User $user,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ImageUploadService $imageService
    ): Response
    {
        // Vérification des droits d'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérification du rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        if ($user->getRole() !== $pilotRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un pilote');
        }
        
        // Création du formulaire
        $form = $this->createForm(PiloteType::class, $user, [
            'require_password' => false
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe si fourni
            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );
                $user->setPassword($hashedPassword);
            }
            
            // Gestion de l'upload d'image
            if ($form->has('imageFile') && $form->get('imageFile')->getData()) {
                $imageFile = $form->get('imageFile')->getData();
                
                try {
                    // Suppression de l'ancienne image
                    if ($user->getImageName()) {
                        $imageService->removeImage(
                            $user->getImageName(),
                            $this->getParameter('pilot_photos_directory')
                        );
                    }
                    
                    // Upload de la nouvelle image
                    $newFilename = $imageService->uploadImage(
                        $imageFile,
                        'pilot',
                        $this->getParameter('pilot_photos_directory')
                    );
                    
                    $user->setImageName($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Le pilote a été modifié avec succès.');
            return $this->redirectToRoute('pilote_show', ['id' => $user->getId()]);
        }
        
        return $this->render('pilote/edit.html.twig', [
            'form' => $form->createView(),
            'pilot' => $user
        ]);
    }

    #[Route('/pilotes/{id}/delete', name: 'pilote_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        User $user,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        ImageUploadService $imageService
    ): Response
    {
        // Vérification des droits d'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérification du rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        if ($user->getRole() !== $pilotRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un pilote');
        }
        
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                // Suppression de l'image
                if ($user->getImageName()) {
                    $imageService->removeImage(
                        $user->getImageName(),
                        $this->getParameter('pilot_photos_directory')
                    );
                }
                
                // Suppression des adresses
                if (method_exists($user, 'getAddresses')) {
                    $addresses = $user->getAddresses()->toArray();
                    foreach ($addresses as $address) {
                        $user->removeAddress($address);
                        if (method_exists($address, 'getUsers') && $address->getUsers()->count() === 0) {
                            $entityManager->remove($address);
                        }
                    }
                }
                
                // Suppression des évaluations
                foreach ($user->getRatings() as $rating) {
                    $entityManager->remove($rating);
                }
                
                // Flush intermédiaire
                $entityManager->flush();
                
                // Suppression de l'utilisateur
                $entityManager->remove($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'Le pilote a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
                $this->addFlash('error', 'Type d\'erreur: ' . get_class($e));
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('pilote');
    }
}
