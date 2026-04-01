<?php
// src/Repository/ProjetPhotoRepository.php

namespace App\Repository;

use App\Entity\ProjetPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjetPhoto>
 */
class ProjetPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjetPhoto::class);
    }

    /**
     * Photos d'un projet triées par position
     */
    public function findByProjet(int $projetId): array
    {
        return $this->createQueryBuilder('ph')
            ->where('ph.projet = :pid')
            ->setParameter('pid', $projetId)
            ->orderBy('ph.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
