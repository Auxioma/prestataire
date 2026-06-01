<?php

namespace App\Repository;

use App\Entity\ServiceCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceCategory>
 */
class ServiceCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceCategory::class);
    }

    public function findWithSubCategories(): array
    {
        return $this->createQueryBuilder('c')
            // On sélectionne la catégorie ET ses sous-catégories
            ->addSelect('sub')
            // On fait la jointure avec la relation "subCategories" définie dans ton entité
            ->leftJoin('c.subCategories', 'sub')
            // Filtres : uniquement les catégories racines et actives
            ->where('c.parent IS NULL')
            ->andWhere('c.isActive = :active')
            // On peut aussi filtrer pour n'avoir que les sous-catégories actives
            ->andWhere('sub.id IS NULL OR sub.isActive = :active')
            ->setParameter('active', true)
            // Tri par position
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('sub.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNavbarCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->andWhere('c.isActive = true')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return ServiceCategory[] Returns an array of ServiceCategory objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ServiceCategory
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
