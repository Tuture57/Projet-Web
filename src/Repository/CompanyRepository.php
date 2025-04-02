<?php

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findUniqueCountries(): array
    {
        return $this->createQueryBuilder('c')
            ->select('DISTINCT a.country')
            ->join('c.address', 'a')
            ->where('a.country IS NOT NULL')
            ->orderBy('a.country', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUniqueCities(?string $country = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('DISTINCT a.city')
            ->join('c.address', 'a')
            ->where('a.city IS NOT NULL')
            ->orderBy('a.city', 'ASC');
            
        if ($country) {
            $qb->andWhere('a.country = :country')
               ->setParameter('country', $country);
        }
        
        return $qb->getQuery()->getResult();
    }

    // Suppression de calculateAverageRating qui est maintenant dans RatingRepository

    //    /**
    //     * @return Company[] Returns an array of Company objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Company
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
