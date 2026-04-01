<?php
// src/Entity/ParrainageHandicap.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAINAGE PERSONNES À BESOINS SPÉCIAUX             ║
// ║  (قائمة ذوي الإحتياجات الخاصة)                              ║
// ║                                                              ║
// ║  Fiche spécifique pour les personnes handicapées             ║
// ║  Calquée sur le formulaire de l'Image 5 :                   ║
// ║   - البيانات الأساسية → données personnelles                ║
// ║   - الدراسة والمستوى → scolarité et niveau                  ║
// ║   - الإعاقة والعلاج  → type et détails du handicap          ║
// ║   - الدخل والعمل     → revenus et emploi                    ║
// ║   - الأسرة والتواصل  → famille et contacts                  ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainageHandicapRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainageHandicapRepository::class)]
#[ORM\Table(name: 'parrainage_handicap')]
#[ORM\HasLifecycleCallbacks]
class ParrainageHandicap
{
    // ══════════════════════════════════════════════
    // CONSTANTES — Types d'handicap (نوع الإعاقة)
    // ══════════════════════════════════════════════

    public const TYPE_MOTEUR    = 'moteur';
    public const TYPE_VISUEL    = 'visuel';
    public const TYPE_AUDITIF   = 'auditif';
    public const TYPE_MENTAL    = 'mental';
    public const TYPE_MULTIPLE  = 'multiple';
    public const TYPE_AUTRE     = 'autre';

    public const TYPES_HANDICAP = [
        self::TYPE_MOTEUR   => ['label_fr' => 'Moteur',              'label_ar' => 'حركي'],
        self::TYPE_VISUEL   => ['label_fr' => 'Visuel',              'label_ar' => 'بصري'],
        self::TYPE_AUDITIF  => ['label_fr' => 'Auditif',             'label_ar' => 'سمعي'],
        self::TYPE_MENTAL   => ['label_fr' => 'Mental / Intellectuel','label_ar' => 'ذهني'],
        self::TYPE_MULTIPLE => ['label_fr' => 'Multiple',             'label_ar' => 'متعدد'],
        self::TYPE_AUTRE    => ['label_fr' => 'Autre',                'label_ar' => 'أخرى'],
    ];

    // ══════════════════════════════════════════════
    // CONSTANTES — Type de revenu fixe
    // ══════════════════════════════════════════════

    public const REVENU_SALAIRE   = 'salaire';
    public const REVENU_PENSION   = 'pension';
    public const REVENU_AIDE      = 'aide';
    public const REVENU_AUCUN     = 'aucun';

