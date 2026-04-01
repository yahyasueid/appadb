<?php
// src/Repository/RapportParrainageRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\Parrainage;
use App\Entity\RapportParrainage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository RapportParrainage — rapports semestriels envoyés aux parrains.
 *
 * Contrainte unique : 1 rapport par (parrainage_id + annee + semestre).
 *
 * @extends ServiceEntityRepository<RapportParrainage>
 */
class RapportParrainageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RapportParrainage::class);
    }

    /**
     * Tous les rapports d'un parrainage, du plus récent au plus ancien.
     *
     * @return RapportParrainage[]
     */
    public function findByParrainage(Parrainage $parrainage): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.parrainage = :par')
            ->setParameter('par', $parrainage)
            ->orderBy('r.annee', 'DESC')
            ->addOrderBy('r.semestre', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un rapport précis par sa clé unique (parrainage + année + semestre).
     * Retourne null si le rapport n'existe pas encore pour cette période.
     */
    public function findOneByPeriode(
        Parrainage $parrainage,
        int $annee,
        int $semestre
    ): ?RapportParrainage {
        return $this->createQueryBuilder('r')
            ->where('r.parrainage = :par')
            ->andWhere('r.annee = :annee')
            ->andWhere('r.semestre = :semestre')
            ->setParameter('par', $parrainage)
            ->setParameter('annee', $annee)
            ->setParameter('semestre', $semestre)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Rapports en attente d'envoi (statut = brouillon ou pret)
     * pour une association — vue de travail de l'employé parrainages.
     *
     * @return RapportParrainage[]
     */
    public function findNonEnvoyes(Association $association): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere("r.statut != 'envoye'")
            ->setParameter('assoc', $association)
            ->orderBy('r.annee', 'ASC')
            ->addOrderBy('r.semestre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rapports envoyés pour une association sur une année donnée.
     *
     * @return RapportParrainage[]
     */
    public function findEnvoyesByAnnee(Association $association, int $annee): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.parrainage', 'par')
            ->addSelect('par')
            ->where('par.association = :assoc')
            ->andWhere('r.annee = :annee')
            ->andWhere("r.statut = 'envoye'")
            ->setParameter('assoc', $association)
            ->setParameter('annee', $annee)
            ->orderBy('r.semestre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Parrainages actifs N'AYANT PAS encore de rapport pour un semestre donné.
     * Utile pour rappeler à l'employé quels rapports restent à créer.
     *
     * @return Parrainage[]
     */
    public function findParrainagesSansRapport(
        Association $association,
        int $annee,
        int $semestre
    ): array {
        // Sous-requête : IDs des parrainages ayant déjà leur rapport
        $deja = $this->createQueryBuilder('r2')
            ->select('IDENTITY(r2.parrainage)')
            ->where('r2.annee = :annee')
            ->andWhere('r2.semestre = :semestre')
            ->getDQL();

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('par')
            ->from(Parrainage::class, 'par')
            ->where('par.association = :assoc')
            ->andWhere("par.statut = 'مكفول'")
            ->andWhere('par.id NOT IN (' . $deja . ')')
            ->setParameter('assoc', $association)
            ->setParameter('annee', $annee)
            ->setParameter('semestre', $semestre)
            ->orderBy('par.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les rapports par statut pour le tableau de bord.
     * Retourne : ['brouillon' => 5, 'pret' => 3, 'envoye' => 42]
     */
    public function countByStatut(Association $association, ?int $annee = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.statut, COUNT(r.id) AS nb')
            ->innerJoin('r.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('r.statut');

        if ($annee) {
            $qb->andWhere('r.annee = :annee')
               ->setParameter('annee', $annee);
        }

        $rows = $qb->getQuery()->getResult();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['statut']] = (int) $row['nb'];
        }
        return $result;
    }

    /**
     * Années pour lesquelles des rapports existent (pour les filtres).
     *
     * @return int[]
     */
    public function findAnnees(Association $association): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('DISTINCT r.annee')
            ->innerJoin('r.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('r.annee', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'annee');
    }
}
