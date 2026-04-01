<?php
// src/Entity/ParrainagePaiement.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAINAGE PAIEMENT                                  ║
// ║                                                              ║
// ║  Représente un versement effectué par le parrain (kafil).    ║
// ║                                                              ║
// ║  Calqué sur ProjetPaiement.php :                             ║
// ║  - Mêmes 5 modes (VIREMENT, CHEQUE, ESPECES, MOBILE, AUTRE) ║
// ║  - Mêmes helpers Twig (getModeLabel, getModeIcone…)          ║
// ║  - Même upload justificatif                                  ║
// ║                                                              ║
// ║  Champ supplémentaire vs ProjetPaiement :                    ║
// ║  - periodeConcernee : "Janvier 2025" / "S2 2024"             ║
// ║                                                              ║
// ║  Table       : parrainage_paiement                           ║
// ║  Upload      : public/uploads/parrainages/paiements/         ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainagePaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainagePaiementRepository::class)]
#[ORM\Table(name: 'parrainage_paiement')]
#[ORM\HasLifecycleCallbacks]
class ParrainagePaiement
{
    // ══════════════════════════════════════════════
    // MODES DE PAIEMENT
    // Identiques à ProjetPaiement::MODES pour cohérence
    // ══════════════════════════════════════════════

    public const MODE_VIREMENT = 'virement';
    public const MODE_CHEQUE   = 'cheque';
    public const MODE_ESPECES  = 'especes';
    public const MODE_MOBILE   = 'mobile';
    public const MODE_AUTRE    = 'autre';

    public const MODES = [
        self::MODE_VIREMENT => [
            'label_fr' => 'Virement bancaire',
            'label_ar' => 'تحويل مصرفي',
            'icone'    => 'bi-bank',
            'couleur'  => '#0288D1',
        ],
        self::MODE_CHEQUE   => [
            'label_fr' => 'Chèque',
            'label_ar' => 'شيك',
            'icone'    => 'bi-file-earmark-text',
            'couleur'  => '#2E7D32',
        ],
        self::MODE_ESPECES  => [
            'label_fr' => 'Espèces',
            'label_ar' => 'نقداً',
            'icone'    => 'bi-cash',
            'couleur'  => '#D4A017',
        ],
        self::MODE_MOBILE   => [
            'label_fr' => 'Paiement mobile',
            'label_ar' => 'دفع إلكتروني',
            'icone'    => 'bi-phone',
            'couleur'  => '#7B1FA2',
        ],
        self::MODE_AUTRE    => [
            'label_fr' => 'Autre',
            'label_ar' => 'أخرى',
            'icone'    => 'bi-three-dots',
            'couleur'  => '#9E9E9E',
        ],
    ];

    // ══════════════════════════════════════════════
    // PROPRIÉTÉS
    // ══════════════════════════════════════════════

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Parrainage auquel ce versement est rattaché.
     * CASCADE DELETE : si le parrainage est supprimé,
     * tous ses versements le sont aussi.
     */
    #[ORM\ManyToOne(targetEntity: Parrainage::class, inversedBy: 'paiements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parrainage $parrainage = null;

    /**
     * Montant versé en MRU
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    #[Assert\NotBlank(message: 'Le montant est obligatoire')]
    #[Assert\Positive(message: 'Le montant doit être positif')]
    private string $montant = '0.00';

    /**
     * Date effective du versement
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date est obligatoire')]
    private ?\DateTimeInterface $datePaiement = null;

    /**
     * Période que ce versement couvre.
     * Spécifique aux parrainages (absent de ProjetPaiement).
     * Ex : "Janvier 2025", "S1 2024", "Année scolaire 2024-2025"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $periodeConcernee = null;

    /**
     * Mode de règlement
     */
    #[ORM\Column(length: 30, options: ['collation' => 'utf8mb4_unicode_ci'])]
    private string $mode = self::MODE_VIREMENT;

    /**
     * Référence bancaire, numéro de chèque ou de virement
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    /**
     * Notes libres sur ce versement
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * Fichier justificatif (reçu, relevé bancaire, etc.)
     * Nom du fichier physique → public/uploads/parrainages/paiements/
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justificatifFilename = null;

    /** Nom original du fichier (pour affichage) */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $justificatifOriginalName = null;

    /**
     * Utilisateur qui a saisi ce versement.
     * SET NULL si l'utilisateur est supprimé.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $saisirPar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    // ══════════════════════════════════════════════
    // CONSTRUCTEUR
    // ══════════════════════════════════════════════

    public function __construct()
    {
        $this->datePaiement = new \DateTime();
        $this->createdAt    = new \DateTime();
    }

    // ══════════════════════════════════════════════
    // GETTERS / SETTERS
    // ══════════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getParrainage(): ?Parrainage { return $this->parrainage; }
    public function setParrainage(?Parrainage $p): static { $this->parrainage = $p; return $this; }

    public function getMontant(): string { return $this->montant; }
    public function setMontant(string $m): static { $this->montant = $m; return $this; }

    public function getDatePaiement(): ?\DateTimeInterface { return $this->datePaiement; }
    public function setDatePaiement(\DateTimeInterface $d): static { $this->datePaiement = $d; return $this; }

    public function getPeriodeConcernee(): ?string { return $this->periodeConcernee; }
    public function setPeriodeConcernee(?string $p): static { $this->periodeConcernee = $p; return $this; }

    public function getMode(): string { return $this->mode; }
    public function setMode(string $m): static { $this->mode = $m; return $this; }

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

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    /**
     * Chemin du justificatif pour Twig.
     * Usage : {{ asset(paiement.justificatifPath) }}
     */
    public function getJustificatifPath(): ?string
    {
        return $this->justificatifFilename
            ? 'uploads/parrainages/paiements/' . $this->justificatifFilename
            : null;
    }

    public function hasJustificatif(): bool
    {
        return $this->justificatifFilename !== null && $this->justificatifFilename !== '';
    }

    /**
     * Label du mode selon la locale.
     * Compatible avec les templates Twig existants.
     *
     * Usage : {{ paiement.getModeLabel(app.request.locale) }}
     */
    public function getModeLabel(string $locale = 'fr'): string
    {
        return self::MODES[$this->mode]['label_' . $locale]
            ?? self::MODES[$this->mode]['label_fr']
            ?? 'Inconnu';
    }

    public function getModeLabelFr(): string { return self::MODES[$this->mode]['label_fr'] ?? 'Inconnu'; }
    public function getModeLabelAr(): string { return self::MODES[$this->mode]['label_ar'] ?? 'غير معروف'; }
    public function getModeIcone(): string   { return self::MODES[$this->mode]['icone']    ?? 'bi-cash'; }
    public function getModeCouleur(): string { return self::MODES[$this->mode]['couleur']  ?? '#9E9E9E'; }

    /**
     * Montant formaté avec séparateur de milliers.
     * Usage : {{ paiement.montantFormate }} MRU
     */
    public function getMontantFormate(): string
    {
        return number_format((float) $this->montant, 2, '.', ' ');
    }

    /**
     * Choices pour le FormType Symfony.
     * Usage : ->add('mode', ChoiceType::class, ['choices' => ParrainagePaiement::getModesChoices()])
     */
    public static function getModesChoices(): array
    {
        $c = [];
        foreach (self::MODES as $k => $v) {
            $c[$v['label_fr']] = $k;
        }
        return $c;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s MRU — %s',
            $this->getMontantFormate(),
            $this->datePaiement?->format('d/m/Y') ?? '?'
        );
    }
}