<?php

namespace App\Trait;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

trait ImageUploadTrait
{
    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $imageName = null;

    /**
     * @var File|null
     */
    #[Assert\Image(
        maxSize: "2M",
        mimeTypes: ["image/jpeg", "image/png"],
        mimeTypesMessage: "Veuillez télécharger une image valide (JPG ou PNG)"
    )]
    private ?File $imageFile = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;
        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): self
    {
        $this->imageFile = $imageFile;
        
        if ($imageFile) {
            // Pour forcer Doctrine à détecter le changement
            $this->updatedAt = new \DateTime();
        }
        
        return $this;
    }
}