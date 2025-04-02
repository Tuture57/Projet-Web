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
        // Trouver l'ID du rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if (!$studentRole) {
            throw $this->createNotFoundException('Le rôle étudiant n\'existe pas');
        }
        
        // Récupérer les paramètres de recherche et de filtrage
        $searchTerm = $request->query->get('search', '');
        $school = $request->query->get('school');
        $status = $request->query->get('status');
        $sortBy = $request->query->get('sortBy', 'lastName_asc');
        
        // Construire la requête pour les utilisateurs ayant le rôle étudiant
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->where('u.role = :role')
            ->setParameter('role', $studentRole);
        
        // Ajouter la recherche
        if (!empty($searchTerm)) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }
        
        // Ajouter les filtres
        if (!empty($school)) {
            $queryBuilder->andWhere('u.school = :school')
                ->setParameter('school', $school);
        }
        
        if (!empty($status)) {
            $queryBuilder->andWhere('u.searchStatus = :status')
                ->setParameter('status', $status);
        }
        
        // Appliquer le tri
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
        
        // Récupérer les écoles distinctes pour les filtres
        $schoolsQuery = $userRepository->createQueryBuilder('u')
            ->select('DISTINCT u.school')
            ->where('u.role = :role')
            ->setParameter('role', $studentRole)
            ->orderBy('u.school', 'ASC')
            ->getQuery();
        
        $schools = array_map(function($result) {
            return ['value' => $result['school'], 'label' => $result['school']];
        }, $schoolsQuery->getArrayResult());
        
        // Récupérer les statuts de recherche distincts pour les filtres
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
        
        // Paginer les résultats
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
        // Créer un nouvel utilisateur
        $user = new User();
        
        // Récupérer le rôle "student"
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        if (!$studentRole) {
            throw $this->createNotFoundException('Le rôle "student" n\'existe pas.');
        }
        
        // Définir le rôle par défaut
        $user->setRole($studentRole);
        
        // Créer le formulaire
        $form = $this->createForm(StudentType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            
            // Gérer l'upload d'image exactement comme dans la méthode edit()
            if ($form->has('imageFile') && $form->get('imageFile')->getData()) {
                $imageFile = $form->get('imageFile')->getData();
                
                try {
                    // Utiliser exactement les mêmes paramètres que dans edit()
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
            
            // Persister et flush
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
        // Vérifier que l'utilisateur a bien le rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if ($user->getRole() !== $studentRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un étudiant');
        }
        
        // Récupérer les candidatures de l'étudiant
        // Soit directement depuis l'utilisateur:
        $applications = $user->getApplications();
        
        // Ou alternative avec le repository pour plus de contrôle:
        // $applications = $applicationRepository->findBy(['user' => $user], ['applicationDate' => 'DESC']);
        
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
        // Vérifier que l'utilisateur est administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier que l'utilisateur a bien le rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if ($user->getRole() !== $studentRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un étudiant');
        }
        
        $form = $this->createForm(StudentType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hasher le nouveau mot de passe si fourni
            if ($form->has('plainPassword') && $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );
                $user->setPassword($hashedPassword);
            }
            
            // Gérer l'upload d'image si disponible
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                try {
                    // Supprimer l'ancienne image s'il y en a une
                    if ($user->getImageName()) {
                        $imageService->removeImage(
                            $user->getImageName(),
                            $this->getParameter('student_photos_directory')
                        );
                    }
                    
                    // Uploader la nouvelle image
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
        // Vérifier que l'utilisateur est administrateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Vérifier que l'utilisateur a bien le rôle étudiant
        $studentRole = $roleRepository->findOneBy(['name' => 'student']);
        
        if ($user->getRole() !== $studentRole) {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un étudiant');
        }
        
        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            try {
                // 1. Suppression des candidatures
                $applications = $applicationRepository->findBy(['user' => $user]);
                foreach ($applications as $application) {
                    $entityManager->remove($application);
                }
                
                // 2. Suppression des ratings
                $ratings = $ratingRepository->findBy(['user' => $user]);
                foreach ($ratings as $rating) {
                    $entityManager->remove($rating);
                }
                
                // 3. Suppression de l'image
                if ($user->getImageName()) {
                    // Supprimer le fichier physique
                    $imageService->removeImage(
                        $user->getImageName(),
                        $this->getParameter('student_photos_directory')
                    );
                    
                    // Supprimer la référence dans l'entité
                    $user->setImageName(null);
                }
                
                // 4. Suppression des entrées wishlist
                foreach ($user->getWishListOffers() as $offer) {
                    $user->removeWishListOffer($offer);
                }
                
                // 5. Suppression des adresses
                if (method_exists($user, 'getAddresses')) {
                    $addresses = $user->getAddresses()->toArray();
                    foreach ($addresses as $address) {
                        $user->removeAddress($address);
                        if (method_exists($address, 'getUsers') && $address->getUsers()->count() === 0) {
                            $entityManager->remove($address);
                        }
                    }
                }
                
                // Effectuer un flush intermédiaire pour enregistrer les suppressions de relations
                $entityManager->flush();
                
                // 6. Suppression de l'utilisateur
                $entityManager->remove($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'L\'étudiant a été supprimé avec succès.');
            } catch (\Exception $e) {
                // Capturer l'erreur complète pour le diagnostic
                $this->addFlash('error', 'Erreur: ' . $e->getMessage());
                $this->addFlash('error', 'Type d\'erreur: ' . get_class($e));
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        
        return $this->redirectToRoute('etudiant');
    }
}
