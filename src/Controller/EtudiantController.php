<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Entity\User;
use App\Form\StudentType;
use App\Service\ImageUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Repository\ApplicationRepository;
use App\Repository\RatingRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EtudiantController extends AbstractController
{
    #[Route('/etudiants', name: 'etudiant')]
    #[IsGranted('ROLE_PILOT')]
    public function index(
        Request $request, 
        UserRepository $userRepository, 
        RoleRepository $roleRepository,
        PaginatorInterface $paginator
    ): Response
    {
        // Recherche du rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if (!$studentRole) {
            throw $this->createNotFoundException('Le rôle étudiant n\'existe pas');
        }
        
        // Récupération des paramètres de filtrage
        $searchTerm = $request->query->get('search', '');
        $school = $request->query->get('school');
        $status = $request->query->get('status');
        $sortBy = $request->query->get('sortBy', 'lastName_asc');
        
        // Construction de la requête pour les étudiants
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->where('u.role = :role')
            ->setParameter('role', $studentRole);
        
        // Ajout de la recherche textuelle
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        // Application des filtres
        if (!empty($school)) {
            $queryBuilder->andWhere('u.school = :school')
                ->setParameter('school', $school);
        }
        
        if (!empty($status)) {
            $queryBuilder->andWhere('u.searchStatus = :status')
                ->setParameter('status', $status);
        }
        
        // Application du tri
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
            case 'lastName_asc':
            default:
                $queryBuilder->orderBy('u.lastName', 'ASC');
        }
        
        // Récupération des écoles distinctes pour le filtre
        $schoolsQuery = $userRepository->createQueryBuilder('u')
            ->select('DISTINCT u.school')
            ->where('u.role = :role')
            ->setParameter('role', $studentRole)
            ->orderBy('u.school', 'ASC')
            ->getQuery();
        
        $schools = array_map(function($result) {
            return ['value' => $result['school'], 'label' => $result['school']];
        }, $schoolsQuery->getArrayResult());
        
        // Récupération des statuts de recherche pour le filtre
        $statusQuery = $userRepository->createQueryBuilder('u')
            ->select('DISTINCT u.searchStatus')
            ->where('u.role = :role')
            ->andWhere('u.searchStatus IS NOT NULL')
            ->setParameter('role', $studentRole)
            ->orderBy('u.searchStatus', 'ASC')
            ->getQuery();
        
        $statuses = array_map(function($result) {
            return ['value' => $result['searchStatus'], 'label' => $result['searchStatus']];
        }, $statusQuery->getArrayResult());
        
        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            5 // Nombre d'étudiants par page
        );
        
        return $this->render('etudiant/index.html.twig', [
            'students' => $pagination,
            'schools' => $schools,
            'statuses' => $statuses
        ]);
    }

    #[Route('/etudiants/new', name: 'etudiant_new')]
    #[IsGranted('ROLE_PILOT')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository,
        ImageUploadService $imageService
    ): Response
    {
        // Création d'un nouvel utilisateur
        $user = new User();
        
        // Récupération du rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        if (!$studentRole) {
            throw $this->createNotFoundException('Le rôle "student" n\'existe pas.');
        }
        
        // Attribution du rôle
        $user->setRole($studentRole);
        
        // Création du formulaire
        $form = $this->createForm(StudentType::class, $user);
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
                        'student',
                        $this->getParameter('student_photos_directory')
                    );
                    
                    $user->setImageName($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image: ' . $e->getMessage());
                }
            }
            
            // Persistance des données
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'étudiant a été créé avec succès.');
            
            return $this->redirectToRoute('etudiant');
        }
        
        return $this->render('etudiant/new.html.twig', [
            'student' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/etudiants/{id}', name: 'etudiant_show')]
    #[IsGranted('ROLE_PILOT')]
    public function show(
        User $user, 
        RoleRepository $roleRepository,
        ApplicationRepository $applicationRepository
    ): Response
    {
        // Vérification du rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if ($user->getRole() !== $studentRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un étudiant');
        }
        
        // Récupération des candidatures de l'étudiant
        $applications = $user->getApplications();
        
        return $this->render('etudiant/show.html.twig', [
            'student' => $user,
            'applications' => $applications
        ]);
    }

    #[Route('/etudiants/{id}/edit', name: 'etudiant_edit')]
    #[IsGranted('ROLE_PILOT')]
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
        
        // Vérification du rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if ($user->getRole() !== $studentRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un étudiant');
        }
        
        $form = $this->createForm(StudentType::class, $user, ['is_edit' => true]);
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
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                try {
                    // Suppression de l'ancienne image
                    if ($user->getImageName()) {
                        $imageService->removeImage(
                            $user->getImageName(),
                            $this->getParameter('student_photos_directory')
                        );
                    }
                    
                    // Upload de la nouvelle image
                    $newFilename = $imageService->uploadImage(
                        $imageFile,
                        'student',
                        $this->getParameter('student_photos_directory')
                    );
                    
                    $user->setImageName($newFilename);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'étudiant a été modifié avec succès.');
            return $this->redirectToRoute('etudiant_show', ['id' => $user->getId()]);
        }
        
        return $this->render('etudiant/edit.html.twig', [
            'form' => $form->createView(),
            'student' => $user
        ]);
    }

    #[Route('/etudiants/{id}/delete', name: 'etudiant_delete', methods: ['POST'])]
    #[IsGranted('ROLE_PILOT')]
    public function delete(
        Request $request,
        User $user,
        RoleRepository $roleRepository,
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager,
        ImageUploadService $imageService,
        ApplicationRepository $applicationRepository,
        RatingRepository $ratingRepository
    ): Response
    {
        // Vérification des droits d'accès
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérification du rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if ($user->getRole() !== $studentRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un étudiant');
        }
        
        // Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                // Suppression des candidatures
                $applications = $applicationRepository->findBy(['user' => $user]);
                foreach ($applications as $application) {
                    $entityManager->remove($application);
                }
                
                // Suppression des évaluations
                $ratings = $ratingRepository->findBy(['user' => $user]);
                foreach ($ratings as $rating) {
                    $entityManager->remove($rating);
                }
                
                // Suppression de l'image
                if ($user->getImageName()) {
                    $imageService->removeImage(
                        $user->getImageName(),
                        $this->getParameter('student_photos_directory')
                    );
                    
                    $user->setImageName(null);
                }
                
                // Suppression des entrées wishlist
                foreach ($user->getWishListOffers() as $offer) {
                    $user->removeWishListOffer($offer);
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
                
                // Flush intermédiaire
                $entityManager->flush();
                
                // Suppression de l'utilisateur
                $entityManager->remove($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'L\'étudiant a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
                $this->addFlash('error', 'Type d\'erreur: ' . get_class($e));
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('etudiant');
    }
}
