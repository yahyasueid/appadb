<?php
// src/Entity/Projet.php

namespace App\Entity;

use App\Repository\ProjetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
#[ORM\Table(name: 'projet')]
#[ORM\HasLifecycleCallbacks]
class Projet
{
    public const TYPE_CONSTRUCTION = 'construction';
    public const TYPE_EDUCATION    = 'education';
    public const TYPE_SANTE        = 'sante';
    public const TYPE_EAU          = 'eau';
    public const TYPE_AGRICULTURE  = 'agriculture';
    public const TYPE_AUTRE        = 'autre';

    public const TYPES = [
        self::TYPE_CONSTRUCTION => ['label_fr' => 'Construction', 'label_ar' => 'الآبار',     'icone' => 'bi-building',    'couleur' => '#2E7D32'],
        self::TYPE_EDUCATION    => ['label_fr' => 'Éducation',    'label_ar' => 'المساجد',    'icone' => 'bi-mortarboard', 'couleur' => '#0288D1'],
        self::TYPE_SANTE        => ['label_fr' => 'Santé',        'label_ar' => 'جفيرالخير ',      'icone' => 'bi-heart-pulse', 'couleur' => '#D32F2F'],
        self::TYPE_EAU          => ['label_fr' => 'Eau',          'label_ar' => 'آبارارتوازية',     'icone' => 'bi-droplet',     'couleur' => '#03A9F4'],
        self::TYPE_AGRICULTURE  => ['label_fr' => 'Agriculture',  'label_ar' => 'بيوت الفقراء ',   'icone' => 'bi-tree',        'couleur' => '#4CAF50'],
        self::TYPE_AUTRE        => ['label_fr' => 'Autre',        'label_ar' => 'الفصول القرآنية',     'icone' => 'bi-folder',      'couleur' => '#9E9E9E'],
    ];

    public const STATUT_BROUILLON  = 'brouillon';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDE     = 'valide';
    public const STATUT_EN_COURS   = 'en_cours';
    public const STATUT_TERMINE    = 'termine';
    public const STATUT_REJETE     = 'rejete';

    public const STATUTS = [
        self::STATUT_BROUILLON  => ['label_fr' => 'Brouillon',   'label_ar' => 'مسودة',        'couleur' => '#9E9E9E', 'icone' => 'bi-pencil'],
        self::STATUT_EN_ATTENTE => ['label_fr' => 'En attente',  'label_ar' => 'في الانتظار',  'couleur' => '#D4A017', 'icone' => 'bi-hourglass-split'],
        self::STATUT_VALIDE     => ['label_fr' => 'Validé',      'label_ar' => 'تمت المصادقة', 'couleur' => '#0288D1', 'icone' => 'bi-check-circle'],
        self::STATUT_EN_COURS   => ['label_fr' => 'En cours',    'label_ar' => 'قيد التنفيذ',  'couleur' => '#2E7D32', 'icone' => 'bi-gear'],
        self::STATUT_TERMINE    => ['label_fr' => 'Terminé',     'label_ar' => 'منتهي',        'couleur' => '#14532D', 'icone' => 'bi-flag'],
        self::STATUT_REJETE     => ['label_fr' => 'Rejeté',      'label_ar' => 'مرفوض',        'couleur' => '#D32F2F', 'icone' => 'bi-x-circle'],
    ];

    // ── Identification ──────────────────────────────────

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $numero = null;

    #[ORM\Column]
    private ?int $annee = null;

    // ── Infos générales ─────────────────────────────────

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du projet est obligatoire')]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    private string $pays = 'Mauritanie';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $lieu = null;

    // ── Financement ─────────────────────────────────────

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $donateur = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $coutTotal = '0.00';

    // ── Dates ───────────────────────────────────────────

