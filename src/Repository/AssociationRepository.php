<?php
// src/Repository/AssociationRepository.php

namespace App\Repository;

use App\Entity\Association;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AssociationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Association::class);
    }

    /**
     * Toutes les associations actives, triées par nom
     */
    
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.isActive = true')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Associations avec leurs projets chargés en une seule requête
     * Évite le problème N+1 pour le tableau de bord
     */
    public function findAllWithProjets(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.projets', 'p')
            ->addSelect('p')
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche par nom, sigle ou responsable
     */
    public function search(string $query): array
    {
        $q = '%' . mb_strtolower($query) . '%';

        return $this->createQueryBuilder('a')
            ->where('LOWER(a.nom) LIKE :q')
            ->orWhere('LOWER(a.sigle) LIKE :q')
            ->orWhere('LOWER(a.responsable) LIKE :q')
            ->orWhere('LOWER(a.nomAr) LIKE :q')
            ->setParameter('q', $q)
            ->orderBy('a.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques rapides pour le tableau de bord
     * Retourne : total, actives, inactives, total_projets
     */
    public function getStats(): array
    {
        $result = $this->createQueryBuilder('a')
            ->select(
                'COUNT(a.id) AS total',
                'SUM(CASE WHEN a.isActive = true THEN 1 ELSE 0 END) AS actives',
                'SUM(CASE WHEN a.isActive = false THEN 1 ELSE 0 END) AS inactives',
            )
            ->getQuery()
            ->getSingleResult();

        // Compter les projets séparément pour éviter les produits cartésiens
        $projetsCount = $this->createQueryBuilder('a')
            ->select('COUNT(p.id)')
            ->leftJoin('a.projets', 'p')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total'         => (int) $result['total'],
            'actives'       => (int) $result['actives'],
            'inactives'     => (int) $result['inactives'],
            'total_projets' => (int) $projetsCount,
        ];
    }
}