<?php
// src/Entity/ParrainageFamille.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAINAGE FAMILLE (استمارة أسرة)                   ║
// ║                                                              ║
// ║  Fiche spécifique au type "Famille" — قائمة طلبات الأسر     ║
// ║  Calquée sur le formulaire de l'Image 3 :                   ║
// ║   - التواريخ    → dates (naissance, enregistrement)          ║
// ║   - بيانات الأسرة → chef de famille, niveau éducatif        ║
// ║   - السكن       → logement                                  ║
// ║   - بيانات الإتصال → contacts                               ║
// ║   - الدخل والعمل → revenus et emploi                        ║
// ║   - الحالة الصحية → santé                                   ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainageFamilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainageFamilleRepository::class)]
#[ORM\Table(name: 'parrainage_famille')]
#[ORM\HasLifecycleCallbacks]
class ParrainageFamille
{
    // ══════════════════════════════════════════════
    // CONSTANTES — Type de logement (نوع السكن)
    // ══════════════════════════════════════════════

    public const LOGEMENT_PROPRIETE = 'propriete';
    public const LOGEMENT_LOYER     = 'loyer';
    public const LOGEMENT_FAMILLE   = 'famille';  // Hébergé famille
    public const LOGEMENT_AUTRE     = 'autre';

    public const TYPES_LOGEMENT = [
        self::LOGEMENT_PROPRIETE => ['label_fr' => 'Propriété',          'label_ar' => 'مِلك'],
        self::LOGEMENT_LOYER     => ['label_fr' => 'Location',           'label_ar' => 'إيجار'],
        self::LOGEMENT_FAMILLE   => ['label_fr' => 'Hébergé par famille','label_ar' => 'سكن عائلي'],
        self::LOGEMENT_AUTRE     => ['label_fr' => 'Autre',              'label_ar' => 'أخرى'],
    ];

    // ══════════════════════════════════════════════
    // CONSTANTES — État du logement (حالة السكن)
    // ══════════════════════════════════════════════

    public const ETAT_BON         = 'bon';
    public const ETAT_MOYEN       = 'moyen';
    public const ETAT_MAUVAIS     = 'mauvais';

    public const ETATS_LOGEMENT = [
        self::ETAT_BON     => ['label_fr' => 'Bon',    'label_ar' => 'جيد'],
        self::ETAT_MOYEN   => ['label_fr' => 'Moyen',  'label_ar' => 'متوسط'],
        self::ETAT_MAUVAIS => ['label_fr' => 'Mauvais','label_ar' => 'سيئ'],
    ];

    // ══════════════════════════════════════════════
    // IDENTIFICATION
    // ══════════════════════════════════════════════

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Lien OneToOne vers le parrainage parent
     */
    #[ORM\OneToOne(targetEntity: Parrainage::class, inversedBy: 'ficheFamille')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parrainage $parrainage = null;

    // ══════════════════════════════════════════════
    // DATES — التواريخ (Image 3)
    // ══════════════════════════════════════════════

