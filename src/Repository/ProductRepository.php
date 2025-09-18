<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findProductsNotInOffer(): array
    {
        /*
        return $this->createQueryBuilder('p')
            ->leftJoin('App\Entity\Offer', 'o', 'WITH', 'o.product = p.id AND o.active = true')
            ->where('o.id IS NULL')
            ->getQuery()
            ->getResult();
        */

        $now = new \DateTime();
        return $this->createQueryBuilder('p')
            ->leftJoin('App\Entity\Offer', 'o', 'WITH', 'o.product = p.id AND o.startDate <= :now AND o.endDate >= :now')
            ->where('o.id IS NULL')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
