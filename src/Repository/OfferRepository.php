<?php

namespace App\Repository;

use App\Entity\Offer;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offer>
 *
 * @method Offer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offer[]    findAll()
 * @method Offer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    /**
     * Devuelve las ofertas activas en este momento (startDate <= now <= endDate).
     */
    public function findActive(\DateTimeInterface $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable();

        return $this->createQueryBuilder('o')
            ->andWhere('o.startDate <= :now')
            ->andWhere('o.endDate >= :now')
            ->setParameter('now', $now)
            ->orderBy('o.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Devuelve la oferta activa (si existe) para un producto concreto.
     */
    public function findActiveForProduct(Product $product, \DateTimeInterface $now = null): ?Offer
    {
        $now = $now ?? new \DateTimeImmutable();

        return $this->createQueryBuilder('o')
            ->andWhere('o.product = :product')
            ->andWhere('o.startDate <= :now')
            ->andWhere('o.endDate >= :now')
            ->setParameter('product', $product)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
