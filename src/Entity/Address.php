<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $country = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    // Collection d'utilisateurs associés à cette adresse
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'addresses')]
    private Collection $users;

    // Collection d'entreprises associées à cette adresse
    #[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'address')]
    private Collection $companies;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->companies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    // Retourne tous les utilisateurs associés à cette adresse
    public function getUsers(): Collection
    {
        return $this->users;
    }

    // Ajoute un utilisateur à la collection et met à jour la relation bidirectionnelle
    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addAddress($this);
        }

        return $this;
    }

    // Supprime un utilisateur de la collection et met à jour la relation bidirectionnelle
    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeAddress($this);
        }

        return $this;
    }

    // Retourne toutes les entreprises associées à cette adresse
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    // Ajoute une entreprise à la collection et définit cette adresse comme propriétaire
    public function addCompany(Company $company): static
    {
        if (!$this->companies->contains($company)) {
            $this->companies->add($company);
            $company->setAddress($this);
        }
        return $this;
    }

    // Supprime une entreprise de la collection
    public function removeCompany(Company $company): static
    {
        if ($this->companies->removeElement($company)) {
            // Réinitialise l'adresse associée si nécessaire
            if ($company->getAddress() === $this) {
                $company->setAddress(null);
            }
        }
        return $this;
    }
}
