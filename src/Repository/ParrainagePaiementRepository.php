<?php
// src/Repository/ParrainagePaiementRepository.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  REPOSITORY PARRAINAGE PAIEMENT                              ║
// ║                                                              ║
// ║  Requêtes métier sur les versements des parrains.            ║
// ║                                                              ║
// ║  Méthodes principales :                                      ║
// ║  — findByParrainage()           liste versements d'un dossier║
// ║  — getTotalForParrainage()      total versé pour un dossier  ║
// ║  — findByAssociationAndPeriode()bilan sur une période        ║
// ║  — getTotalByAssociationAndAnnee()  total annuel             ║
// ║  — getTotalByAssociation()      total global une association  ║
// ║  — getStatsByMode()             répartition par mode         ║
// ║  — getStatsByMois()             courbe mensuelle             ║
// ║  — findRecents()                derniers versements          ║
// ║  — findSansPeriode()            versements sans période      ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Repository;

use App\Entity\Association;
use App\Entity\Parrainage;
use App\Entity\ParrainagePaiement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParrainagePaiement>
 */
class ParrainagePaiementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParrainagePaiement::class);
    }

    // ══════════════════════════════════════════════
    // LISTE PAR PARRAINAGE
    // ══════════════════════════════════════════════

    /**
     * Tous les versements d'un parrainage, du plus récent au plus ancien.
     * Utilisé dans la page show du parrainage.
     *
     * @return ParrainagePaiement[]
     */
    public function findByParrainage(Parrainage $parrainage): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.parrainage = :par')
            ->setParameter('par', $parrainage)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Versements d'un parrainage avec le saisisseur chargé (évite N+1).
     *
     * @return ParrainagePaiement[]
     */
    public function findByParrainageWithUser(Parrainage $parrainage): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.saisirPar', 'u')
            ->addSelect('u')
            ->where('p.parrainage = :par')
            ->setParameter('par', $parrainage)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ══════════════════════════════════════════════
    // TOTAUX
    // ══════════════════════════════════════════════

    /**
     * Total versé pour un parrainage donné.
     * Utilisé dans la carte et la page show pour calculer le taux.
     */
    public function getTotalForParrainage(Parrainage $parrainage): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->where('p.parrainage = :par')
            ->setParameter('par', $parrainage)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Total versé pour toute une association (tous parrainages confondus).
     * Utilisé dans le tableau de bord global.
     */
    public function getTotalByAssociation(Association $association): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Total versé pour une association sur une année donnée.
     * Ex : getTotalByAssociationAndAnnee($assoc, 2024)
     */
    public function getTotalByAssociationAndAnnee(
        Association $association,
        int $annee
    ): float {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.montant)')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('YEAR(p.datePaiement) = :annee')
            ->setParameter('assoc', $association)
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    // ══════════════════════════════════════════════
    // PAR PÉRIODE
    // ══════════════════════════════════════════════

    /**
     * Tous les paiements d'une association entre deux dates.
     * Utilisé pour le bilan financier et l'export.
     *
     * @return ParrainagePaiement[]
     */
    public function findByAssociationAndPeriode(
        Association $association,
        \DateTimeInterface $debut,
        \DateTimeInterface $fin
    ): array {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.parrainage', 'par')
            ->leftJoin('par.parrain', 'parrain')
            ->addSelect('par', 'parrain')
            ->where('par.association = :assoc')
            ->andWhere('p.datePaiement BETWEEN :debut AND :fin')
            ->setParameter('assoc', $association)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Paiements filtrés par type de parrainage et période.
     * Ex : tous les versements des parrainages d'orphelins en 2024.
     *
     * @return ParrainagePaiement[]
     */
    public function findByTypeAndAnnee(
        Association $association,
        string $type,
        int $annee
    ): array {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('par.type = :type')
            ->andWhere('YEAR(p.datePaiement) = :annee')
            ->setParameter('assoc', $association)
            ->setParameter('type', $type)
            ->setParameter('annee', $annee)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // ══════════════════════════════════════════════
    // STATISTIQUES
    // ══════════════════════════════════════════════

    /**
     * Répartition des versements par mode de paiement.
     * Utilisé pour le graphique camembert du tableau de bord.
     *
     * Retourne : [['mode' => 'virement', 'nb' => 45, 'total' => 225000.00], ...]
     */
    public function getStatsByMode(
        Association $association,
        ?int $annee = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('p.mode, COUNT(p.id) AS nb, SUM(p.montant) AS total')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('p.mode')
            ->orderBy('total', 'DESC');

        if ($annee) {
            $qb->andWhere('YEAR(p.datePaiement) = :annee')
               ->setParameter('annee', $annee);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Total mensuel sur une année (courbe de versements).
     * Utilisé pour le graphique ligne du tableau de bord.
     *
     * Retourne : [['mois' => 1, 'total' => 45000.00], ['mois' => 2, ...], ...]
     * Les mois sans versement ne sont PAS inclus.
     */
    public function getStatsByMois(
        Association $association,
        int $annee
    ): array {
        return $this->createQueryBuilder('p')
            ->select('MONTH(p.datePaiement) AS mois, SUM(p.montant) AS total, COUNT(p.id) AS nb')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('YEAR(p.datePaiement) = :annee')
            ->setParameter('assoc', $association)
            ->setParameter('annee', $annee)
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Répartition par type de parrainage (orphelin, famille, imam, handicap).
     * Retourne : [['type' => 'orphelin', 'nb' => 30, 'total' => 150000.00], ...]
     */
    public function getStatsByType(
        Association $association,
        ?int $annee = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->select('par.type AS type, COUNT(p.id) AS nb, SUM(p.montant) AS total')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->groupBy('par.type')
            ->orderBy('total', 'DESC');

        if ($annee) {
            $qb->andWhere('YEAR(p.datePaiement) = :annee')
               ->setParameter('annee', $annee);
        }

        return $qb->getQuery()->getResult();
    }

    // ══════════════════════════════════════════════
    // DASHBOARD / DERNIERS VERSEMENTS
    // ══════════════════════════════════════════════

    /**
     * Derniers versements reçus — widget tableau de bord.
     * Charge le parrainage et le parrain en JOIN pour éviter N+1.
     *
     * @return ParrainagePaiement[]
     */
    public function findRecents(
        Association $association,
        int $limit = 10
    ): array {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.parrainage', 'par')
            ->leftJoin('par.parrain', 'parrain')
            ->addSelect('par', 'parrain')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('p.datePaiement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Derniers versements saisis par un utilisateur.
     *
     * @return ParrainagePaiement[]
     */
    public function findBySaisirPar(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.saisirPar = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // ══════════════════════════════════════════════
    // UTILITAIRES
    // ══════════════════════════════════════════════

    /**
     * Versements sans période renseignée.
     * Permet de repérer les saisies incomplètes.
     *
     * @return ParrainagePaiement[]
     */
    public function findSansPeriode(Association $association): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('p.periodeConcernee IS NULL OR p.periodeConcernee = :empty')
            ->setParameter('assoc', $association)
            ->setParameter('empty', '')
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Versements sans justificatif joint.
     * Utilisé pour le tableau de bord de contrôle.
     *
     * @return ParrainagePaiement[]
     */
    public function findSansJustificatif(Association $association): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->andWhere('p.justificatifFilename IS NULL')
            ->setParameter('assoc', $association)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Années disponibles pour les filtres (liste dédupliquée triée DESC).
     *
     * @return int[]
     */
    public function findAnnees(Association $association): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT YEAR(p.datePaiement) AS annee')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->orderBy('annee', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'annee');
    }

    /**
     * Nombre total de versements pour une association.
     */
    public function countByAssociation(Association $association): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->innerJoin('p.parrainage', 'par')
            ->where('par.association = :assoc')
            ->setParameter('assoc', $association)
            ->getQuery()
            ->getSingleScalarResult();
    }
}