<?php
// src/Repository/ParrainageRepository.php

namespace App\Repository;

use App\Entity\Association;
use App\Entity\Parrainage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Parrainage — pivot central du module.
 *
 * Fournit des méthodes pour :
 *  - Filtrer par type (orphelin, famille, imam, handicap)
 *  - Filtrer par statut arabe (جديد, معتمدة, مكفول, ملغي)
 *  - Charger les fiches spécifiques avec JOIN (évite N+1)
 *  - Statistiques par association
 *
 * @extends ServiceEntityRepository<Parrainage>
 */
class ParrainageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parrainage::class);
    }

    // ══════════════════════════════════════════════
    // REQUÊTES DE BASE
    // ══════════════════════════════════════════════

    /**
     * QueryBuilder de base avec les JOINs communs.
     * Utilisé comme point de départ pour toutes les autres requêtes.
     */
    private function baseQb(): QueryBuilder
    {
        return $this->createQueryBuilder('par')
            ->leftJoin('par.association', 'assoc')
            ->leftJoin('par.parrain', 'parrain')
            ->addSelect('assoc', 'parrain');
    }

    /**
     * Tous les parrainages d'une association, ordonnés par date de création DESC.
     *
     * @return Parrainage[]
     */
    public function findByAssociation(Association $association): array
    {
        return $this->baseQb()
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('par.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Parrainages filtrés par association + type + statut.
     * Tous les paramètres sont optionnels.
     *
     * @return Parrainage[]
     */
    public function findFiltered(
        ?Association $association = null,
        ?string $type   = null,
        ?string $statut = null,
        ?int    $annee  = null
    ): array {
        $qb = $this->baseQb();

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }
        if ($type) {
            $qb->andWhere('par.type = :type')
               ->setParameter('type', $type);
        }
        if ($statut) {
            $qb->andWhere('par.statut = :statut')
               ->setParameter('statut', $statut);
        }
        if ($annee) {
            $qb->andWhere('par.annee = :annee')
               ->setParameter('annee', $annee);
        }

        return $qb->orderBy('par.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Parrainages en attente de validation (statut = جديد).
     * Utilisé pour le badge de notification du directeur.
     *
     * @return Parrainage[]
     */
    public function findEnAttente(?Association $association = null): array
    {
        $qb = $this->baseQb()
            ->where('par.statut = :statut')
            ->setParameter('statut', Parrainage::STATUT_NOUVEAU)
            ->orderBy('par.createdAt', 'ASC');

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre de dossiers en attente — pour le badge sidebar.
     */
    public function countEnAttente(?Association $association = null): int
    {
        $qb = $this->createQueryBuilder('par')
            ->select('COUNT(par.id)')
            ->where('par.statut = :statut')
            ->setParameter('statut', Parrainage::STATUT_NOUVEAU);

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Parrainages actifs (مكفول) — ceux qui reçoivent des versements.
     *
     * @return Parrainage[]
     */
    public function findActifs(?Association $association = null): array
    {
        $qb = $this->baseQb()
            ->where('par.statut = :statut')
            ->setParameter('statut', Parrainage::STATUT_ACTIF)
            ->orderBy('par.dateDebut', 'DESC');

        if ($association) {
            $qb->andWhere('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return $qb->getQuery()->getResult();
    }

    // ══════════════════════════════════════════════
    // CHARGEMENT DES FICHES SPÉCIFIQUES
    // ══════════════════════════════════════════════

    /**
     * Charge un parrainage avec SA fiche spécifique selon son type.
     * Évite les requêtes N+1 lors de l'affichage de la fiche détaillée.
     */
    public function findWithFiche(int $id): ?Parrainage
    {
        // Premier fetch sans JOIN de fiche (on ne connaît pas le type)
        $parrainage = $this->find($id);
        if (!$parrainage) return null;

        // Requête ciblée selon le type
        return match($parrainage->getType()) {
            Parrainage::TYPE_ORPHELIN => $this->createQueryBuilder('par')
                ->leftJoin('par.ficheOrphelin', 'fo')->addSelect('fo')
                ->where('par.id = :id')->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult(),

            Parrainage::TYPE_FAMILLE => $this->createQueryBuilder('par')
                ->leftJoin('par.ficheFamille', 'ff')->addSelect('ff')
                ->where('par.id = :id')->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult(),

            Parrainage::TYPE_IMAM => $this->createQueryBuilder('par')
                ->leftJoin('par.ficheImam', 'fi')->addSelect('fi')
                ->where('par.id = :id')->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult(),

            Parrainage::TYPE_HANDICAP => $this->createQueryBuilder('par')
                ->leftJoin('par.ficheHandicap', 'fh')->addSelect('fh')
                ->where('par.id = :id')->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult(),

            default => $parrainage,
        };
    }

    /**
     * Charge un parrainage avec ses paiements et ses rapports.
     * Utilisé pour la page de détail show.
     */
    public function findWithDetails(int $id): ?Parrainage
    {
        return $this->createQueryBuilder('par')
            ->leftJoin('par.parrain', 'parrain')
            ->leftJoin('par.association', 'assoc')
            ->leftJoin('par.paiements', 'pai')
            ->leftJoin('par.rapports', 'rap')
            ->addSelect('parrain', 'assoc', 'pai', 'rap')
            ->where('par.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // ══════════════════════════════════════════════
    // STATISTIQUES
    // ══════════════════════════════════════════════

    /**
     * Nombre de parrainages par statut pour une association.
     * Retourne : ['جديد' => 5, 'معتمدة' => 12, 'مكفول' => 87, 'ملغي' => 3]
     */
    public function countByStatut(Association $association): array
    {
        $rows = $this->createQueryBuilder('par')
            ->select('par.statut, COUNT(par.id) AS nb')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('par.statut')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['statut']] = (int) $row['nb'];
        }
        return $result;
    }

    /**
     * Nombre de parrainages par type pour une association.
     * Retourne : ['orphelin' => 40, 'famille' => 25, 'imam' => 10, 'handicap' => 12]
     */
    public function countByType(Association $association): array
    {
        $rows = $this->createQueryBuilder('par')
            ->select('par.type, COUNT(par.id) AS nb')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('par.type')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['type']] = (int) $row['nb'];
        }
        return $result;
    }

    /**
     * Montant total versé pour tous les parrainages actifs d'une association.
     */
    public function getTotalVerse(Association $association): float
    {
        $result = $this->createQueryBuilder('par')
            ->select('SUM(pai.montant) AS total')
            ->innerJoin('par.paiements', 'pai')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Parrainages créés par un utilisateur donné.
     *
     * @return Parrainage[]
     */
    public function findByCreePar(User $user): array
    {
        return $this->baseQb()
            ->where('par.creePar = :user')
            ->setParameter('user', $user)
            ->orderBy('par.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Années disponibles pour les filtres (liste dédupliquée).
     *
     * @return int[]
     */
    public function findAnnees(?Association $association = null): array
    {
        $qb = $this->createQueryBuilder('par')
            ->select('DISTINCT par.annee')
            ->orderBy('par.annee', 'DESC');

        if ($association) {
            $qb->where('par.association = :assoc')
               ->setParameter('assoc', $association);
        }

        return array_column($qb->getQuery()->getScalarResult(), 'annee');
    }
}