    #[ORM\Column(length: 100)]
    private ?string $duree = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateContrat = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    // ── Progression & statut ────────────────────────────

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 0, max: 100)]
    private int $progression = 0;

    #[ORM\Column(length: 30)]
    private string $statut = self::STATUT_BROUILLON;

    // ── Relations utilisateurs ──────────────────────────

    #[ORM\ManyToOne(targetEntity: Association::class, inversedBy: 'projets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Association $association = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $creePar = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $validePar = null;

    // ── Photos ──────────────────────────────────────────

    #[ORM\OneToMany(targetEntity: ProjetPhoto::class, mappedBy: 'projet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $photos;

    // ── Fichiers PDF ────────────────────────────────────

    #[ORM\OneToMany(targetEntity: ProjetFichier::class, mappedBy: 'projet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $fichiers;

    // ── Vidéos YouTube ──────────────────────────────────

    #[ORM\OneToMany(targetEntity: ProjetVideo::class, mappedBy: 'projet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $videos;

    // ── Paiements (direct — pas de tranche) ─────────────

    #[ORM\OneToMany(targetEntity: ProjetPaiement::class, mappedBy: 'projet', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['datePaiement' => 'DESC'])]
    private Collection $paiements;

    // ── Métadonnées ─────────────────────────────────────

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // ═══════════════════════════════════════════
    // CONSTRUCTEUR
    // ═══════════════════════════════════════════

    public function __construct()
    {
        $this->photos    = new ArrayCollection();
        $this->fichiers  = new ArrayCollection();
        $this->videos    = new ArrayCollection();
        $this->paiements = new ArrayCollection();
        $this->annee     = (int) date('Y');
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    // ═══════════════════════════════════════════
    // GETTERS / SETTERS
    // ═══════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $n): static { $this->numero = $n; return $this; }

    public function getAnnee(): ?int { return $this->annee; }
    public function setAnnee(int $a): static { $this->annee = $a; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $n): static { $this->nom = $n; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): static { $this->description = $d; return $this; }

    public function getPays(): string { return $this->pays; }
    public function setPays(string $p): static { $this->pays = $p; return $this; }

    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(string $l): static { $this->lieu = $l; return $this; }

    public function getDonateur(): ?string { return $this->donateur; }
    public function setDonateur(string $d): static { $this->donateur = $d; return $this; }

    public function getCoutTotal(): string { return $this->coutTotal; }
    public function setCoutTotal(string $c): static { $this->coutTotal = $c; return $this; }

    public function getDuree(): ?string { return $this->duree; }
    public function setDuree(string $d): static { $this->duree = $d; return $this; }

    public function getDateContrat(): ?\DateTimeInterface { return $this->dateContrat; }
    public function setDateContrat(\DateTimeInterface $d): static { $this->dateContrat = $d; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(?\DateTimeInterface $d): static { $this->dateFin = $d; return $this; }

    public function getProgression(): int { return $this->progression; }
    public function setProgression(int $p): static { $this->progression = $p; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }

    public function getAssociation(): ?Association { return $this->association; }
    public function setAssociation(?Association $a): static { $this->association = $a; return $this; }

    public function getCreePar(): ?User { return $this->creePar; }
    public function setCreePar(?User $u): static { $this->creePar = $u; return $this; }

    public function getValidePar(): ?User { return $this->validePar; }
    public function setValidePar(?User $u): static { $this->validePar = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ── Photos ──────────────────────────────────────────

    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(ProjetPhoto $p): static
    {
        if (!$this->photos->contains($p)) { $this->photos->add($p); $p->setProjet($this); }
        return $this;
    }

    public function removePhoto(ProjetPhoto $p): static
    {
        if ($this->photos->removeElement($p) && $p->getProjet() === $this) { $p->setProjet(null); }
        return $this;
    }

    public function canAddPhoto(): bool { return $this->photos->count() < 20; }

    // ── Fichiers ────────────────────────────────────────

    public function getFichiers(): Collection { return $this->fichiers; }

    public function addFichier(ProjetFichier $f): static
    {
        if (!$this->fichiers->contains($f)) { $this->fichiers->add($f); $f->setProjet($this); }
        return $this;
    }

    public function removeFichier(ProjetFichier $f): static
    {
        if ($this->fichiers->removeElement($f) && $f->getProjet() === $this) { $f->setProjet(null); }
        return $this;
    }

    // ── Vidéos ──────────────────────────────────────────

    public function getVideos(): Collection { return $this->videos; }

    public function addVideo(ProjetVideo $v): static
    {
        if (!$this->videos->contains($v)) { $this->videos->add($v); $v->setProjet($this); }
        return $this;
    }

    public function removeVideo(ProjetVideo $v): static
    {
        if ($this->videos->removeElement($v) && $v->getProjet() === $this) { $v->setProjet(null); }
        return $this;
    }

    // ── Paiements ───────────────────────────────────────

    public function getPaiements(): Collection { return $this->paiements; }

    public function addPaiement(ProjetPaiement $p): static
    {
        if (!$this->paiements->contains($p)) { $this->paiements->add($p); $p->setProjet($this); }
        return $this;
    }

    public function removePaiement(ProjetPaiement $p): static
    {
        if ($this->paiements->removeElement($p) && $p->getProjet() === $this) { $p->setProjet(null); }
        return $this;
    }

    // ═══════════════════════════════════════════
    // HELPERS — Calculs financiers
    // ═══════════════════════════════════════════

    /**
     * Somme de tous les paiements reçus
     */
    public function getMontantTotalPaye(): float
    {
        $total = 0.0;
        foreach ($this->paiements as $p) {
            $total += (float) $p->getMontant();
        }
        return $total;
    }

    /**
     * Montant restant à payer
     */
    public function getMontantRestant(): float
    {
        return max(0.0, (float) $this->coutTotal - $this->getMontantTotalPaye());
    }

    /**
     * Taux de réalisation financière en %
     */
    public function getTauxPaiement(): float
    {
        $cout = (float) $this->coutTotal;
        if ($cout <= 0) return 0.0;
        return min(100.0, round($this->getMontantTotalPaye() / $cout * 100, 2));
    }

    // ═══════════════════════════════════════════
    // HELPERS — Labels Twig
    // ═══════════════════════════════════════════

    public function getTypeLabel(): string { return self::TYPES[$this->type]['label_fr'] ?? 'Inconnu'; }
    public function getTypeLabelAr(): string { return self::TYPES[$this->type]['label_ar'] ?? 'غير معروف'; }
    public function getTypeIcone(): string { return self::TYPES[$this->type]['icone'] ?? 'bi-folder'; }
    public function getTypeCouleur(): string { return self::TYPES[$this->type]['couleur'] ?? '#9E9E9E'; }

    public function getStatutLabel(): string { return self::STATUTS[$this->statut]['label_fr'] ?? 'Inconnu'; }
    public function getStatutLabelAr(): string { return self::STATUTS[$this->statut]['label_ar'] ?? 'غير معروف'; }
    public function getStatutCouleur(): string { return self::STATUTS[$this->statut]['couleur'] ?? '#9E9E9E'; }
    public function getStatutIcone(): string { return self::STATUTS[$this->statut]['icone'] ?? 'bi-circle'; }

    public function getCoverPhoto(): ?ProjetPhoto { return $this->photos->first() ?: null; }

    public function countRapports(): int
    {
        return $this->fichiers->filter(fn($f) => $f->getCategorie() === ProjetFichier::CAT_RAPPORT)->count();
    }

    public function countTransferts(): int
    {
        return $this->fichiers->filter(fn($f) => $f->getCategorie() === ProjetFichier::CAT_TRANSFERT)->count();
    }

    public static function getTypesKeys(): array { return array_keys(self::TYPES); }
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

    public function __toString(): string { return $this->numero . ' — ' . $this->nom; }
}