<?php
// src/Entity/ParrainageOrphelin.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAINAGE ORPHELIN (استمارة طلب كفالة يتيم)        ║
// ║                                                              ║
// ║  Fiche spécifique au type "Orphelin" — قائمة طلبات الأيتام  ║
// ║  Calquée sur le formulaire de l'Image 2 :                   ║
// ║   - بيانات الكافل  → lié à l'entité Parrain                 ║
// ║   - بيانات اليتيم  → données de l'orphelin                  ║
// ║   - المرحلة الدراسية → scolarité                            ║
// ║                                                              ║
// ║  Lié à Parrainage (OneToOne — chaque parrainage orphelin     ║
// ║  a une et une seule fiche orphelin)                          ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainageOrphelinRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainageOrphelinRepository::class)]
#[ORM\Table(name: 'parrainage_orphelin')]
#[ORM\HasLifecycleCallbacks]
class ParrainageOrphelin
{
    // ══════════════════════════════════════════════
    // CONSTANTES — Types d'orphelins
    // ══════════════════════════════════════════════

    public const TYPE_PERE_DECEDE  = 'pere_decede';    // Père décédé
    public const TYPE_MERE_DECEDEE = 'mere_decedee';   // Mère décédée
    public const TYPE_DEUX_PARENTS = 'deux_parents';   // Les deux parents décédés

    public const TYPES_ORPHELIN = [
        self::TYPE_PERE_DECEDE  => ['label_fr' => 'Père décédé',           'label_ar' => 'فقد الأب'],
        self::TYPE_MERE_DECEDEE => ['label_fr' => 'Mère décédée',          'label_ar' => 'فقد الأم'],
        self::TYPE_DEUX_PARENTS => ['label_fr' => 'Les deux parents',       'label_ar' => 'فقد الوالدين'],
    ];

    // ══════════════════════════════════════════════
    // CONSTANTES — Relation du tuteur (صلة القرابة)
    // ══════════════════════════════════════════════

    public const TUTEUR_MERE    = 'mere';
    public const TUTEUR_FRERE   = 'frere';
    public const TUTEUR_ONCLE   = 'oncle';
    public const TUTEUR_GRANPERE = 'grand_pere';
    public const TUTEUR_AUTRE   = 'autre';

