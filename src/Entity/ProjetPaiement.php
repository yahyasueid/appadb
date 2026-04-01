<?php
// src/Entity/ProjetPaiement.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PROJET PAIEMENT (version simplifiée)                 ║
// ║  Lié directement au Projet — pas de tranche                  ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ProjetPaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetPaiementRepository::class)]
#[ORM\Table(name: 'projet_paiement')]
#[ORM\HasLifecycleCallbacks]
class ProjetPaiement
{
    public const MODE_VIREMENT = 'virement';
    public const MODE_CHEQUE   = 'cheque';
    public const MODE_ESPECES  = 'especes';
    public const MODE_MOBILE   = 'mobile';
    public const MODE_AUTRE    = 'autre';

    public const MODES = [
        self::MODE_VIREMENT => ['label_fr' => 'Virement bancaire', 'label_ar' => 'تحويل مصرفي', 'icone' => 'bi-bank',              'couleur' => '#0288D1'],
        self::MODE_CHEQUE   => ['label_fr' => 'Chèque',            'label_ar' => 'شيك',          'icone' => 'bi-file-earmark-text', 'couleur' => '#2E7D32'],
        self::MODE_ESPECES  => ['label_fr' => 'Espèces',           'label_ar' => 'نقداً',         'icone' => 'bi-cash',              'couleur' => '#D4A017'],
        self::MODE_MOBILE   => ['label_fr' => 'Paiement mobile',   'label_ar' => 'دفع إلكتروني', 'icone' => 'bi-phone',             'couleur' => '#7B1FA2'],
        self::MODE_AUTRE    => ['label_fr' => 'Autre',             'label_ar' => 'أخرى',          'icone' => 'bi-three-dots',        'couleur' => '#9E9E9E'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Lié directement au projet */
    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Projet $projet = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    private string $montant = '0.00';

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de paiement est obligatoire')]
    private ?\DateTimeInterface $datePaiement = null;

    #[ORM\Column(length: 30)]
    private string $mode = self::MODE_VIREMENT;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** Justificatif PDF → public/uploads/projets/paiements/ */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justificatifFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justificatifOriginalName = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $saisirPar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->datePaiement = new \DateTime();
        $this->createdAt    = new \DateTime();
    }

    // ── Getters / Setters ──────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getProjet(): ?Projet { return $this->projet; }
    public function setProjet(?Projet $projet): static { $this->projet = $projet; return $this; }

    public function getMontant(): string { return $this->montant; }
    public function setMontant(string $montant): static { $this->montant = $montant; return $this; }

    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(\DateTimeInterface $d): static { $this->datePaiement = $d; return $this; }

    public function getMode(): string { return $this->mode; }
    public function setMode(string $mode): static { $this->mode = $mode; return $this; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $r): static { $this->reference = $r; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $n): static { $this->notes = $n; return $this; }

    public function getJustificatifFilename(): ?string { return $this->justificatifFilename; }
    public function setJustificatifFilename(?string $f): static { $this->justificatifFilename = $f; return $this; }

    public function getJustificatifOriginalName(): ?string { return $this->justificatifOriginalName; }
    public function setJustificatifOriginalName(?string $n): static { $this->justificatifOriginalName = $n; return $this; }

    public function getSaisirPar(): ?User { return $this->saisirPar; }
    public function setSaisirPar(?User $u): static { $this->saisirPar = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    // ── Helpers ────────────────────────────────────

    public function getJustificatifPath(): ?string
    {
        return $this->justificatifFilename
            ? 'uploads/projets/paiements/' . $this->justificatifFilename
            : null;
    }

    public function hasJustificatif(): bool { return $this->justificatifFilename !== null; }

    public function getModeLabel(string $locale = 'fr'): string
    {
        return self::MODES[$this->mode]['label_' . $locale]
            ?? self::MODES[$this->mode]['label_fr']
            ?? 'Inconnu';
    }

    public function getModeIcone(): string { return self::MODES[$this->mode]['icone'] ?? 'bi-cash'; }
    public function getModeCouleur(): string { return self::MODES[$this->mode]['couleur'] ?? '#9E9E9E'; }

    public static function getModesKeys(): array { return array_keys(self::MODES); }
    public static function getModesChoices(): array
    {
        $c = [];
        foreach (self::MODES as $k => $v) { $c[$v['label_fr']] = $k; }
        return $c;
    }

    public function __toString(): string
    {
        return sprintf('%s MRU — %s', $this->montant, $this->datePaiement?->format('d/m/Y') ?? '?');
    }
}