<?php
// src/Entity/RapportParrainage.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ RAPPORT PARRAINAGE                                   ║
// ║                                                              ║
// ║  Représente un rapport périodique envoyé au parrain.         ║
// ║  Chaque parrainage génère 2 rapports par an :                ║
// ║   - 1er semestre  (S1)                                       ║
// ║   - 2ème semestre (S2)                                       ║
// ║                                                              ║
// ║  Le rapport contient :                                       ║
// ║   - Une mise à jour de la situation du bénéficiaire          ║
// ║   - Des photos jointes (stockées en dossier)                 ║
// ║   - Un document PDF (bilan complet)                          ║
// ║   - Statut d'envoi au parrain                                ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\RapportParrainageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RapportParrainageRepository::class)]
#[ORM\Table(name: 'rapport_parrainage')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'unique_rapport', columns: ['parrainage_id', 'annee', 'semestre'])]
class RapportParrainage
{
    // ══════════════════════════════════════════════
    // SEMESTRES
    // ══════════════════════════════════════════════

    public const SEMESTRE_1 = 1;  // 1er semestre (S1) — janvier à juin
    public const SEMESTRE_2 = 2;  // 2ème semestre (S2) — juillet à décembre

    public const SEMESTRES = [
        self::SEMESTRE_1 => ['label_fr' => '1er semestre', 'label_ar' => 'الفصل الأول'],
        self::SEMESTRE_2 => ['label_fr' => '2ème semestre','label_ar' => 'الفصل الثاني'],
    ];

    // ══════════════════════════════════════════════
    // STATUTS DU RAPPORT
    // ══════════════════════════════════════════════

    public const STATUT_BROUILLON = 'brouillon';  // En cours de rédaction
    public const STATUT_PRET      = 'pret';       // Prêt à envoyer
    public const STATUT_ENVOYE    = 'envoye';     // Envoyé au parrain

    public const STATUTS = [
        self::STATUT_BROUILLON => ['label_fr' => 'Brouillon', 'label_ar' => 'مسودة',    'couleur' => '#9E9E9E', 'icone' => 'bi-pencil'],
        self::STATUT_PRET      => ['label_fr' => 'Prêt',      'label_ar' => 'جاهز',     'couleur' => '#D4A017', 'icone' => 'bi-check-circle'],
        self::STATUT_ENVOYE    => ['label_fr' => 'Envoyé',    'label_ar' => 'تم الإرسال','couleur' => '#2E7D32', 'icone' => 'bi-envelope-check'],
    ];

    // ══════════════════════════════════════════════
    // IDENTIFICATION
    // ══════════════════════════════════════════════

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Le parrainage auquel ce rapport est rattaché
     * Contrainte unique : 1 rapport par semestre par parrainage
     */
    #[ORM\ManyToOne(targetEntity: Parrainage::class, inversedBy: 'rapports')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parrainage $parrainage = null;

    // ══════════════════════════════════════════════
    // PÉRIODE DU RAPPORT
    // ══════════════════════════════════════════════

    /**
     * Année du rapport
     * Ex: 2024
     */
    #[ORM\Column]
    #[Assert\Range(min: 2000, max: 2099)]
    private ?int $annee = null;

    /**
     * Semestre : 1 ou 2
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Choice(choices: [1, 2])]
    private int $semestre = self::SEMESTRE_1;

    // ══════════════════════════════════════════════
    // CONTENU DU RAPPORT
    // ══════════════════════════════════════════════

    /**
     * Titre du rapport
     * Ex: "Rapport S1 2024 — Ali Mohamed"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    /**
     * Mise à jour de la situation générale du bénéficiaire
     * Ex: état de santé, scolarité, progrès observés
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $situationGenerale = null;

    /**
     * Résultats scolaires (pour les orphelins / enseignants)
     * Ex: "Passage en classe supérieure, mention Bien"
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultatsScolarite = null;

    /**
     * Situation sanitaire et médicale
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $situationSante = null;

    /**
     * Message de remerciement au parrain
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $messageParrain = null;

    /**
     * Document PDF du rapport complet
     * Stocké dans public/uploads/rapports/parrainages/
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentOriginalName = null;

    // ══════════════════════════════════════════════
    // STATUT ET ENVOI
    // ══════════════════════════════════════════════

    /**
     * Statut du rapport : brouillon → prêt → envoyé
     */
    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_BROUILLON;

