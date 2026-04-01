<?php
// src/Entity/Parrainage.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAINAGE — STATUTS ADAPTÉS AU SYSTÈME EXISTANT     ║
// ║                                                              ║
// ║  Statuts réels observés dans le code existant (capture) :    ║
// ║  - 'جديد'     = nouveau dossier soumis (non encore traité)   ║
// ║  - 'معتمدة'   = dossier approuvé / validé                   ║
// ║  - 'مكفول'    = parrainage actif (bénéficiaire pris en charge)║
// ║  - 'ملغي'     = dossier annulé / rejeté                     ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainageRepository::class)]
#[ORM\Table(name: 'parrainage')]
#[ORM\HasLifecycleCallbacks]
class Parrainage
{
    // ══════════════════════════════════════════════
    // 4 TYPES — calqués sur Image 1
    // ══════════════════════════════════════════════

    public const TYPE_ORPHELIN = 'orphelin';
    public const TYPE_FAMILLE  = 'famille';
    public const TYPE_IMAM     = 'imam';
    public const TYPE_HANDICAP = 'handicap';

    public const TYPES = [
        self::TYPE_ORPHELIN => ['label_fr' => 'Orphelin',          'label_ar' => 'قائمة طلبات الأيتام',        'icone' => 'bi-heart',          'couleur' => '#D4A017'],
        self::TYPE_FAMILLE  => ['label_fr' => 'Famille',           'label_ar' => 'قائمة طلبات الأسر',          'icone' => 'bi-people-fill',    'couleur' => '#2E7D32'],
        self::TYPE_IMAM     => ['label_fr' => 'Imam / Enseignant', 'label_ar' => 'قائمة الأئمه والمعلمين',    'icone' => 'bi-mortarboard',    'couleur' => '#0288D1'],
        self::TYPE_HANDICAP => ['label_fr' => 'Besoins spéciaux',  'label_ar' => 'قائمة ذوي الإحتياجات الخاصة','icone' => 'bi-accessibility', 'couleur' => '#7B1FA2'],
    ];

    // ══════════════════════════════════════════════
    // STATUTS — valeurs RÉELLES du système existant
    //
    // Observés dans le code PHP (screenshot) :
    //   if ($type == 'جديد')      → nouveau dossier
    //   elseif ($type == 'معتمدة') → approuvé
    //   elseif ($type == 'مكفول')  → pris en charge
    //   elseif ($type == 'ملغي')   → annulé
    //
    // Les constantes PHP ont des noms anglais pour la lisibilité
    // du code, mais les VALEURS stockées en base sont en arabe
    // pour compatibilité complète avec le système existant.
    // ══════════════════════════════════════════════

    public const STATUT_NOUVEAU  = 'جديد';    // Nouveau dossier soumis
    public const STATUT_APPROUVE = 'معتمدة';  // Approuvé par le directeur
    public const STATUT_ACTIF    = 'مكفول';   // Bénéficiaire pris en charge
    public const STATUT_ANNULE   = 'ملغي';    // Dossier annulé / rejeté

    public const STATUTS = [
        self::STATUT_NOUVEAU  => ['label_fr' => 'Nouveau',          'label_ar' => 'جديد',    'couleur' => '#9E9E9E', 'icone' => 'bi-file-earmark-plus'],
        self::STATUT_APPROUVE => ['label_fr' => 'Approuvé',         'label_ar' => 'معتمدة',  'couleur' => '#0288D1', 'icone' => 'bi-check-circle'],
        self::STATUT_ACTIF    => ['label_fr' => 'Pris en charge',   'label_ar' => 'مكفول',   'couleur' => '#2E7D32', 'icone' => 'bi-heart-fill'],
        self::STATUT_ANNULE   => ['label_fr' => 'Annulé',           'label_ar' => 'ملغي',    'couleur' => '#D32F2F', 'icone' => 'bi-x-circle'],
    ];

    // ══════════════════════════════════════════════
    // PÉRIODICITÉ
    // ══════════════════════════════════════════════

