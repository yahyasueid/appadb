<?php
// src/Repository/ParrainageImamRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\ParrainageImam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository ParrainageImam — fiches أئمه ومعلمين.
 *
 * @extends ServiceEntityRepository<ParrainageImam>
 */
class ParrainageImamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParrainageImam::class);
    }

    /**
     * Toutes les fiches imams/enseignants d'une association.
     *
     * @return ParrainageImam[]
     */
    public function findByAssociation(Association $association): array
    {
        return $this->createQueryBuilder('fi')
            ->innerJoin('fi.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('fi.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fiches actives (مكفول) avec parrainage chargé.
     *
     * @return ParrainageImam[]
     */
    public function findActifs(Association $association): array
    {
        return $this->createQueryBuilder('fi')
            ->innerJoin('fi.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere("par.statut = 'مكفول'")
            ->setParameter('assoc', $association)
            ->orderBy('fi.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom ou diplôme.
     *
     * @return ParrainageImam[]
     */
    public function search(string $query, ?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('fi')
            ->innerJoin('fi.parrainage', 'par')
            ->addSelect('par')
            ->where('LOWER(fi.nomComplet) LIKE :q OR LOWER(fi.diplome) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('fi.nomComplet', 'ASC');

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Répartition par métier (imam, enseignant, muezzin…).
     * Retourne : [['metier' => 'imam', 'nb' => 22], ...]
     */
    public function countByMetier(Association $association): array
    {
        return $this->createQueryBuilder('fi')
            ->select('fi.metier, COUNT(fi.id) AS nb')
            ->innerJoin('fi.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('fi.metier')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
