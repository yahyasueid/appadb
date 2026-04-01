<?php
// src/Entity/Parrain.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PARRAIN (الكافل)                                     ║
// ║                                                              ║
// ║  Représente le donateur (kafil) qui finance un parrainage.   ║
// ║  Inspiré du formulaire "بيانات الكافل" (Image 2).           ║
// ║                                                              ║
// ║  Un parrain peut financer plusieurs parrainages              ║
// ║  (OneToMany → Parrainage)                                    ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ParrainRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParrainRepository::class)]
#[ORM\Table(name: 'parrain')]
#[ORM\HasLifecycleCallbacks]
class Parrain
{
    // ══════════════════════════════════════════════
    // IDENTIFICATION
    // ══════════════════════════════════════════════

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Numéro unique du parrain — رقم الكافل
     * Ex: "KAF-2024-0010"
     */
    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank]
    private ?string $numero = null;

    // ══════════════════════════════════════════════
    // INFORMATIONS PERSONNELLES — بيانات الكافل
    // Champs issus du formulaire (Image 2)
    // ══════════════════════════════════════════════

    /**
     * Nom complet du parrain — اسم الكافل
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du parrain est obligatoire')]
    private ?string $nom = null;

    /**
     * Adresse postale — العنوان
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresse = null;

    /**
     * Boîte postale — ص ب
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bp = null;

    /**
     * Numéro de téléphone — الهاتف
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    /**
     * Email — Email
     */
    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * Pays d'origine du parrain
     * Utile pour les parrains venant des pays du Golfe, Europe…
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $pays = null;

    // ══════════════════════════════════════════════
    // RELATIONS
    // ══════════════════════════════════════════════

    /**
     * Association gérant ce parrain
     */
    #[ORM\ManyToOne(targetEntity: Association::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Association $association = null;

    /**
     * Tous les parrainages financés par ce parrain
     */
    #[ORM\OneToMany(targetEntity: Parrainage::class, mappedBy: 'parrain', cascade: ['persist'])]
    private Collection $parrainages;

    // ══════════════════════════════════════════════
    // MÉTADONNÉES
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
        $this->parrainages = new ArrayCollection();
        $this->createdAt   = new \DateTime();
        $this->updatedAt   = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTime(); }

    // ══════════════════════════════════════════════
    // GETTERS / SETTERS
    // ══════════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getNumero(): ?string { return $this->numero; }
    public function setNumero(string $n): static { $this->numero = $n; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $n): static { $this->nom = $n; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $a): static { $this->adresse = $a; return $this; }

    public function getBp(): ?string { return $this->bp; }
    public function setBp(?string $b): static { $this->bp = $b; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $t): static { $this->telephone = $t; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $e): static { $this->email = $e; return $this; }

    public function getPays(): ?string { return $this->pays; }
    public function setPays(?string $p): static { $this->pays = $p; return $this; }

    public function getAssociation(): ?Association { return $this->association; }
    public function setAssociation(?Association $a): static { $this->association = $a; return $this; }

    public function getCreePar(): ?User { return $this->creePar; }
    public function setCreePar(?User $u): static { $this->creePar = $u; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    /** @return Collection<int, Parrainage> */
    public function getParrainages(): Collection { return $this->parrainages; }

    // ══════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════

    /** Nombre de parrainages actifs */
    public function countParrainagesActifs(): int
    {
        return $this->parrainages->filter(
            fn(Parrainage $p) => $p->getStatut() === Parrainage::STATUT_ACTIF
        )->count();
    }

    public function __toString(): string { return $this->numero . ' — ' . $this->nom; }
}
