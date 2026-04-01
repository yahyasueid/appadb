<?php
// src/Repository/ParrainageOrphelinRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\ParrainageOrphelin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository ParrainageOrphelin — fiches يتيم.
 *
 * @extends ServiceEntityRepository<ParrainageOrphelin>
 */
class ParrainageOrphelinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParrainageOrphelin::class);
    }

    /**
     * Toutes les fiches orphelins d'une association (via JOIN parrainage).
     *
     * @return ParrainageOrphelin[]
     */
    public function findByAssociation(Association $association): array
    {
        return $this->createQueryBuilder('fo')
            ->innerJoin('fo.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('fo.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fiches orphelins avec parrainage actif (مكفول).
     *
     * @return ParrainageOrphelin[]
     */
    public function findActifs(Association $association): array
    {
        return $this->createQueryBuilder('fo')
            ->innerJoin('fo.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere("par.statut = 'مكفول'")
            ->setParameter('assoc', $association)
            ->orderBy('fo.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom complet (insensible à la casse).
     *
     * @return ParrainageOrphelin[]
     */
    public function search(string $query, ?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('fo')
            ->innerJoin('fo.parrainage', 'par')
            ->addSelect('par')
            ->where('LOWER(fo.nomComplet) LIKE :q')
            ->setParameter('q', '%' . strtolower($query) . '%')
            ->orderBy('fo.nomComplet', 'ASC');

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Orphelins scolarisés (ayant une école renseignée).
     *
     * @return ParrainageOrphelin[]
     */
    public function findScolarises(Association $association): array
    {
        return $this->createQueryBuilder('fo')
            ->innerJoin('fo.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('fo.ecole IS NOT NULL')
            ->andWhere("par.statut = 'مكفول'")
            ->setParameter('assoc', $association)
            ->orderBy('fo.nomComplet', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Répartition par type d'orphelin (père décédé, mère, les deux).
     * Retourne : [['typeOrphelin' => 'pere_decede', 'nb' => 35], ...]
     */
    public function countByTypeOrphelin(Association $association): array
    {
        return $this->createQueryBuilder('fo')
            ->select('fo.typeOrphelin, COUNT(fo.id) AS nb')
            ->innerJoin('fo.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('fo.typeOrphelin')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
