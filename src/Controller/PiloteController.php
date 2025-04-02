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
        // Vérifier que l'utilisateur est administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupérer le rôle "pilot"
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        // Requête de base pour tous les pilotes
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', $pilotRole);
        
        // Récupérer les filtres de recherche
        $search = $request->query->get('search', '');
        
        // Appliquer le filtre de recherche si présent
        if (!empty($search)) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR u.school LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Appliquer le filtre par école si présent
        $selectedSchool = $request->query->get('school');
        if (!empty($selectedSchool)) {
            $queryBuilder->andWhere('u.school = :selectedSchool')
                ->setParameter('selectedSchool', $selectedSchool);
        }
        
        // Appliquer le tri si présent
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
        
        // Obtenir les écoles uniques pour les filtres
        $schoolsData = $userRepository->createQueryBuilder('u')
            ->select('DISTINCT u.school')
            ->where('u.role = :role')
            ->setParameter('role', $pilotRole)
            ->orderBy('u.school', 'ASC')
            ->getQuery()
            ->getResult();

        // Transformation en tableau avec les clés value et label
        $schools = [];
        foreach ($schoolsData as $data) {
            $schools[] = [
                'value' => $data['school'],
                'label' => $data['school']
            ];
        }
        
        // Paginer les résultats
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
        // Vérifier que l'utilisateur est administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Créer un nouvel utilisateur
        $user = new User();
        
        // Récupérer le rôle "pilot"
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        if (!$pilotRole) {
            throw $this->createNotFoundException('Le rôle "pilot" n\'existe pas.');
        }
        
        // Définir le rôle par défaut
        $user->setRole($pilotRole);
        
        // Créer le formulaire
        $form = $this->createForm(PiloteType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            
            // Gérer l'upload d'image
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
            
            // Persister et flush
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
        // Vérifier que l'utilisateur a bien le rôle pilote
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
        // Vérifier que l'utilisateur est administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier que l'utilisateur a bien le rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        if ($user->getRole() !== $pilotRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un pilote');
        }
        
        // Créer le formulaire
        $form = $this->createForm(PiloteType::class, $user, [
            'require_password' => false
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe si fourni
            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );
                $user->setPassword($hashedPassword);
            }
            
            // Gérer l'upload d'image
            if ($form->has('imageFile') && $form->get('imageFile')->getData()) {
                $imageFile = $form->get('imageFile')->getData();
                
                try {
                    // Supprimer l'ancienne image s'il y en a une
                    if ($user->getImageName()) {
                        $imageService->removeImage(
                            $user->getImageName(),
                            $this->getParameter('pilot_photos_directory')
                        );
                    }
                    
                    // Uploader la nouvelle image
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
        // Vérifier que l'utilisateur est administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier que l'utilisateur a bien le rôle pilote
        $pilotRole = $roleRepository->findOneBy(['name' => 'pilot']);
        
        if ($user->getRole() !== $pilotRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un pilote');
        }
        
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                // Supprimer l'image si elle existe
                if ($user->getImageName()) {
                    $imageService->removeImage(
                        $user->getImageName(),
                        $this->getParameter('pilot_photos_directory')
                    );
                }
                
                // Supprimer les adresses
                if (method_exists($user, 'getAddresses')) {
                    $addresses = $user->getAddresses()->toArray();
                    foreach ($addresses as $address) {
                        $user->removeAddress($address);
                        if (method_exists($address, 'getUsers') && $address->getUsers()->count() === 0) {
                            $entityManager->remove($address);
                        }
                    }
                }
                
                // Supprimer les ratings associés
                foreach ($user->getRatings() as $rating) {
                    $entityManager->remove($rating);
                }
                
                // Effectuer un flush intermédiaire
                $entityManager->flush();
                
                // Supprimer l'utilisateur
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
