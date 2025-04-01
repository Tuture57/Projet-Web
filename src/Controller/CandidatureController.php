<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Offer;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        
        $entityManager->persist($application);
        $entityManager->flush();
        
        $this->addFlash('success', 'Votre candidature a été envoyée avec succès.');
        
        return $this->redirectToRoute('candidature_index');
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
        
        // Vérifier que la candidature appartient bien à l'utilisateur
        if ($application->getUser() !== $user && $this->isGranted('ROLE_STUDENT')) {
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