    public const PERIODICITE_MENSUEL     = 'mensuel';
    public const PERIODICITE_TRIMESTRIEL = 'trimestriel';
    public const PERIODICITE_SEMESTRIEL  = 'semestriel';
    public const PERIODICITE_ANNUEL      = 'annuel';
    public const PERIODICITE_UNIQUE      = 'unique';

    public const PERIODICITES = [
        self::PERIODICITE_MENSUEL     => ['label_fr' => 'Mensuel',          'label_ar' => 'شهري'],
        self::PERIODICITE_TRIMESTRIEL => ['label_fr' => 'Trimestriel',      'label_ar' => 'ربع سنوي'],
        self::PERIODICITE_SEMESTRIEL  => ['label_fr' => 'Semestriel',       'label_ar' => 'نصف سنوي'],
        self::PERIODICITE_ANNUEL      => ['label_fr' => 'Annuel',           'label_ar' => 'سنوي'],
        self::PERIODICITE_UNIQUE      => ['label_fr' => 'Versement unique', 'label_ar' => 'دفعة واحدة'],
    ];

    // ══════════════════════════════════════════════
    // IDENTIFICATION
    // ══════════════════════════════════════════════

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $numero = null;

    #[ORM\Column]
    private ?int $annee = null;

    // ══════════════════════════════════════════════
    // TYPE ET STATUT
    // ══════════════════════════════════════════════

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_ORPHELIN;

    /**
     * Stocke la valeur arabe : 'جديد', 'معتمدة', 'مكفول', 'ملغي'
     * Utiliser les constantes STATUT_* pour toute comparaison dans le code
     */
    #[ORM\Column(length: 30, options: ['collation' => 'utf8mb4_unicode_ci'])]
    private string $statut = self::STATUT_NOUVEAU;

    // ══════════════════════════════════════════════
    // FINANCEMENT
    // ══════════════════════════════════════════════

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $montantPeriodique = '0.00';

    #[ORM\Column(length: 20)]
    private string $periodicite = self::PERIODICITE_ANNUEL;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $duree = null;

    // ══════════════════════════════════════════════
    // DATES
    // ══════════════════════════════════════════════

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    // ══════════════════════════════════════════════
    // RELATIONS
    // ══════════════════════════════════════════════

    #[ORM\ManyToOne(targetEntity: Association::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Association $association = null;

    #[ORM\ManyToOne(targetEntity: Parrain::class, inversedBy: 'parrainages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Parrain $parrain = null;

    // Fiches spécifiques selon le type
    #[ORM\OneToOne(targetEntity: ParrainageOrphelin::class, mappedBy: 'parrainage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ParrainageOrphelin $ficheOrphelin = null;

    #[ORM\OneToOne(targetEntity: ParrainageFamille::class, mappedBy: 'parrainage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ParrainageFamille $ficheFamille = null;

    #[ORM\OneToOne(targetEntity: ParrainageImam::class, mappedBy: 'parrainage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ParrainageImam $ficheImam = null;

    #[ORM\OneToOne(targetEntity: ParrainageHandicap::class, mappedBy: 'parrainage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?ParrainageHandicap $ficheHandicap = null;

    #[ORM\OneToMany(targetEntity: ParrainagePaiement::class, mappedBy: 'parrainage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['datePaiement' => 'DESC'])]
    private Collection $paiements;

    #[ORM\OneToMany(targetEntity: RapportParrainage::class, mappedBy: 'parrainage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['annee' => 'DESC', 'semestre' => 'DESC'])]
    private Collection $rapports;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $validePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // ══════════════════════════════════════════════
    // CONSTRUCTEUR
    // ══════════════════════════════════════════════

