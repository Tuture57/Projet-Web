<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use App\Trait\ImageUploadTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    use ImageUploadTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column]
    private int $numberOfInterns = 0;

    #[ORM\Column(nullable: true)]
    private ?float $averageRating = null;

    #[ORM\ManyToOne(targetEntity: Address::class, inversedBy: 'companies', cascade: ["persist"])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    // Collection d'offres liées à cette entreprise
    #[ORM\OneToMany(targetEntity: offer::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    private Collection $offers;

    // Collection d'évaluations liées à cette entreprise
    #[ORM\OneToMany(targetEntity: rating::class, mappedBy: 'company')]
    private Collection $ratings;

    public function __construct()
    {
        $this->offers = new ArrayCollection();
        $this->ratings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getNumberOfInterns(): ?int
    {
        return $this->numberOfInterns;
    }

    public function setNumberOfInterns(int $numberOfInterns): static
    {
        $this->numberOfInterns = $numberOfInterns;

        return $this;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;
        return $this;
    }

    // Retourne toutes les offres associées à l'entreprise
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    // Ajoute une offre à la collection et définit cette entreprise comme propriétaire
    public function addOffer(offer $offer): static
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setCompany($this);
        }

        return $this;
    }

    // Supprime une offre de la collection
    public function removeOffer(offer $offer): static
    {
        if ($this->offers->removeElement($offer)) {
            // Réinitialise l'entreprise associée si nécessaire
            if ($offer->getCompany() === $this) {
                $offer->setCompany(null);
            }
        }

        return $this;
    }

    // Retourne toutes les évaluations associées à l'entreprise
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    // Ajoute une évaluation à la collection
    public function addRating(rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setCompany($this);
        }

        return $this;
    }

    // Supprime une évaluation de la collection
    public function removeRating(rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // Réinitialise l'entreprise associée si nécessaire
            if ($rating->getCompany() === $this) {
                $rating->setCompany(null);
            }
        }

        return $this;
    }

    // Gestion du logo via le trait ImageUploadTrait
    public function getLogoName(): ?string
    {
        return $this->getImageName();
    }

    public function setLogoName(?string $logoName): self
    {
        return $this->setImageName($logoName);
    }

    public function getLogoFile(): ?File
    {
        return $this->getImageFile();
    }

    public function setLogoFile(?File $logoFile): self
    {
        return $this->setImageFile($logoFile);
    }
}
