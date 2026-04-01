<?php
// src/Repository/ProjetVideoRepository.php

namespace App\Repository;

use App\Entity\ProjetVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjetVideo>
 */
class ProjetVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjetVideo::class);
    }

    /**
     * Vidéos d'un projet triées par date d'ajout
     */
    public function findByProjet(int $projetId): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.projet = :pid')
            ->setParameter('pid', $projetId)
            ->orderBy('v.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
