<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    // Identifiant unique du rôle
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Nom du rôle (admin, étudiant, etc.)
    #[ORM\Column(length: 50)]
    private ?string $name = null;

    // Utilisateurs associés à ce rôle
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'role')]
    private Collection $users;

    // Initialisation de la collection d'utilisateurs
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    // Retourne l'identifiant du rôle
    public function getId(): ?int
    {
        return $this->id;
    }

    // Retourne le nom du rôle
    public function getName(): ?string
    {
        return $this->name;
    }

    // Définit le nom du rôle
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    // Retourne les utilisateurs associés à ce rôle
    public function getUsers(): Collection
    {
        return $this->users;
    }

    // Ajoute un utilisateur à ce rôle
    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setRole($this);
        }

        return $this;
    }

    // Supprime un utilisateur de ce rôle
    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // Met à jour le côté propriétaire à null (sauf si déjà modifié)
            if ($user->getRole() === $this) {
                $user->setRole(null);
            }
        }

        return $this;
    }
}
