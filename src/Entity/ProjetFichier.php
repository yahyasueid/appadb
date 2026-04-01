<?php
// src/Entity/ProjetFichier.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PROJET FICHIER                                      ║
// ║  Stocke les fichiers PDF d'un projet                        ║
// ║                                                              ║
// ║  2 catégories (visibles dans tes captures) :                 ║
// ║  - "rapport"   → Rapports du projet (PDF ou images)         ║
// ║  - "transfert" → Transferts financiers (PDF)                ║
// ║                                                              ║
// ║  Fichiers physiques → public/uploads/projets/fichiers/       ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ProjetFichierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjetFichierRepository::class)]
#[ORM\Table(name: 'projet_fichier')]
class ProjetFichier
{
    // Catégories de fichiers (comme dans ta capture)
    public const CAT_RAPPORT    = 'rapport';      // التقارير          — Rapports du projet
    public const CAT_TRANSFERT  = 'transfert';    // الترحيل المالي    — Transferts financiers
    public const CAT_CONVENTION = 'convention';   // الاتفاقية          — Conventions / contrats

    public const CATEGORIES = [
        self::CAT_RAPPORT    => [
            'label_fr' => 'Rapport',
            'label_ar' => 'تقرير',
            'icone'    => 'bi-file-earmark-pdf',
            'couleur'  => '#D32F2F',
        ],
        self::CAT_TRANSFERT  => [
            'label_fr' => 'Transfert financier',
            'label_ar' => 'ترحيل مالي',
            'icone'    => 'bi-cash-stack',
            'couleur'  => '#0288D1',
        ],
        self::CAT_CONVENTION => [
            'label_fr' => 'Convention',
            'label_ar' => 'الاتفاقية',
            'icone'    => 'bi-file-earmark-text',
            'couleur'  => '#2E7D32',
        ],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Relation : ce fichier appartient à UN projet
     */
    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'fichiers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Projet $projet = null;

    /**
     * Catégorie du fichier : 'rapport' ou 'transfert'
     */
    #[ORM\Column(length: 30)]
    private string $categorie = self::CAT_RAPPORT;

    /**
     * Nom du fichier sur le serveur
     * Ex: "rapport_5_abc123.pdf"
     */
    
    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    /**
     * Nom original du fichier (pour affichage)
     * Ex: "Rapport_Mars_2026.pdf"
     */
    #[ORM\Column(length: 255)]
    private ?string $originalName = null;

    /**
     * Type MIME du fichier
     * Ex: "application/pdf", "image/jpeg"
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    /**
     * Taille du fichier en octets
     */
    #[ORM\Column(nullable: true)]
    private ?int $taille = null;

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

    public function getCategorie(): string { return $this->categorie; }
    public function setCategorie(string $categorie): static { $this->categorie = $categorie; return $this; }

    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): static { $this->filename = $filename; return $this; }

    public function getOriginalName(): ?string { return $this->originalName; }
    public function setOriginalName(string $originalName): static { $this->originalName = $originalName; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getTaille(): ?int { return $this->taille; }
    public function setTaille(?int $taille): static { $this->taille = $taille; return $this; }

    public function getUploadedAt(): ?\DateTimeInterface { return $this->uploadedAt; }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    /** Chemin relatif pour téléchargement */
    public function getPath(): string
    {
        return 'uploads/projets/fichiers/' . $this->filename;
    }

    /** Label de la catégorie */
    public function getCategorieLabel(): string { return self::CATEGORIES[$this->categorie]['label_fr'] ?? 'Inconnu'; }
    public function getCategorieLabelAr(): string { return self::CATEGORIES[$this->categorie]['label_ar'] ?? 'غير معروف'; }
    public function getCategorieIcone(): string { return self::CATEGORIES[$this->categorie]['icone'] ?? 'bi-file'; }
    public function getCategorieCouleur(): string { return self::CATEGORIES[$this->categorie]['couleur'] ?? '#9E9E9E'; }

    /** Est-ce un PDF ? */
    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }

    /** Taille formatée */
    public function getTailleFormatee(): string
    {
        if (!$this->taille) return '';
        if ($this->taille < 1024) return $this->taille . ' o';
        if ($this->taille < 1048576) return round($this->taille / 1024, 1) . ' Ko';
        return round($this->taille / 1048576, 1) . ' Mo';
    }
}