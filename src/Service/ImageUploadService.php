<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageUploadService
{
    private SluggerInterface $slugger;
    private ParameterBagInterface $params;

    public function __construct(SluggerInterface $slugger, ParameterBagInterface $params)
    {
        $this->slugger = $slugger;
        $this->params = $params;
    }

    /**
     * Upload une image et retourne le nouveau nom de fichier
     */
    public function uploadImage(UploadedFile $file, string $type = 'default', string $directory = null): string
    {
        // Générer un nom de fichier unique
        $newFilename = $type . '_' . bin2hex(random_bytes(8)) . '.' . $file->guessExtension();
        
        // Déterminer le répertoire de destination
        $uploadDir = $directory ?: $this->params->get('uploads_directory');
        
        // Déplacer le fichier
        $file->move($uploadDir, $newFilename);
        
        return $newFilename;
    }

    /**
     * Supprimer une image existante
     */
    public function removeImage(string $filename, string $directory = null): bool
    {
        $uploadDir = $directory ?: $this->params->get('uploads_directory');
        $filePath = $uploadDir . '/' . $filename;
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
}