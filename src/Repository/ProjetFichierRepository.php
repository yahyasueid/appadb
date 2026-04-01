<?php
// src/Repository/ProjetFichierRepository.php

namespace App\Repository;

use App\Entity\ProjetFichier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjetFichier>
 */
class ProjetFichierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjetFichier::class);
    }

    /**
     * Fichiers d'un projet par catégorie
     */
    public function findByProjetAndCategorie(int $projetId, string $categorie): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.projet = :pid')
            ->andWhere('f.categorie = :cat')
            ->setParameter('pid', $projetId)
            ->setParameter('cat', $categorie)
            ->orderBy('f.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les rapports d'un projet
     */
    public function findRapports(int $projetId): array
    {
        return $this->findByProjetAndCategorie($projetId, ProjetFichier::CAT_RAPPORT);
    }

    /**
     * Tous les transferts financiers d'un projet
     */
    public function findTransferts(int $projetId): array
    {
        return $this->findByProjetAndCategorie($projetId, ProjetFichier::CAT_TRANSFERT);
    }
}
