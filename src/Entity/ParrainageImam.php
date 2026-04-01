<?php
// src/Entity/ParrainageImam.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAINAGE IMAM / ENSEIGNANT                         ║
// ║  (قائمة الأئمه والمعلمين)                                    ║
// ║                                                              ║
// ║  Fiche spécifique pour les Imams et Enseignants              ║
// ║  Calquée sur le formulaire de l'Image 4 :                   ║
// ║   - البيانات        → données personnelles                   ║
// ║   - الشهادة العلمية → diplôme et formation                  ║
// ║   - الخبرات        → expériences professionnelles           ║
// ║   - الحالة الاجتماعية → situation sociale et revenus        ║
// ║   - الأسرة والتواصل → famille et contacts                   ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainageImamRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainageImamRepository::class)]
#[ORM\Table(name: 'parrainage_imam')]
#[ORM\HasLifecycleCallbacks]
class ParrainageImam
{
    // ══════════════════════════════════════════════
    // CONSTANTES — Type de profession
    // ══════════════════════════════════════════════

    public const METIER_IMAM         = 'imam';
    public const METIER_ENSEIGNANT   = 'enseignant';
    public const METIER_MUEZZIN      = 'muezzin';
    public const METIER_AUTRE        = 'autre';

    public const METIERS = [
        self::METIER_IMAM       => ['label_fr' => 'Imam',         'label_ar' => 'إمام'],
        self::METIER_ENSEIGNANT => ['label_fr' => 'Enseignant',   'label_ar' => 'معلم'],
        self::METIER_MUEZZIN    => ['label_fr' => 'Muezzin',      'label_ar' => 'مؤذن'],
        self::METIER_AUTRE      => ['label_fr' => 'Autre',        'label_ar' => 'أخرى'],
    ];

    // ══════════════════════════════════════════════
    // CONSTANTES — Type d'emploi (نوع العمل)
    // ══════════════════════════════════════════════

    public const EMPLOI_FIXE      = 'fixe';
    public const EMPLOI_PARTIEL   = 'partiel';
    public const EMPLOI_BENEVOLE  = 'benevole';
    public const EMPLOI_SANS      = 'sans';