    public const RELATIONS_TUTEUR = [
        self::TUTEUR_MERE     => ['label_fr' => 'Mère',        'label_ar' => 'الأم'],
        self::TUTEUR_FRERE    => ['label_fr' => 'Frère',       'label_ar' => 'الأخ'],
        self::TUTEUR_ONCLE    => ['label_fr' => 'Oncle',       'label_ar' => 'العم'],
        self::TUTEUR_GRANPERE => ['label_fr' => 'Grand-père',  'label_ar' => 'الجد'],
        self::TUTEUR_AUTRE    => ['label_fr' => 'Autre',       'label_ar' => 'أخرى'],
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
     * Chaque fiche orphelin est rattachée à UN parrainage
     */
    #[ORM\OneToOne(targetEntity: Parrainage::class, inversedBy: 'ficheOrphelin')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parrainage $parrainage = null;

    // ══════════════════════════════════════════════
    // DONNÉES DE L'ORPHELIN — بيانات اليتيم (Image 2)
    // ══════════════════════════════════════════════

    /**
     * Numéro de carte nationale — الرقم الوطني
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cin = null;

    /**
     * Nom complet de l'orphelin — الإسم الكامل لليتيم
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l\'orphelin est obligatoire')]
    private ?string $nomComplet = null;

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
     * Genre — الجنس
     */
    #[ORM\Column(length: 10)]
    private string $genre = 'masculin';

    /**
     * Type d'orphelin — نوع اليتيم
     * (père décédé / mère décédée / les deux parents)
     */
    #[ORM\Column(length: 30)]
    private string $typeOrphelin = self::TYPE_PERE_DECEDE;

    // ══════════════════════════════════════════════
    // INFORMATIONS FAMILLE — معلومات الأسرة
    // ══════════════════════════════════════════════

    /**
     * Nom de la mère — اسم الأم
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomMere = null;

    /**
     * La mère est-elle remariée — الأم متزوجة
     */
    #[ORM\Column]
    private bool $mereMariee = false;

    /**
     * Nom du tuteur légal / wali — اسم ولي الأمر
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomTuteur = null;

    /**
     * Relation du tuteur avec l'orphelin — صلة القرابة
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $relationTuteur = null;

    /**
     * Date de décès du père — تاريخ وفاة الأب
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDecesPere = null;

    /**
     * Date de décès de la mère (si décédée) — تاريخ وفاة الأم اذا كانت ميت
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDecesMere = null;

    /**
     * Nombre de frères — عدد الأخوة الذكور
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $nbFreres = 0;

    /**
     * Nombre de sœurs — عدد الأخوة الإناث
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $nbSoeurs = 0;

    /**
     * Adresse de l'orphelin — عنوان اليتيم
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresse = null;

    /**
     * Téléphone du foyer — الهاتف
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    /**
     * État de santé général — الحالة الصحية
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatSante = null;

    // ══════════════════════════════════════════════
    // SCOLARITÉ — المرحلة الدراسية (Image 2)
    // ══════════════════════════════════════════════

    /**
     * Nom de l'école / établissement — اسم المدرسة
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ecole = null;

    /**
     * Niveau scolaire actuel — المرحلة الدراسية
     * Ex: "CM2", "3ème", "Terminale", "Licence 2"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $niveauScolaire = null;

    /**
     * Raison de non-scolarisation (si applicable) — سبب عدم الدراسة
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $raisonNonScolarisation = null;

    /**
     * Photo de l'orphelin — stockée dans public/uploads/orphelins/
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

    public function getCin(): ?string { return $this->cin; }
    public function setCin(?string $c): static { $this->cin = $c; return $this; }

    public function getNomComplet(): ?string { return $this->nomComplet; }
    public function setNomComplet(string $n): static { $this->nomComplet = $n; return $this; }

    public function getDateNaissance(): ?\DateTimeInterface { return $this->dateNaissance; }
    public function setDateNaissance(?\DateTimeInterface $d): static { $this->dateNaissance = $d; return $this; }

    public function getLieuNaissance(): ?string { return $this->lieuNaissance; }
    public function setLieuNaissance(?string $l): static { $this->lieuNaissance = $l; return $this; }

    public function getGenre(): string { return $this->genre; }
    public function setGenre(string $g): static { $this->genre = $g; return $this; }

    public function getTypeOrphelin(): string { return $this->typeOrphelin; }
    public function setTypeOrphelin(string $t): static { $this->typeOrphelin = $t; return $this; }

    public function getNomMere(): ?string { return $this->nomMere; }
    public function setNomMere(?string $n): static { $this->nomMere = $n; return $this; }

    public function isMereMariee(): bool { return $this->mereMariee; }
    public function setMereMariee(bool $m): static { $this->mereMariee = $m; return $this; }

    public function getNomTuteur(): ?string { return $this->nomTuteur; }
    public function setNomTuteur(?string $n): static { $this->nomTuteur = $n; return $this; }

    public function getRelationTuteur(): ?string { return $this->relationTuteur; }
    public function setRelationTuteur(?string $r): static { $this->relationTuteur = $r; return $this; }

    public function getDateDecesPere(): ?\DateTimeInterface { return $this->dateDecesPere; }
    public function setDateDecesPere(?\DateTimeInterface $d): static { $this->dateDecesPere = $d; return $this; }

    public function getDateDecesMere(): ?\DateTimeInterface { return $this->dateDecesMere; }
    public function setDateDecesMere(?\DateTimeInterface $d): static { $this->dateDecesMere = $d; return $this; }

    public function getNbFreres(): int { return $this->nbFreres; }
    public function setNbFreres(int $n): static { $this->nbFreres = $n; return $this; }

    public function getNbSoeurs(): int { return $this->nbSoeurs; }
    public function setNbSoeurs(int $n): static { $this->nbSoeurs = $n; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $a): static { $this->adresse = $a; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $t): static { $this->telephone = $t; return $this; }

    public function getEtatSante(): ?string { return $this->etatSante; }
    public function setEtatSante(?string $e): static { $this->etatSante = $e; return $this; }

    public function getEcole(): ?string { return $this->ecole; }
    public function setEcole(?string $e): static { $this->ecole = $e; return $this; }

    public function getNiveauScolaire(): ?string { return $this->niveauScolaire; }
    public function setNiveauScolaire(?string $n): static { $this->niveauScolaire = $n; return $this; }

    public function getRaisonNonScolarisation(): ?string { return $this->raisonNonScolarisation; }
    public function setRaisonNonScolarisation(?string $r): static { $this->raisonNonScolarisation = $r; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $p): static { $this->photo = $p; return $this; }

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

    public function getPhotoPath(): string
    {
        return $this->photo ? 'uploads/orphelins/' . $this->photo : '';
    }

    public function hasPhoto(): bool { return $this->photo !== null && $this->photo !== ''; }

    public function getTypeOrphelinLabel(): string    { return self::TYPES_ORPHELIN[$this->typeOrphelin]['label_fr'] ?? 'Inconnu'; }
    public function getTypeOrphelinLabelAr(): string  { return self::TYPES_ORPHELIN[$this->typeOrphelin]['label_ar'] ?? 'غير معروف'; }

    public function getRelationTuteurLabel(): string
    {
        return $this->relationTuteur
            ? (self::RELATIONS_TUTEUR[$this->relationTuteur]['label_fr'] ?? $this->relationTuteur)
            : '';
    }

    public function __toString(): string { return $this->nomComplet ?? ''; }
}
