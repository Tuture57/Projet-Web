<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Trait\ImageUploadTrait;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface 
{
    use ImageUploadTrait;
    
    // Identifiant unique de l'utilisateur
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Adresse email de l'utilisateur
    #[ORM\Column(length: 255)]
    private ?string $email = null;

    // Mot de passe encodé
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    // Prénom de l'utilisateur
    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    // Nom de famille de l'utilisateur
    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    // Établissement scolaire de l'utilisateur
    #[ORM\Column(length: 255)]
    private ?string $school = null;

    // Statut de recherche d'emploi
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $searchStatus = null;

    // Rôle de l'utilisateur (étudiant, admin, etc.)
    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    // Adresses associées à l'utilisateur
    #[ORM\ManyToMany(targetEntity: Address::class, inversedBy: 'users')]
    private Collection $addresses;

    // Offres ajoutées à la liste de souhaits
    #[ORM\ManyToMany(targetEntity: Offer::class, inversedBy: 'wishListUsers')]
    private Collection $wishListOffers;

    // Candidatures soumises par l'utilisateur
    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'user')]
    private Collection $applications;

    // Évaluations faites par l'utilisateur
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'user')]
    private Collection $ratings;

    // Initialisation des collections
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->wishListOffers = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    // Récupère l'identifiant unique de l'utilisateur
    public function getId(): ?int
    {
        return $this->id;
    }

    // Récupère l'adresse email de l'utilisateur
    public function getEmail(): ?string
    {
        return $this->email;
    }

    // Définit l'adresse email de l'utilisateur
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    // Récupère le mot de passe encodé
    public function getPassword(): ?string
    {
        return $this->password;
    }

    // Définit le mot de passe encodé
    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    // Récupère le prénom de l'utilisateur
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    // Définit le prénom de l'utilisateur
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    // Récupère le nom de famille de l'utilisateur
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    // Définit le nom de famille de l'utilisateur
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    // Récupère l'établissement scolaire de l'utilisateur
    public function getSchool(): ?string
    {
        return $this->school;
    }

    // Définit l'établissement scolaire de l'utilisateur
    public function setSchool(string $school): static
    {
        $this->school = $school;

        return $this;
    }

    // Récupère le statut de recherche d'emploi
    public function getSearchStatus(): ?string
    {
        return $this->searchStatus;
    }

    // Définit le statut de recherche d'emploi
    public function setSearchStatus(?string $searchStatus): static
    {
        $this->searchStatus = $searchStatus;

        return $this;
    }

    // Récupère le rôle de l'utilisateur
    public function getRole(): ?Role
    {
        return $this->role;
    }

    // Définit le rôle de l'utilisateur
    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    // Récupère les adresses associées à l'utilisateur
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    // Ajoute une adresse à l'utilisateur
    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
        }

        return $this;
    }

    // Supprime une adresse de l'utilisateur
    public function removeAddress(Address $address): static
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    // Récupère les offres de la liste de souhaits
    public function getWishListOffers(): Collection
    {
        // Initialisation de la collection si elle est nulle
        if ($this->wishListOffers === null) {
            $this->wishListOffers = new ArrayCollection();
        }
        return $this->wishListOffers;
    }

    // Ajoute une offre à la liste de souhaits
    public function addWishListOffer(Offer $wishListOffer): static
    {
        if (!$this->getWishListOffers()->contains($wishListOffer)) {
            $this->wishListOffers->add($wishListOffer);
            $wishListOffer->addWishListUser($this); // Assure que les deux côtés de la relation sont mis à jour
        }

        return $this;
    }

    // Supprime une offre de la liste de souhaits
    public function removeWishListOffer(Offer $wishListOffer): static
    {
        if ($this->wishListOffers->removeElement($wishListOffer)) {
            $wishListOffer->removeWishListUser($this); // Assure que les deux côtés de la relation sont mis à jour
        }

        return $this;
    }

    // Récupère les candidatures soumises par l'utilisateur
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    // Ajoute une candidature à l'utilisateur
    public function addApplication(Application $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setUser($this);
        }

        return $this;
    }

    // Supprime une candidature de l'utilisateur
    public function removeApplication(Application $application): static
    {
        if ($this->applications->removeElement($application)) {
            // Définit le côté propriétaire à null (sauf si déjà changé)
            if ($application->getUser() === $this) {
                $application->setUser(null);
            }
        }

        return $this;
    }

    // Récupère les évaluations faites par l'utilisateur
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    // Ajoute une évaluation à l'utilisateur
    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setUser($this);
        }

        return $this;
    }

    // Supprime une évaluation de l'utilisateur
    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // Définit le côté propriétaire à null (sauf si déjà changé)
            if ($rating->getUser() === $this) {
                $rating->setUser(null);
            }
        }

        return $this;
    }

    // Récupère les rôles de l'utilisateur pour la sécurité Symfony
    public function getRoles(): array
    {
        $roleName = $this->role->getName();
        return ['ROLE_' . strtoupper($roleName)];
    }
    
    // Retourne l'identifiant unique pour l'authentification
    public function getUserIdentifier(): string
    {
        return $this->email;
    }
    
    // Méthode requise par l'interface UserInterface
    public function eraseCredentials(): void
    {
        // Rien à faire ici
    }
}