    /**
     * Date de naissance du chef de famille — تاريخ الميلاد
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    /**
     * Genre du chef de famille — الجنس
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $genre = null;

    // ══════════════════════════════════════════════
    // DONNÉES FAMILLE — بيانات الأسرة (Image 3)
    // ══════════════════════════════════════════════

    /**
     * Nom complet du chef de famille / soutien — اسم رب الأسرة أو المعيل
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du chef de famille est obligatoire')]
    private ?string $nomChef = null;

    /**
     * Numéro national du chef — الرقم الوطني
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cinChef = null;

    /**
     * Niveau éducatif du chef — المستوى التعليمي
     * Ex: "Primaire", "Secondaire", "Analphabète"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveauEducatif = null;

    // ══════════════════════════════════════════════
    // LOGEMENT — السكن (Image 3)
    // ══════════════════════════════════════════════

    /**
     * Adresse du logement — عنوان السكن
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresseSкn = null;

    /**
     * Type de logement — نوع السكن
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $typeLogement = null;

    /**
     * État du logement — حالة السكن
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $etatLogement = null;

    // ══════════════════════════════════════════════
    // CONTACTS — بيانات الإتصال (Image 3)
    // ══════════════════════════════════════════════

    /**
     * Téléphone principal — رقم الهاتف
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    /**
     * Téléphone secondaire — رقم هاتف أخر
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone2 = null;

    /**
     * Revenu mensuel total de la famille — دخل الأسرة / إجمالي الدخل الشهري
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $revenuMensuelTotal = null;

    // ══════════════════════════════════════════════
    // REVENUS ET EMPLOI — الدخل والعمل (Image 3)
    // ══════════════════════════════════════════════

    /**
     * Emploi actuel du chef — العمل الحالي لمعيل الأسرة
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emploiChef = null;

    /**
     * Source de revenu familial — مصدر دخل الأسرة
     * Ex: "Salaire", "Commerce", "Aide sociale"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceRevenu = null;

    /**
     * Raison de la demande de kefala — سبب طلب الكفالة
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $raisonDemande = null;

    // ══════════════════════════════════════════════
    // SANTÉ — الحالة الصحية (Image 3)
    // ══════════════════════════════════════════════

    /**
     * Situation sanitaire de la famille — الحالة الصحية
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $etatSante = null;

    /**
     * Nombre total de membres dans la famille
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $nombreMembres = null;

    // ══════════════════════════════════════════════
    // PHOTO — صورة رب الأسرة
    // ══════════════════════════════════════════════

    /**
     * Nom du fichier photo stocké dans public/uploads/familles/
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    // ══════════════════════════════════════════════
    // MÉTADONNÉES
    // ══════════════════════════════════════════════

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // ══════════════════════════════════════════════
    // CONSTRUCTEUR
    // ══════════════════════════════════════════════

    public function __construct()
    {
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

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $d): static { $this->dateNaissance = $d; return $this; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $g): static { $this->genre = $g; return $this; }

    public function getNomChef(): ?string { return $this->nomChef; }
    public function setNomChef(string $n): static { $this->nomChef = $n; return $this; }

    public function getCinChef(): ?string { return $this->cinChef; }
    public function setCinChef(?string $c): static { $this->cinChef = $c; return $this; }

    public function getNiveauEducatif(): ?string { return $this->niveauEducatif; }
    public function setNiveauEducatif(?string $n): static { $this->niveauEducatif = $n; return $this; }

    public function getAdresseSкn(): ?string { return $this->adresseSкn; }
    public function setAdresseSкn(?string $a): static { $this->adresseSкn = $a; return $this; }

    public function getTypeLogement(): ?string { return $this->typeLogement; }
    public function setTypeLogement(?string $t): static { $this->typeLogement = $t; return $this; }

    public function getEtatLogement(): ?string { return $this->etatLogement; }
    public function setEtatLogement(?string $e): static { $this->etatLogement = $e; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $t): static { $this->telephone = $t; return $this; }

    public function getTelephone2(): ?string { return $this->telephone2; }
    public function setTelephone2(?string $t): static { $this->telephone2 = $t; return $this; }

    public function getRevenuMensuelTotal(): ?string { return $this->revenuMensuelTotal; }
    public function setRevenuMensuelTotal(?string $r): static { $this->revenuMensuelTotal = $r; return $this; }

    public function getEmploiChef(): ?string { return $this->emploiChef; }
    public function setEmploiChef(?string $e): static { $this->emploiChef = $e; return $this; }

    public function getSourceRevenu(): ?string { return $this->sourceRevenu; }
    public function setSourceRevenu(?string $s): static { $this->sourceRevenu = $s; return $this; }

    public function getRaisonDemande(): ?string { return $this->raisonDemande; }
    public function setRaisonDemande(?string $r): static { $this->raisonDemande = $r; return $this; }

    public function getEtatSante(): ?string { return $this->etatSante; }
    public function setEtatSante(?string $e): static { $this->etatSante = $e; return $this; }

    public function getNombreMembres(): ?int { return $this->nombreMembres; }
    public function setNombreMembres(?int $n): static { $this->nombreMembres = $n; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $p): static { $this->photo = $p; return $this; }
    public function hasPhoto(): bool { return $this->photo !== null; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    public function getTypeLogementLabel(): string
    {
        return $this->typeLogement
            ? (self::TYPES_LOGEMENT[$this->typeLogement]['label_fr'] ?? $this->typeLogement)
            : '';
    }

    public function getTypeLogementLabelAr(): string
    {
        return $this->typeLogement
            ? (self::TYPES_LOGEMENT[$this->typeLogement]['label_ar'] ?? $this->typeLogement)
            : '';
    }

    public function __toString(): string { return $this->nomChef ?? ''; }
}