    /**
     * Date d'envoi au parrain (renseignée quand statut = envoyé)
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateEnvoi = null;

    /**
     * Moyen d'envoi au parrain
     * Ex: "Email", "Courrier", "WhatsApp"
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $moyenEnvoi = null;

    // ══════════════════════════════════════════════
    // TRAÇABILITÉ
    // ══════════════════════════════════════════════

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // ══════════════════════════════════════════════
    // CONSTRUCTEUR
    // ══════════════════════════════════════════════

    public function __construct()
    {
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

    public function getParrainage(): ?Parrainage { return $this->parrainage; }
    public function setParrainage(?Parrainage $p): static { $this->parrainage = $p; return $this; }

    public function getAnnee(): ?int { return $this->annee; }
    public function setAnnee(int $a): static { $this->annee = $a; return $this; }

    public function getSemestre(): int { return $this->semestre; }
    public function setSemestre(int $s): static { $this->semestre = $s; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $t): static { $this->titre = $t; return $this; }

    public function getSituationGenerale(): ?string { return $this->situationGenerale; }
    public function setSituationGenerale(?string $s): static { $this->situationGenerale = $s; return $this; }

    public function getResultatsScolarite(): ?string { return $this->resultatsScolarite; }
    public function setResultatsScolarite(?string $r): static { $this->resultatsScolarite = $r; return $this; }

    public function getSituationSante(): ?string { return $this->situationSante; }
    public function setSituationSante(?string $s): static { $this->situationSante = $s; return $this; }

    public function getMessageParrain(): ?string { return $this->messageParrain; }
    public function setMessageParrain(?string $m): static { $this->messageParrain = $m; return $this; }

    public function getDocumentFilename(): ?string { return $this->documentFilename; }
    public function setDocumentFilename(?string $f): static { $this->documentFilename = $f; return $this; }

    public function getDocumentOriginalName(): ?string { return $this->documentOriginalName; }
    public function setDocumentOriginalName(?string $n): static { $this->documentOriginalName = $n; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; return $this; }

    public function getDateEnvoi(): ?\DateTimeInterface { return $this->dateEnvoi; }
    public function setDateEnvoi(?\DateTimeInterface $d): static { $this->dateEnvoi = $d; return $this; }

    public function getMoyenEnvoi(): ?string { return $this->moyenEnvoi; }
    public function setMoyenEnvoi(?string $m): static { $this->moyenEnvoi = $m; return $this; }

    public function getCreePar(): ?User { return $this->creePar; }
    public function setCreePar(?User $u): static { $this->creePar = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    public function getDocumentPath(): ?string
    {
        return $this->documentFilename
            ? 'uploads/rapports/parrainages/' . $this->documentFilename
            : null;
    }

    public function hasDocument(): bool { return $this->documentFilename !== null; }

    public function isEnvoye(): bool { return $this->statut === self::STATUT_ENVOYE; }

    public function getSemestreLabel(): string   { return self::SEMESTRES[$this->semestre]['label_fr'] ?? ''; }
    public function getSemestreLabelAr(): string { return self::SEMESTRES[$this->semestre]['label_ar'] ?? ''; }

    public function getStatutLabel(): string   { return self::STATUTS[$this->statut]['label_fr'] ?? 'Inconnu'; }
    public function getStatutLabelAr(): string { return self::STATUTS[$this->statut]['label_ar'] ?? 'غير معروف'; }
    public function getStatutCouleur(): string { return self::STATUTS[$this->statut]['couleur'] ?? '#9E9E9E'; }
    public function getStatutIcone(): string   { return self::STATUTS[$this->statut]['icone'] ?? 'bi-circle'; }

    /**
     * Label complet : "Rapport S1 2024"
     */
    public function getLabel(): string
    {
        return sprintf('Rapport S%d %d', $this->semestre, $this->annee);
    }

    public function __toString(): string { return $this->getLabel(); }
}