    public function __construct()
    {
        $this->paiements = new ArrayCollection();
        $this->rapports  = new ArrayCollection();
        $this->annee     = (int) date('Y');
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    // ══════════════════════════════════════════════
    // GETTERS / SETTERS
    // ══════════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $n): static { $this->numero = $n; return $this; }

    public function getAnnee(): ?int { return $this->annee; }
    public function setAnnee(int $a): static { $this->annee = $a; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }

    public function getMontantPeriodique(): string { return $this->montantPeriodique; }
    public function setMontantPeriodique(string $m): static { $this->montantPeriodique = $m; return $this; }

    public function getPeriodicite(): string { return $this->periodicite; }
    public function setPeriodicite(string $p): static { $this->periodicite = $p; return $this; }

    public function getDuree(): ?string { return $this->duree; }
    public function setDuree(?string $d): static { $this->duree = $d; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(?\DateTimeInterface $d): static { $this->dateDebut = $d; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $d): static { $this->dateFin = $d; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function getAssociation(): ?Association { return $this->association; }
    public function setAssociation(?Association $a): static { $this->association = $a; return $this; }

    public function getParrain(): ?Parrain { return $this->parrain; }
    public function setParrain(?Parrain $p): static { $this->parrain = $p; return $this; }

    public function getFicheOrphelin(): ?ParrainageOrphelin { return $this->ficheOrphelin; }
    public function setFicheOrphelin(?ParrainageOrphelin $f): static { $this->ficheOrphelin = $f; return $this; }

    public function getFicheFamille(): ?ParrainageFamille { return $this->ficheFamille; }
    public function setFicheFamille(?ParrainageFamille $f): static { $this->ficheFamille = $f; return $this; }

    public function getFicheImam(): ?ParrainageImam { return $this->ficheImam; }
    public function setFicheImam(?ParrainageImam $f): static { $this->ficheImam = $f; return $this; }

    public function getFicheHandicap(): ?ParrainageHandicap { return $this->ficheHandicap; }
    public function setFicheHandicap(?ParrainageHandicap $f): static { $this->ficheHandicap = $f; return $this; }

    public function getCreePar(): ?User { return $this->creePar; }
    public function setCreePar(?User $u): static { $this->creePar = $u; return $this; }

    public function getValidePar(): ?User { return $this->validePar; }
    public function setValidePar(?User $u): static { $this->validePar = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Paiements ────────────────────────────────

    public function getPaiements(): Collection { return $this->paiements; }

    // public function addPaiement(ParrainagePaiement $p): static
    // {
    //     if (!$this->paiements->contains($p)) { $this->paiements->add($p); $p->setParrainage($this); }
    //     return $this;
    // }

    // public function removePaiement(ParrainagePaiement $p): static
    // {
    //     if ($this->paiements->removeElement($p) && $p->getParrainage() === $this) { $p->setParrainage(null); }
    //     return $this;
    // }

    // ── Rapports ─────────────────────────────────

    public function getRapports(): Collection { return $this->rapports; }

    public function addRapport(RapportParrainage $r): static
    {
        if (!$this->rapports->contains($r)) { $this->rapports->add($r); $r->setParrainage($this); }
        return $this;
    }

    public function removeRapport(RapportParrainage $r): static
    {
        if ($this->rapports->removeElement($r) && $r->getParrainage() === $this) { $r->setParrainage(null); }
        return $this;
    }

    // ══════════════════════════════════════════════
    // HELPERS — vérifications de statut
    // Utiliser ces méthodes plutôt que de comparer
    // directement les chaînes arabes dans le code
    // ══════════════════════════════════════════════

    public function isNouveau(): bool  { return $this->statut === self::STATUT_NOUVEAU; }
    public function isApprouve(): bool { return $this->statut === self::STATUT_APPROUVE; }
    public function isActif(): bool    { return $this->statut === self::STATUT_ACTIF; }
    public function isAnnule(): bool   { return $this->statut === self::STATUT_ANNULE; }

    // ── Transitions autorisées ───────────────────

    /** جديد → معتمدة */
    public function canApprouver(): bool { return $this->statut === self::STATUT_NOUVEAU; }

    /** معتمدة → مكفول */
    public function canActiver(): bool   { return $this->statut === self::STATUT_APPROUVE; }

    /** جديد ou معتمدة → ملغي */
    public function canAnnuler(): bool   { return in_array($this->statut, [self::STATUT_NOUVEAU, self::STATUT_APPROUVE]); }

    // ── Méthodes de transition ───────────────────

    /** Transition جديد → معتمدة */
    public function approuver(?User $validePar = null): static
    {
        if (!$this->canApprouver()) {
            throw new \LogicException('Transition impossible : statut actuel = ' . $this->statut);
        }
        $this->statut    = self::STATUT_APPROUVE;
        $this->validePar = $validePar;
        return $this;
    }

    /** Transition معتمدة → مكفول */
    public function activer(): static
    {
        if (!$this->canActiver()) {
            throw new \LogicException('Transition impossible : le parrainage doit être approuvé d\'abord.');
        }
        $this->statut    = self::STATUT_ACTIF;
        $this->dateDebut = $this->dateDebut ?? new \DateTime();
        return $this;
    }

    /** Transition → ملغي */
    public function annuler(): static
    {
        if (!$this->canAnnuler()) {
            throw new \LogicException('Transition impossible depuis le statut : ' . $this->statut);
        }
        $this->statut = self::STATUT_ANNULE;
        return $this;
    }

    // ══════════════════════════════════════════════
    // HELPERS — fiche active + calculs
    // ══════════════════════════════════════════════

    public function getNomBeneficiaire(): string
    {
        return match($this->type) {
            self::TYPE_ORPHELIN => $this->ficheOrphelin?->getNomComplet() ?? '—',
            self::TYPE_FAMILLE  => $this->ficheFamille?->getNomChef() ?? '—',
            self::TYPE_IMAM     => $this->ficheImam?->getNomComplet() ?? '—',
            self::TYPE_HANDICAP => $this->ficheHandicap?->getNomComplet() ?? '—',
            default             => '—',
        };
    }

    public function getFicheActive(): ParrainageOrphelin|ParrainageFamille|ParrainageImam|ParrainageHandicap|null
    {
        return match($this->type) {
            self::TYPE_ORPHELIN => $this->ficheOrphelin,
            self::TYPE_FAMILLE  => $this->ficheFamille,
            self::TYPE_IMAM     => $this->ficheImam,
            self::TYPE_HANDICAP => $this->ficheHandicap,
            default             => null,
        };
    }

    public function getMontantTotalPaye(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $p) { $total += (float) $p->getMontant(); }
        return $total;
    }

    public function getTauxPaiement(): float
    {
        $du = (float) $this->montantPeriodique;
        if ($du <= 0) return 0.0;
        return min(100.0, round($this->getMontantTotalPaye() / $du * 100, 2));
    }

    // ══════════════════════════════════════════════
    // HELPERS — labels Twig
    // ══════════════════════════════════════════════

    public function getTypeLabel(): string   { return self::TYPES[$this->type]['label_fr'] ?? 'Inconnu'; }
    public function getTypeLabelAr(): string { return self::TYPES[$this->type]['label_ar'] ?? 'غير معروف'; }
    public function getTypeIcone(): string   { return self::TYPES[$this->type]['icone'] ?? 'bi-folder'; }
    public function getTypeCouleur(): string { return self::TYPES[$this->type]['couleur'] ?? '#9E9E9E'; }

    public function getStatutLabel(): string   { return self::STATUTS[$this->statut]['label_fr'] ?? $this->statut; }
    public function getStatutLabelAr(): string { return self::STATUTS[$this->statut]['label_ar'] ?? $this->statut; }
    public function getStatutCouleur(): string { return self::STATUTS[$this->statut]['couleur'] ?? '#9E9E9E'; }
    public function getStatutIcone(): string   { return self::STATUTS[$this->statut]['icone'] ?? 'bi-circle'; }

    public function getPeriodiciteLabel(): string   { return self::PERIODICITES[$this->periodicite]['label_fr'] ?? 'Inconnu'; }
    public function getPeriodiciteLabelAr(): string { return self::PERIODICITES[$this->periodicite]['label_ar'] ?? 'غير معروف'; }

    public static function getTypesChoices(): array
    {
        $c = [];
        foreach (self::TYPES as $k => $v) { $c[$v['label_fr']] = $k; }
        return $c;
    }

    public static function getStatutsChoices(): array
    {
        $c = [];
        foreach (self::STATUTS as $k => $v) { $c[$v['label_fr']] = $k; }
        return $c;
    }

    public function __toString(): string
    {
        return $this->numero . ' — ' . $this->getNomBeneficiaire();
    }
}
