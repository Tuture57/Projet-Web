<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    // Collection d'offres associées à cette compétence
    #[ORM\ManyToMany(targetEntity: Offer::class, mappedBy: 'skills')]
    private Collection $offers;

    public function __construct()
    {
        $this->offers = new ArrayCollection();
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

    // Retourne toutes les offres associées à cette compétence
    public function getOffers(): Collection
    {
        return $this->offers;
    }

    // Ajoute une offre à la collection et met à jour la relation bidirectionnelle
    public function addOffer(Offer $offer): static
    {
        if (!$this->offers->contains($offer)) {
            $this->offers->add($offer);
            $offer->addSkill($this);
        }

        return $this;
    }

    // Supprime une offre de la collection et met à jour la relation bidirectionnelle
    public function removeOffer(Offer $offer): static
    {
        if ($this->offers->removeElement($offer)) {
            $offer->removeSkill($this);
        }

        return $this;
    }
}
