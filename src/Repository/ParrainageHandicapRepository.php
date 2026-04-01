<?php
// src/Repository/ParrainageHandicapRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\ParrainageHandicap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository ParrainageHandicap — fiches ذوي الإحتياجات الخاصة.
 *
 * @extends ServiceEntityRepository<ParrainageHandicap>
 */
class ParrainageHandicapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParrainageHandicap::class);
    }

    /**
     * Toutes les fiches besoins spéciaux d'une association.
     *
     * @return ParrainageHandicap[]
     */
    public function findByAssociation(Association $association): array
    {
        return $this->createQueryBuilder('fh')
            ->innerJoin('fh.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('fh.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fiches avec parrainage actif (مكفول).
     *
     * @return ParrainageHandicap[]
     */
    public function findActifs(Association $association): array
    {
        return $this->createQueryBuilder('fh')
            ->innerJoin('fh.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere("par.statut = 'مكفول'")
            ->setParameter('assoc', $association)
            ->orderBy('fh.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou type d'handicap.
     *
     * @return ParrainageHandicap[]
     */
    public function search(string $query, ?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('fh')
            ->innerJoin('fh.parrainage', 'par')
            ->addSelect('par')
            ->where('LOWER(fh.nomComplet) LIKE :q OR LOWER(fh.causeHandicap) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('fh.nomComplet', 'ASC');

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Répartition par type d'handicap.
     * Retourne : [['typeHandicap' => 'moteur', 'nb' => 14], ...]
     */
    public function countByTypeHandicap(Association $association): array
    {
        return $this->createQueryBuilder('fh')
            ->select('fh.typeHandicap, COUNT(fh.id) AS nb')
            ->innerJoin('fh.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('fh.typeHandicap')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Personnes nécessitant un traitement médical (coût > 0).
     *
     * @return ParrainageHandicap[]
     */
    public function findWithTraitement(Association $association): array
    {
        return $this->createQueryBuilder('fh')
            ->innerJoin('fh.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere('fh.coutTraitementMensuel > 0')
            ->andWhere("par.statut = 'مكفول'")
            ->setParameter('assoc', $association)
            ->orderBy('fh.coutTraitementMensuel', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
