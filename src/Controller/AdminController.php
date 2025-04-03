<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Role;
use App\Form\AdminType;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/administrateurs')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
        PaginatorInterface $paginator
    ): Response {
        // Récupérer le rôle admin
        $adminRole = $roleRepository->findOneBy(['name' => 'Admin']);
        
        // Créer le queryBuilder de base
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', $adminRole)
            ->orderBy('u.lastName', 'ASC');
        
        // Récupérer les paramètres de recherche
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'lastName_asc');
        
        // Appliquer le filtre de recherche textuelle
        if (!empty($search)) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        // Appliquer le tri
        switch ($sortBy) {
            case 'firstName_asc':
                $queryBuilder->orderBy('u.firstName', 'ASC');
                break;
            case 'firstName_desc':
                $queryBuilder->orderBy('u.firstName', 'DESC');
                break;
            case 'lastName_desc':
                $queryBuilder->orderBy('u.lastName', 'DESC');
                break;
            case 'lastName_asc':
            default:
                $queryBuilder->orderBy('u.lastName', 'ASC');
        }
        
        // Pagination
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10 // Nombre d'administrateurs par page
        );
        
        return $this->render('admin/index.html.twig', [
            'admins' => $pagination,
            'search' => $search,
            'sortBy' => $sortBy
        ]);
    }
    
    #[Route('/new', name: 'admin_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        RoleRepository $roleRepository,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        $admin = new User();
        
        // Assigner le rôle admin
        $adminRole = $roleRepository->findOneBy(['name' => 'Admin']);
        $admin->setRole($adminRole);
        
        // Valeur par défaut pour l'école (obligatoire dans l'entité)
        $admin->setSchool('Administration');
        
        $form = $this->createForm(AdminType::class, $admin);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $admin,
                    $plainPassword
                );
                $admin->setPassword($hashedPassword);
            }
            
            // Gestion de l'upload de l'image
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sécuriser le nom du fichier
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                // Déplacer le fichier dans le répertoire où sont stockées les images
                try {
                    $imageFile->move(
                        $this->getParameter('admin_photos_directory'),
                        $newFilename
                    );
                    $admin->setImageName($newFilename);
                } catch (FileException $e) {
                    // Gérer l'exception
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                }
            }
            
            $entityManager->persist($admin);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'administrateur a été créé avec succès.');
            
            return $this->redirectToRoute('admin_show', [
                'id' => $admin->getId(),
            ]);
        }
        
        return $this->render('admin/new.html.twig', [
            'admin' => $admin,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}', name: 'admin_show', methods: ['GET'])]
    public function show(User $admin): Response
    {
        // Vérifier que l'utilisateur est bien un admin
        if (!$admin->getRole() || $admin->getRole()->getName() !== 'Admin') {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un administrateur.');
        }
        
        return $this->render('admin/show.html.twig', [
            'admin' => $admin,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'admin_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $admin,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        // Vérifier que l'utilisateur est bien un admin
        if (!$admin->getRole() || $admin->getRole()->getName() !== 'Admin') {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un administrateur.');
        }
        
        $form = $this->createForm(AdminType::class, $admin, [
            'require_password' => false, // Mot de passe non requis pour la modification
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe si un nouveau mot de passe est fourni
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword(
                    $admin,
                    $plainPassword
                );
                $admin->setPassword($hashedPassword);
            }
            
            // Gestion de l'upload de l'image
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sécuriser le nom du fichier
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                // Déplacer le fichier dans le répertoire où sont stockées les images
                try {
                    $imageFile->move(
                        $this->getParameter('admin_photos_directory'),
                        $newFilename
                    );
                    
                    // Supprimer l'ancienne image si elle existe
                    $oldFilename = $admin->getImageName();
                    if ($oldFilename) {
                        $oldFilePath = $this->getParameter('admin_photos_directory').'/'.$oldFilename;
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    
                    $admin->setImageName($newFilename);
                } catch (FileException $e) {
                    // Gérer l'exception
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de l\'image.');
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'administrateur a été modifié avec succès.');
            
            return $this->redirectToRoute('admin_show', [
                'id' => $admin->getId(),
            ]);
        }
        
        return $this->render('admin/edit.html.twig', [
            'admin' => $admin,
            'form' => $form->createView(),
        ]);
    }
    
    #[Route('/{id}/delete', name: 'admin_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $admin,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier que l'utilisateur est bien un admin
        if (!$admin->getRole() || $admin->getRole()->getName() !== 'Admin') {
            throw $this->createNotFoundException('Cet utilisateur n\'est pas un administrateur.');
        }
        
        // Vérifier le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$admin->getId(), $request->request->get('_token'))) {
            $entityManager->remove($admin);
            $entityManager->flush();
            
            $this->addFlash('success', 'L\'administrateur a été supprimé avec succès.');
        }
        
        return $this->redirectToRoute('admin');
    }
}
