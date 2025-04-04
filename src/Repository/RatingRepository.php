<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Calcule la note moyenne d'une entreprise avec détour par requête SQL directe
     * pour éviter les problèmes de cache
     */
    public function calculateAverageRating(Company $company): float
    {
        try {
            // Utiliser une requête native pour éviter les problèmes de cache
            $conn = $this->getEntityManager()->getConnection();
            $sql = '
                SELECT AVG(r.score) as average_rating 
                FROM rating r 
                WHERE r.company_id = :companyId
            ';
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery(['companyId' => $company->getId()]);
            $avgResult = $result->fetchOne();
            
            return $avgResult ? round((float) $avgResult, 1) : 0;
        } catch (\Exception $e) {
            // En cas d'erreur, utiliser la méthode alternative
            return $this->calculateAverageRatingAlternative($company);
        }
    }
    
    /**
     * Méthode alternative pour calculer la moyenne
     */
    private function calculateAverageRatingAlternative(Company $company): float
    {
        $ratings = $this->findAllScoresByCompany($company);
        $count = count($ratings);
        if ($count === 0) {
            return 0;
        }
        
        $sum = array_sum(array_column($ratings, 'score'));
        return round($sum / $count, 1);
    }
    
    /**
     * Récupère toutes les notes d'une entreprise pour vérification
     */
    public function findAllScoresByCompany(Company $company): array
    {
        return $this->createQueryBuilder('r')
            ->select('r.score', 'IDENTITY(r.user) as userId')
            ->where('r.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getResult();
    }
}
