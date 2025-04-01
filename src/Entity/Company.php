<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
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

    /**
     * @var Collection<int, offer>
     */
    #[ORM\OneToMany(targetEntity: offer::class, mappedBy: 'company', cascade: ['persist', 'remove'])]
    private Collection $offers;

    /**
     * @var Collection<int, rating>
     */
    #[ORM\OneToMany(targetEntity: rating::class, mappedBy: 'company')]
    private Collection $ratings;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $logoName = null;

    /**
     * @var File|null
     * @Assert\Image(
     *     maxSize = "2M",
     *     mimeTypes = {"image/jpeg", "image/png"},
     *     mimeTypesMessage = "Veuillez télécharger une image valide (JPG ou PNG)"
     * )
     */
    private ?File $logoFile = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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

    /**
     * @return Collection<int, offer>
     */
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    public function addOffer(offer $offer): static
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->setCompany($this);
        }

        return $this;
    }

    public function removeOffer(offer $offer): static
    {
        if ($this->offers->removeElement($offer)) {
            // set the owning side to null (unless already changed)
            if ($offer->getCompany() === $this) {
                $offer->setCompany(null);
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
            $rating->setCompany($this);
        }

        return $this;
    }

    public function removeRating(rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getCompany() === $this) {
                $rating->setCompany(null);
            }
        }

        return $this;
    }

    public function getLogoName(): ?string
    {
        return $this->logoName;
    }

    public function setLogoName(?string $logoName): self
    {
        $this->logoName = $logoName;
        return $this;
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogoFile(?File $logoFile): self
    {
        $this->logoFile = $logoFile;
        
        if ($logoFile) {
            // Pour forcer Doctrine à détecter le changement
            $this->updatedAt = new \DateTime();
        }
        
        return $this;
    }
}
