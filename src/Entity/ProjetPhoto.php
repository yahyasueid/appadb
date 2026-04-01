<?php
// src/Entity/ProjetPhoto.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PROJET PHOTO                                        ║
// ║  Stocke les photos d'un projet (max 20 par projet)          ║
// ║                                                              ║
// ║  Fichiers physiques → public/uploads/projets/photos/         ║
// ║  Formats acceptés : JPG, PNG, WebP                           ║
// ║  Taille max : 5 Mo par photo                                 ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ProjetPhotoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjetPhotoRepository::class)]
#[ORM\Table(name: 'projet_photo')]
class ProjetPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Relation inverse : cette photo appartient à UN projet
     * ManyToOne : Plusieurs photos → 1 projet
     * Si le projet est supprimé → toutes ses photos sont supprimées (CASCADE)
     */
    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Projet $projet = null;

    /**
     * Nom du fichier stocké sur le serveur
     * Ex: "projet_5_abc123.jpg"
     * Le fichier physique est dans public/uploads/projets/photos/
     */
    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    /**
     * Nom original du fichier (pour affichage)
     * Ex: "photo_chantier_mars.jpg"
     */
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    /**
     * Légende / description de la photo (optionnel)
     * Ex: "Vue du chantier — Phase 1"
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $legende = null;

    /**
     * Taille du fichier en octets
     * Utile pour afficher "1.2 Mo" dans l'interface
     */
    #[ORM\Column(nullable: true)]
    private ?int $taille = null;

    /**
     * Ordre d'affichage (1 = première photo = couverture)
     * Permet de réorganiser les photos par drag & drop
     */
    #[ORM\Column(type: Types::SMALLINT)]
    private int $position = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $uploadedAt = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    // ═══════════════════════════════════════════
    // GETTERS / SETTERS
    // ═══════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getProjet(): ?Projet { return $this->projet; }
    public function setProjet(?Projet $projet): static { $this->projet = $projet; return $this; }

    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): static { $this->filename = $filename; return $this; }

    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $originalName): static { $this->originalName = $originalName; return $this; }

    public function getLegende(): ?string { return $this->legende; }
    public function setLegende(?string $legende): static { $this->legende = $legende; return $this; }

    public function getTaille(): ?int { return $this->taille; }
    public function setTaille(?int $taille): static { $this->taille = $taille; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getUploadedAt(): ?\DateTimeInterface { return $this->uploadedAt; }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    /** Chemin relatif pour Twig : {{ asset(photo.path) }} */
    public function getPath(): string
    {
        return 'uploads/projets/photos/' . $this->filename;
    }

    /** Taille formatée : "1.2 Mo" */
    public function getTailleFormatee(): string
    {
        if (!$this->taille) return '';
        if ($this->taille < 1024) return $this->taille . ' o';
        if ($this->taille < 1048576) return round($this->taille / 1024, 1) . ' Ko';
        return round($this->taille / 1048576, 1) . ' Mo';
    }
}
