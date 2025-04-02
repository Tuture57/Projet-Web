<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Offer;
use App\Entity\User;
use App\Form\ApplicationType;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/candidatures')]
class CandidatureController extends AbstractController
{
    #[Route('/', name: 'candidature_index')]
    public function index(Request $request, ApplicationRepository $applicationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Récupérer toutes les candidatures de l'étudiant
        $applications = $applicationRepository->findBy(
            ['user' => $user],
            ['applicationDate' => 'DESC']
        );
        
        return $this->render('candidature/index.html.twig', [
            'applications' => $applications,
        ]);
    }
    
    #[Route('/postuler/{id}', name: 'candidature_postuler')]
    public function postuler(
        Offer $offer,
        Request $request,
        EntityManagerInterface $entityManager,
        ApplicationRepository $applicationRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier si l'étudiant a déjà postulé à cette offre
        $existingApplication = $applicationRepository->findOneBy([
            'user' => $user,
            'offer' => $offer
        ]);
        
        if ($existingApplication) {
            $this->addFlash('error', 'Vous avez déjà postulé à cette offre.');
            return $this->redirectToRoute('offre_show', ['id' => $offer->getId()]);
        }
        
        // Créer une nouvelle candidature
        $application = new Application();
        $application->setUser($user);
        $application->setOffer($offer);
        $application->setStatus('Pending');
        $application->setApplicationDate(new \DateTime());
        
        // Créer le formulaire
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer l'upload du CV
            $cvFile = $form->get('cvFile')->getData();
            
            if ($cvFile) {
                $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                // Sécuriser le nom du fichier
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$cvFile->guessExtension();
                
                // Déplacer le fichier dans le répertoire où sont stockés les CVs
                try {
                    $cvFile->move(
                        $this->getParameter('cv_directory'),
                        $newFilename
                    );
                    $application->setCv($newFilename);
                } catch (FileException $e) {
                    // Gérer l'exception
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du CV.');
                }
            }
            
            $entityManager->persist($application);
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre candidature a été envoyée avec succès.');
            
            return $this->redirectToRoute('candidature_index');
        }
        
        return $this->render('candidature/postuler.html.twig', [
            'offer' => $offer,
            'form' => $form->createView()
        ]);
    }
    
    #[Route('/annuler/{id}', name: 'candidature_annuler')]
    public function annuler(
        Application $application,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que la candidature appartient bien à l'utilisateur
        if ($application->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette candidature.');
        }
        
        // Vérifier que la candidature est encore en attente
        if ($application->getStatus() !== 'Pending') {
            $this->addFlash('error', 'Vous ne pouvez pas annuler une candidature déjà traitée.');
            return $this->redirectToRoute('candidature_index');
        }
        
        $entityManager->remove($application);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre candidature a été annulée avec succès.');
        
        return $this->redirectToRoute('candidature_index');
    }
    
    #[Route('/details/{id}', name: 'candidature_details')]
    public function details(Application $application): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $isPilot = $this->isGranted('ROLE_PILOT');
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        // Only allow the student who made the application or pilots/admins to view
        if (!$isAdmin && !$isPilot && $user !== $application->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette candidature.');
        }
        
        return $this->render('candidature/details.html.twig', [
            'application' => $application,
        ]);
    }
    
    #[Route('/accepter/{id}', name: 'candidature_accepter')]
    public function accepter(
        Application $application,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'offre associée appartient bien à l'entreprise
        if ($application->getOffer()->getCompany() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer cette candidature.');
        }
        
        $application->setStatus('Accepted');
        $entityManager->flush();
        
        $this->addFlash('success', 'La candidature a été acceptée.');
        
        return $this->redirectToRoute('entreprise_candidatures');
    }
    
    #[Route('/refuser/{id}', name: 'candidature_refuser')]
    public function refuser(
        Application $application,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        
        // Vérifier que l'offre associée appartient bien à l'entreprise
        if ($application->getOffer()->getCompany() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer cette candidature.');
        }
        
        $application->setStatus('Rejected');
        $entityManager->flush();
        
        $this->addFlash('success', 'La candidature a été refusée.');
        
        return $this->redirectToRoute('entreprise_candidatures');
    }
}
