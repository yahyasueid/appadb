<?php
// src/Repository/ParrainageFamilleRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\ParrainageFamille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository ParrainageFamille — fiches أسرة.
 *
 * @extends ServiceEntityRepository<ParrainageFamille>
 */
class ParrainageFamilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParrainageFamille::class);
    }

    /**
     * Toutes les fiches familles d'une association.
     *
     * @return ParrainageFamille[]
     */
    public function findByAssociation(Association $association): array
    {
        return $this->createQueryBuilder('ff')
            ->innerJoin('ff.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('ff.nomChef', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Familles avec parrainage actif (مكفول).
     *
     * @return ParrainageFamille[]
     */
    public function findActifs(Association $association): array
    {
        return $this->createQueryBuilder('ff')
            ->innerJoin('ff.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere("par.statut = 'مكفول'")
            ->setParameter('assoc', $association)
            ->orderBy('ff.nomChef', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom du chef de famille.
     *
     * @return ParrainageFamille[]
     */
    public function search(string $query, ?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('ff')
            ->innerJoin('ff.parrainage', 'par')
            ->addSelect('par')
            ->where('LOWER(ff.nomChef) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('ff.nomChef', 'ASC');

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Répartition par type de logement.
     * Retourne : [['typeLogement' => 'loyer', 'nb' => 18], ...]
     */
    public function countByTypeLogement(Association $association): array
    {
        return $this->createQueryBuilder('ff')
            ->select('ff.typeLogement, COUNT(ff.id) AS nb')
            ->innerJoin('ff.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('ff.typeLogement IS NOT NULL')
            ->setParameter('assoc', $association)
            ->groupBy('ff.typeLogement')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
