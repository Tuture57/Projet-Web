<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $school = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $searchStatus = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?role $role = null;

    /**
     * @var Collection<int, address>
     */
    #[ORM\ManyToMany(targetEntity: address::class, inversedBy: 'users')]
    private Collection $addresses;

    /**
     * @var Collection<int, offer>
     */
    #[ORM\ManyToMany(targetEntity: offer::class, inversedBy: 'wishListUsers')]
    private Collection $wishListOffers;

    /**
     * @var Collection<int, application>
     */
    #[ORM\OneToMany(targetEntity: application::class, mappedBy: 'user')]
    private Collection $applications;

    /**
     * @var Collection<int, rating>
     */
    #[ORM\OneToMany(targetEntity: rating::class, mappedBy: 'user')]
    private Collection $ratings;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->wishListOffers = new ArrayCollection();
        $this->applications = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getSchool(): ?string
    {
        return $this->school;
    }

    public function setSchool(string $school): static
    {
        $this->school = $school;

        return $this;
    }

    public function getSearchStatus(): ?string
    {
        return $this->searchStatus;
    }

    public function setSearchStatus(?string $searchStatus): static
    {
        $this->searchStatus = $searchStatus;

        return $this;
    }

    public function getRole(): ?role
    {
        return $this->role;
    }

    public function setRole(?role $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection<int, address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
        }

        return $this;
    }

    public function removeAddress(address $address): static
    {
        $this->addresses->removeElement($address);

        return $this;
    }

    /**
     * @return Collection<int, offer>
     */
    public function getWishListOffers(): Collection
    {
        return $this->wishListOffers;
    }

    public function addWishListOffer(offer $wishListOffer): static
    {
        if (!$this->wishListOffers->contains($wishListOffer)) {
            $this->wishListOffers->add($wishListOffer);
        }

        return $this;
    }

    public function removeWishListOffer(offer $wishListOffer): static
    {
        $this->wishListOffers->removeElement($wishListOffer);

        return $this;
    }

    /**
     * @return Collection<int, application>
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    public function addApplication(application $application): static
    {
        if (!$this->applications->contains($application)) {
            $this->applications->add($application);
            $application->setUser($this);
        }

        return $this;
    }

    public function removeApplication(application $application): static
    {
        if ($this->applications->removeElement($application)) {
            // set the owning side to null (unless already changed)
            if ($application->getUser() === $this) {
                $application->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setUser($this);
        }

        return $this;
    }

    public function removeRating(rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getUser() === $this) {
                $rating->setUser(null);
            }
        }

        return $this;
    }
}