    public const TYPES_EMPLOI = [
        self::EMPLOI_FIXE     => ['label_fr' => 'Emploi fixe',    'label_ar' => 'وظيفة ثابتة'],
        self::EMPLOI_PARTIEL  => ['label_fr' => 'Temps partiel',  'label_ar' => 'دوام جزئي'],
        self::EMPLOI_BENEVOLE => ['label_fr' => 'Bénévole',       'label_ar' => 'متطوع'],
        self::EMPLOI_SANS     => ['label_fr' => 'Sans emploi',    'label_ar' => 'بدون عمل'],
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
    #[ORM\OneToOne(targetEntity: Parrainage::class, inversedBy: 'ficheImam')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Parrainage $parrainage = null;

    // ══════════════════════════════════════════════
    // DONNÉES PERSONNELLES — البيانات (Image 4)
    // ══════════════════════════════════════════════

    /**
     * Nom complet — الاسم كاملاً
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

    /**
     * Profession — اختر المهنة
     */
    #[ORM\Column(length: 30)]
    private string $metier = self::METIER_IMAM;

    // ══════════════════════════════════════════════
    // FORMATION — الشهادة العلمية (Image 4)
    // ══════════════════════════════════════════════

    /**
     * Institution / établissement de formation — المؤسسة العلمية
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $institutionFormation = null;

    /**
     * Diplôme obtenu — الشهادة العلمية
     * Ex: "Licence en sciences islamiques", "Baccalauréat"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplome = null;

    /**
     * Spécialité / filière — التخصص
     * Ex: "Fiqh", "Hadith", "Mathématiques"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialite = null;

    /**
     * Année d'obtention du diplôme — سنة التخرج
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $anneeObtentionDiplome = null;

    // ══════════════════════════════════════════════
    // EXPÉRIENCE PROFESSIONNELLE (Image 4)
    // ══════════════════════════════════════════════

    /**
     * Date de début de la fonction actuelle — تاريخ الإلتحاق
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebutFonction = null;

    /**
     * Expériences académiques et pratiques — الخبرات العلمية / العملية
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $experiences = null;

    /**
     * Compétences particulières — المهارات
     * Ex: "Hafiz du Coran", "Khattaba", "Informatique"
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $competences = null;

    // ══════════════════════════════════════════════
    // SITUATION SOCIALE — الحالة الاجتماعية (Image 4)
    // ══════════════════════════════════════════════

    /**
     * Type d'emploi actuel — نوع العمل
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $typeEmploi = null;

    /**
     * Poste / fonction actuelle — الوظيفة / المهنة الحالية
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fonctionActuelle = null;

    /**
     * Revenu mensuel personnel — إجمالي دخله الشهري
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $revenuMensuel = null;

    /**
     * Revenu mensuel total de la famille — إجمالي الدخل الشهري للأسرة
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $revenuFamilleTotal = null;

    /**
     * Situation sociale (état matrimonial, charges…) — اختيار الحالة الاجتماعية
     * Ex: "Marié", "Célibataire", "Veuf"
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $situationSociale = null;

    // ══════════════════════════════════════════════
    // FAMILLE ET CONTACTS — الأسرة والتواصل (Image 4)
    // ══════════════════════════════════════════════

    /**
     * Nombre d'enfants mâles — الذكور
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $nbGarcons = 0;

    /**
     * Nombre de filles — الإناث
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $nbFilles = 0;

    /**
     * Nombre total de personnes à charge — عدد أفراد الأسرة
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $nombrePersonnesCharge = null;

    /**
     * Adresse du domicile — عنوان السكن
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresse = null;

    /**
     * Besoins spécifiques de la famille — احتياجات الاسرة
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $besoins = null;

    /**
     * Téléphone principal — رقم الهاتف
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    /**
     * Téléphone secondaire — هاتف أخر
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone2 = null;

    // ══════════════════════════════════════════════
    // PHOTO — صورة الإمام / المعلم
    // ══════════════════════════════════════════════

    /**
     * Nom du fichier photo stocké dans public/uploads/imams/
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

    public function getMetier(): string { return $this->metier; }
    public function setMetier(string $m): static { $this->metier = $m; return $this; }

    public function getInstitutionFormation(): ?string { return $this->institutionFormation; }
    public function setInstitutionFormation(?string $i): static { $this->institutionFormation = $i; return $this; }

    public function getDiplome(): ?string { return $this->diplome; }
    public function setDiplome(?string $d): static { $this->diplome = $d; return $this; }

    public function getSpecialite(): ?string { return $this->specialite; }
    public function setSpecialite(?string $s): static { $this->specialite = $s; return $this; }

    public function getAnneeObtentionDiplome(): ?int { return $this->anneeObtentionDiplome; }
    public function setAnneeObtentionDiplome(?int $a): static { $this->anneeObtentionDiplome = $a; return $this; }

    public function getDateDebutFonction(): ?\DateTimeInterface { return $this->dateDebutFonction; }
    public function setDateDebutFonction(?\DateTimeInterface $d): static { $this->dateDebutFonction = $d; return $this; }

    public function getExperiences(): ?string { return $this->experiences; }
    public function setExperiences(?string $e): static { $this->experiences = $e; return $this; }

    public function getCompetences(): ?string { return $this->competences; }
    public function setCompetences(?string $c): static { $this->competences = $c; return $this; }

    public function getTypeEmploi(): ?string { return $this->typeEmploi; }
    public function setTypeEmploi(?string $t): static { $this->typeEmploi = $t; return $this; }

    public function getFonctionActuelle(): ?string { return $this->fonctionActuelle; }
    public function setFonctionActuelle(?string $f): static { $this->fonctionActuelle = $f; return $this; }

    public function getRevenuMensuel(): ?string { return $this->revenuMensuel; }
    public function setRevenuMensuel(?string $r): static { $this->revenuMensuel = $r; return $this; }

    public function getRevenuFamilleTotal(): ?string { return $this->revenuFamilleTotal; }
    public function setRevenuFamilleTotal(?string $r): static { $this->revenuFamilleTotal = $r; return $this; }

    public function getSituationSociale(): ?string { return $this->situationSociale; }
    public function setSituationSociale(?string $s): static { $this->situationSociale = $s; return $this; }

    public function getNbGarcons(): int { return $this->nbGarcons; }
    public function setNbGarcons(int $n): static { $this->nbGarcons = $n; return $this; }

    public function getNbFilles(): int { return $this->nbFilles; }
    public function setNbFilles(int $n): static { $this->nbFilles = $n; return $this; }

    public function getNombrePersonnesCharge(): ?int { return $this->nombrePersonnesCharge; }
    public function setNombrePersonnesCharge(?int $n): static { $this->nombrePersonnesCharge = $n; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $a): static { $this->adresse = $a; return $this; }

    public function getBesoins(): ?string { return $this->besoins; }
    public function setBesoins(?string $b): static { $this->besoins = $b; return $this; }

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

    public function getMetierLabel(): string   { return self::METIERS[$this->metier]['label_fr'] ?? 'Inconnu'; }
    public function getMetierLabelAr(): string { return self::METIERS[$this->metier]['label_ar'] ?? 'غير معروف'; }

    public function getTypeEmploiLabel(): string
    {
        return $this->typeEmploi
            ? (self::TYPES_EMPLOI[$this->typeEmploi]['label_fr'] ?? $this->typeEmploi)
            : '';
    }

    /** Nombre total d'enfants */
    public function getNbEnfants(): int { return $this->nbGarcons + $this->nbFilles; }

    public function __toString(): string { return $this->nomComplet ?? ''; }
}