    public const TYPES_REVENU = [
        self::REVENU_SALAIRE => ['label_fr' => 'Salaire',     'label_ar' => 'راتب'],
        self::REVENU_PENSION => ['label_fr' => 'Pension',     'label_ar' => 'معاش'],
        self::REVENU_AIDE    => ['label_fr' => 'Aide sociale', 'label_ar' => 'مساعدة اجتماعية'],
        self::REVENU_AUCUN   => ['label_fr' => 'Aucun',       'label_ar' => 'لا يوجد'],
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
    #[ORM\OneToOne(targetEntity: Parrainage::class, inversedBy: 'ficheHandicap')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parrainage $parrainage = null;

    // ══════════════════════════════════════════════
    // DONNÉES PERSONNELLES — البيانات الأساسية (Image 5)
    // ══════════════════════════════════════════════

    /**
     * Nom complet — الاسم الكامل
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $nomComplet = null;

    /**
     * Numéro national — الرقم الوطني
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cin = null;

    /**
     * Date de naissance — تاريخ الميلاد
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    /**
     * Lieu de naissance — مكان الميلاد
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lieuNaissance = null;

    /**
     * Genre — اختيار الجنس
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $genre = null;

    // ══════════════════════════════════════════════
    // SCOLARITÉ — الدراسة والمستوى (Image 5)
    // ══════════════════════════════════════════════

    /**
     * Niveau scolaire actuel — أخر شهادة / الصف
     * Ex: "CE2", "3ème année collège", "Non scolarisé"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveauScolaire = null;

    /**
     * Niveau éducatif global — المستوى التعليمي
     * Ex: "Primaire", "Secondaire", "Analphabète"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveauEducatif = null;

    // ══════════════════════════════════════════════
    // HANDICAP ET TRAITEMENT — الإعاقة والعلاج (Image 5)
    // ══════════════════════════════════════════════

    /**
     * Type d'handicap — نوع الإعاقة
     */
    #[ORM\Column(length: 30)]
    private string $typeHandicap = self::TYPE_MOTEUR;

    /**
     * Cause / origine du handicap — سبب الإعاقة
     * Ex: "Accident", "Congénital", "Maladie"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $causeHandicap = null;

    /**
     * Date d'apparition du handicap — تاريخ الإعاقة
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateHandicap = null;

    /**
     * Taux d'incapacité en % — نسبة الإعاقة (0 à 100)
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $tauxHandicap = null;

    /**
     * Type de traitement reçu actuellement — نوع العلاج
     * Ex: "Kinésithérapie", "Médicaments", "Chirurgie programmée"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeTraitement = null;

    /**
     * Détail du traitement actuel — نوع العلاج الذي يتلقاه حالياً
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $detailTraitement = null;

    /**
     * Coût mensuel du traitement — تكلفة العلاج الشهري (MRU)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $coutTraitementMensuel = null;

    // ══════════════════════════════════════════════
    // REVENUS ET EMPLOI — الدخل والعمل (Image 5)
    // ══════════════════════════════════════════════

    /**
     * Type de revenu fixe — هل يوجد دخل ثابت
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $typeRevenuFixe = null;

    /**
     * Emploi actuel (si existe) — الوظيفة / المهنة الحالية
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emploiActuel = null;

    /**
     * Revenu mensuel personnel — إجمالي دخله الشهري
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $revenuMensuel = null;

    /**
     * Revenu mensuel total du foyer — إجمالي الدخل الشهري للأسرة
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $revenuFoyerTotal = null;

    // ══════════════════════════════════════════════
    // FAMILLE ET CONTACTS — الأسرة والتواصل (Image 5)
    // ══════════════════════════════════════════════

    /**
     * Nombre de garçons dans la famille — الذكور
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $nbGarcons = 0;

    /**
     * Nombre de filles — الإناث
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $nbFilles = 0;

    /**
     * Besoins spécifiques de la famille — احتياجات الاسرة
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $besoins = null;

    /**
     * Adresse du domicile — عنوان السكن
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresse = null;

    /**
     * Téléphone principal — رقم الهاتف
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    /**
     * Téléphone secondaire — رقم الهاتف الأخر
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone2 = null;

    // ══════════════════════════════════════════════
    // PHOTO — صورة المستفيد
    // ══════════════════════════════════════════════

    /**
     * Nom du fichier photo stocké dans public/uploads/handicaps/
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

    public function getNomComplet(): ?string { return $this->nomComplet; }
    public function setNomComplet(string $n): static { $this->nomComplet = $n; return $this; }

    public function getCin(): ?string { return $this->cin; }
    public function setCin(?string $c): static { $this->cin = $c; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $d): static { $this->dateNaissance = $d; return $this; }

    public function getLieuNaissance(): ?string { return $this->lieuNaissance; }
    public function setLieuNaissance(?string $l): static { $this->lieuNaissance = $l; return $this; }

    public function getGenre(): ?string { return $this->genre; }
    public function setGenre(?string $g): static { $this->genre = $g; return $this; }

    public function getNiveauScolaire(): ?string { return $this->niveauScolaire; }
    public function setNiveauScolaire(?string $n): static { $this->niveauScolaire = $n; return $this; }

    public function getNiveauEducatif(): ?string { return $this->niveauEducatif; }
    public function setNiveauEducatif(?string $n): static { $this->niveauEducatif = $n; return $this; }

    public function getTypeHandicap(): string { return $this->typeHandicap; }
    public function setTypeHandicap(string $t): static { $this->typeHandicap = $t; return $this; }

    public function getCauseHandicap(): ?string { return $this->causeHandicap; }
    public function setCauseHandicap(?string $c): static { $this->causeHandicap = $c; return $this; }

    public function getDateHandicap(): ?\DateTimeInterface { return $this->dateHandicap; }
    public function setDateHandicap(?\DateTimeInterface $d): static { $this->dateHandicap = $d; return $this; }

    public function getTauxHandicap(): ?int { return $this->tauxHandicap; }
    public function setTauxHandicap(?int $t): static { $this->tauxHandicap = $t; return $this; }

    public function getTypeTraitement(): ?string { return $this->typeTraitement; }
    public function setTypeTraitement(?string $t): static { $this->typeTraitement = $t; return $this; }

    public function getDetailTraitement(): ?string { return $this->detailTraitement; }
    public function setDetailTraitement(?string $d): static { $this->detailTraitement = $d; return $this; }

    public function getCoutTraitementMensuel(): ?string { return $this->coutTraitementMensuel; }
    public function setCoutTraitementMensuel(?string $c): static { $this->coutTraitementMensuel = $c; return $this; }

    public function getTypeRevenuFixe(): ?string { return $this->typeRevenuFixe; }
    public function setTypeRevenuFixe(?string $t): static { $this->typeRevenuFixe = $t; return $this; }

    public function getEmploiActuel(): ?string { return $this->emploiActuel; }
    public function setEmploiActuel(?string $e): static { $this->emploiActuel = $e; return $this; }

    public function getRevenuMensuel(): ?string { return $this->revenuMensuel; }
    public function setRevenuMensuel(?string $r): static { $this->revenuMensuel = $r; return $this; }

    public function getRevenuFoyerTotal(): ?string { return $this->revenuFoyerTotal; }
    public function setRevenuFoyerTotal(?string $r): static { $this->revenuFoyerTotal = $r; return $this; }

    public function getNbGarcons(): int { return $this->nbGarcons; }
    public function setNbGarcons(int $n): static { $this->nbGarcons = $n; return $this; }

    public function getNbFilles(): int { return $this->nbFilles; }
    public function setNbFilles(int $n): static { $this->nbFilles = $n; return $this; }

    public function getBesoins(): ?string { return $this->besoins; }
    public function setBesoins(?string $b): static { $this->besoins = $b; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $a): static { $this->adresse = $a; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $t): static { $this->telephone = $t; return $this; }

    public function getTelephone2(): ?string { return $this->telephone2; }
    public function setTelephone2(?string $t): static { $this->telephone2 = $t; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $p): static { $this->photo = $p; return $this; }
    public function hasPhoto(): bool { return $this->photo !== null; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    public function getAge(): ?int
    {
        if (!$this->dateNaissance) return null;
        return $this->dateNaissance->diff(new \DateTime())->y;
    }

    public function getNbEnfants(): int { return $this->nbGarcons + $this->nbFilles; }

    public function getTypeHandicapLabel(): string   { return self::TYPES_HANDICAP[$this->typeHandicap]['label_fr'] ?? 'Inconnu'; }
    public function getTypeHandicapLabelAr(): string { return self::TYPES_HANDICAP[$this->typeHandicap]['label_ar'] ?? 'غير معروف'; }

    public function getTypeRevenuFixeLabel(): string
    {
        return $this->typeRevenuFixe
            ? (self::TYPES_REVENU[$this->typeRevenuFixe]['label_fr'] ?? $this->typeRevenuFixe)
            : '';
    }

    public function __toString(): string { return $this->nomComplet ?? ''; }
}
