<?php
// src/Repository/ProjetRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Projet
 *
 * @method Projet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Projet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Projet[]    findAll()
 * @method Projet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    // ══════════════════════════════════════════════
    // LECTURE — Listes
    // ══════════════════════════════════════════════

    /**
     * Tous les projets avec TOUTES leurs relations pré-chargées en 1 requête.
     * Utilisé pour la page index (évite N+1).
     *
     * @return Projet[]
     */
    public function findAllWithRelations(): array
    {
        return $this->baseQB()
            ->orderBy('a.nom', 'ASC')
            ->addOrderBy('p.dateContrat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Un projet avec toutes ses relations (pour la page show/rapport).
     */
    public function findOneWithRelations(int $id): ?Projet
    {
        return $this->baseQB()
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Projets d'une association, triés par date décroissante.
     *
     * @return Projet[]
     */
    public function findByAssociation(Association $assoc): array
    {
        return $this->baseQB()
            ->where('p.association = :assoc')
            ->setParameter('assoc', $assoc)
            ->orderBy('p.dateContrat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets filtrés par statut.
     *
     * @return Projet[]
     */
    public function findByStatut(string $statut): array
    {
        return $this->baseQB()
            ->where('p.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('p.dateContrat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets filtrés par type.
     *
     * @return Projet[]
     */
    public function findByType(string $type): array
    {
        return $this->baseQB()
            ->where('p.type = :type')
            ->setParameter('type', $type)
            ->orderBy('p.dateContrat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets de l'année en cours.
     *
     * @return Projet[]
     */
    public function findByAnnee(int $annee): array
    {
        return $this->baseQB()
            ->where('p.annee = :annee')
            ->setParameter('annee', $annee)
            ->orderBy('a.nom', 'ASC')
            ->addOrderBy('p.numero', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche full-text sur nom, numéro, lieu, donateur.
     *
     * @return Projet[]
     */
    public function search(string $query): array
    {
        $q = '%' . mb_strtolower(trim($query)) . '%';

        return $this->baseQB()
            ->where('LOWER(p.nom) LIKE :q')
            ->orWhere('LOWER(p.numero) LIKE :q')
            ->orWhere('LOWER(p.lieu) LIKE :q')
            ->orWhere('LOWER(p.donateur) LIKE :q')
            ->setParameter('q', $q)
            ->orderBy('p.dateContrat', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets récents (N derniers créés).
     *
     * @return Projet[]
     */
    public function findRecents(int $limit = 5): array
    {
        return $this->baseQB()
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Projets en cours ou validés (actifs).
     *
     * @return Projet[]
     */
    public function findActifs(): array
    {
        return $this->baseQB()
            ->where('p.statut IN (:statuts)')
            ->setParameter('statuts', [Projet::STATUT_VALIDE, Projet::STATUT_EN_COURS])
            ->orderBy('p.dateContrat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ══════════════════════════════════════════════
    // STATISTIQUES
    // ══════════════════════════════════════════════

    /**
     * Statistiques globales pour le header de la page liste.
     * Une seule requête SQL avec agrégats.
     */
    public function getStatsGlobales(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select(
                'COUNT(p.id)                                                         AS total',
                "SUM(CASE WHEN p.statut = 'en_cours'   THEN 1 ELSE 0 END)           AS en_cours",
                "SUM(CASE WHEN p.statut = 'termine'    THEN 1 ELSE 0 END)           AS termines",
                "SUM(CASE WHEN p.statut = 'en_attente' THEN 1 ELSE 0 END)           AS en_attente",
                "SUM(CASE WHEN p.statut = 'valide'     THEN 1 ELSE 0 END)           AS valides",
                'SUM(p.coutTotal)                                                    AS budget_total',
            )
            ->getQuery()
            ->getSingleResult();

        return [
            'total'        => (int)   ($result['total']       ?? 0),
            'en_cours'     => (int)   ($result['en_cours']    ?? 0),
            'termines'     => (int)   ($result['termines']    ?? 0),
            'en_attente'   => (int)   ($result['en_attente']  ?? 0),
            'valides'      => (int)   ($result['valides']     ?? 0),
            'total_budget' => (float) ($result['budget_total'] ?? 0),
        ];
    }

    /**
     * Statistiques par association (pour la sidebar du dashboard).
     *
     * @return array[]  [['assoc_id', 'assoc_nom', 'total', 'budget'], ...]
     */
    public function getStatsParAssociation(): array
    {
        return $this->createQueryBuilder('p')
            ->select(
                'a.id          AS assoc_id',
                'a.nom         AS assoc_nom',
                'COUNT(p.id)   AS total',
                'SUM(p.coutTotal) AS budget',
                "SUM(CASE WHEN p.statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours",
                "SUM(CASE WHEN p.statut = 'termine'  THEN 1 ELSE 0 END) AS termines",
            )
            ->join('p.association', 'a')
            ->groupBy('a.id', 'a.nom')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Statistiques par type de projet.
     *
     * @return array[]  [['type', 'total', 'budget'], ...]
     */
    public function getStatsParType(): array
    {
        return $this->createQueryBuilder('p')
            ->select(
                'p.type            AS type',
                'COUNT(p.id)       AS total',
                'SUM(p.coutTotal)  AS budget',
            )
            ->groupBy('p.type')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Statistiques par année (pour le graphique évolution).
     *
     * @return array[]  [['annee', 'total', 'budget'], ...]
     */
    public function getStatsParAnnee(): array
    {
        return $this->createQueryBuilder('p')
            ->select(
                'p.annee          AS annee',
                'COUNT(p.id)      AS total',
                'SUM(p.coutTotal) AS budget',
            )
            ->groupBy('p.annee')
            ->orderBy('p.annee', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    // ══════════════════════════════════════════════
    // VÉRIFICATIONS / HELPERS
    // ══════════════════════════════════════════════

    /**
     * Vérifie si un numéro de projet existe déjà.
     */
    public function numeroExists(string $numero, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.numero = :numero')
            ->setParameter('numero', $numero);

        if ($excludeId !== null) {
            $qb->andWhere('p.id != :id')->setParameter('id', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Compte les projets d'une année donnée (pour générer le prochain numéro).
     */
    public function countByAnnee(int $annee): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.annee = :annee')
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les projets d'une association (pour bloquer la suppression si > 0).
     */
    public function countByAssociation(Association $assoc): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.association = :assoc')
            ->setParameter('assoc', $assoc)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // ══════════════════════════════════════════════
    // PRIVÉ — QueryBuilder de base avec tous les JOIN
    // ══════════════════════════════════════════════

    /**
     * QueryBuilder de base qui pré-charge toutes les relations pour éviter N+1.
     * Toutes les méthodes publiques l'utilisent comme point de départ.
     */
    private function baseQB(): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.association', 'a')   ->addSelect('a')
            ->leftJoin('p.photos',      'ph')  ->addSelect('ph')
            ->leftJoin('p.fichiers',    'fi')  ->addSelect('fi')
            ->leftJoin('p.videos',      'vi')  ->addSelect('vi')
            ->leftJoin('p.paiements',   'pai') ->addSelect('pai')
            ->leftJoin('p.creePar',     'cu')  ->addSelect('cu')
            ->leftJoin('p.validePar',   'vu')  ->addSelect('vu');
    }
}