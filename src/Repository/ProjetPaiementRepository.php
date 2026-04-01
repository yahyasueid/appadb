<?php
// src/Repository/ProjetPaiementRepository.php

namespace App\Repository;

use App\Entity\ProjetPaiement;
use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjetPaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjetPaiement::class);
    }

    /**
     * Tous les paiements d'un projet, du plus récent au plus ancien
     */
    public function findByProjet(Projet $projet): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.projet = :projet')
            ->setParameter('projet', $projet)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Montant total payé pour un projet (requête SQL directe — performant)
     */
    public function getMontantTotalByProjet(Projet $projet): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant) AS total')
            ->where('p.projet = :projet')
            ->setParameter('projet', $projet)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}