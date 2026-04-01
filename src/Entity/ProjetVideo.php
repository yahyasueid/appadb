<?php
// src/Entity/ProjetVideo.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ PROJET VIDEO                                        ║
// ║  Stocke les liens YouTube liés à un projet                  ║
// ║                                                              ║
// ║  Pas de fichier uploadé — juste l'URL YouTube               ║
// ║  L'ID YouTube est extrait automatiquement pour l'embed      ║
// ║  Ex: "https://www.youtube.com/watch?v=abc123" → "abc123"    ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\ProjetVideoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjetVideoRepository::class)]
#[ORM\Table(name: 'projet_video')]
class ProjetVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Relation : cette vidéo appartient à UN projet
     */
    #[ORM\ManyToOne(targetEntity: Projet::class, inversedBy: 'videos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Projet $projet = null;

    /**
     * URL complète de la vidéo YouTube
     * Ex: "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
     * ou:  "https://youtu.be/dQw4w9WgXcQ"
     */
    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: 'L\'URL YouTube est obligatoire')]
    #[Assert\Url(message: 'L\'URL n\'est pas valide')]
    private ?string $url = null;

    /**
     * Titre de la vidéo (optionnel, saisi manuellement)
     * Ex: "Visite du chantier — Mars 2026"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $addedAt = null;

    public function __construct()
    {
        $this->addedAt = new \DateTime();
    }

    // ═══════════════════════════════════════════
    // GETTERS / SETTERS
    // ═══════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getProjet(): ?Projet { return $this->projet; }
    public function setProjet(?Projet $projet): static { $this->projet = $projet; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(string $url): static { $this->url = $url; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(?string $titre): static { $this->titre = $titre; return $this; }

    public function getAddedAt(): ?\DateTimeInterface { return $this->addedAt; }

    // ═══════════════════════════════════════════
    // HELPERS — YouTube
    // ═══════════════════════════════════════════

    /**
     * Extraire l'ID YouTube depuis l'URL
     * Supporte :
     *   - https://www.youtube.com/watch?v=abc123
     *   - https://youtu.be/abc123
     *   - https://www.youtube.com/embed/abc123
     */
    public function getYoutubeId(): ?string
    {
        if (!$this->url) return null;

        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
            '/youtu\.be\/([a-zA-Z0-9_-]+)/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * URL d'embed pour iframe
     * Ex: "https://www.youtube.com/embed/abc123"
     */
    public function getEmbedUrl(): ?string
    {
        $id = $this->getYoutubeId();
        return $id ? 'https://www.youtube.com/embed/' . $id : null;
    }

    /**
     * URL de la miniature YouTube
     * Ex: "https://img.youtube.com/vi/abc123/mqdefault.jpg"
     */
    public function getThumbnailUrl(): ?string
    {
        $id = $this->getYoutubeId();
        return $id ? 'https://img.youtube.com/vi/' . $id . '/mqdefault.jpg' : null;
    }
}
