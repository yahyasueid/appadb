<?php
// src/Repository/ParrainRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\Parrain;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Parrain — requêtes sur les kafils (donnateurs).
 *
 * @extends ServiceEntityRepository<Parrain>
 */
class ParrainRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parrain::class);
    }

    // ══════════════════════════════════════════════
    // RECHERCHE / LISTE
    // ══════════════════════════════════════════════

    /**
     * Tous les parrains d'une association, triés par nom.
     *
     * @return Parrain[]
     */
    public function findByAssociation(Association $association): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou email (insensible à la casse).
     *
     * @return Parrain[]
     */
    public function search(string $query, ?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('LOWER(p.nom) LIKE :q OR LOWER(p.email) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('p.nom', 'ASC');

        if ($association) {
            $qb->andWhere('p.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Parrain avec tous ses parrainages chargés (évite N+1).
     */
    public function findWithParrainages(int $id): ?Parrain
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.parrainages', 'par')
            ->addSelect('par')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Parrains ayant au moins un parrainage actif (مكفول).
     *
     * @return Parrain[]
     */
    public function findActifs(?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.parrainages', 'par')
            ->where("par.statut = 'مكفول'")
            ->orderBy('p.nom', 'ASC')
            ->distinct();

        if ($association) {
            $qb->andWhere('p.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre total de parrains par association.
     */
    public function countByAssociation(Association $association): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.association = :assoc')
            ->setParameter('assoc', $association)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
