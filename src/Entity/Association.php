<?php
// src/Entity/Association.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  ENTITÉ ASSOCIATION                                          ║
// ║                                                              ║
// ║  Représente une association/organisation partenaire          ║
// ║  Chaque projet appartient à UNE association                  ║
// ║                                                              ║
// ║  Exemple :                                                   ║
// ║  - "Association Développement et Bien Faisance" (la vôtre)   ║
// ║  - Ou un partenaire externe qui co-gère un projet            ║
// ║                                                              ║
// ║  Relations :                                                 ║
// ║  - Une Association a 0..N Projets  → OneToMany               ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Entity;

use App\Repository\AssociationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AssociationRepository::class)]
#[ORM\Table(name: 'association')]
class Association
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // ═══════════════════════════════════════════
    // INFORMATIONS GÉNÉRALES
    // ═══════════════════════════════════════════

    /**
     * Nom officiel en français
     * Ex: "Association Développement et Bien Faisance"
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $nom = null;

    /**
     * Nom en arabe
     * Ex: "جمعية التنمية والعمل الخيري"
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomAr = null;

    /**
     * Sigle / Acronyme
     * Ex: "ADBF"
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $sigle = null;

    // ═══════════════════════════════════════════
    // COORDONNÉES
    // ═══════════════════════════════════════════

    /**
     * Adresse complète du siège
     * Ex: "Ilot K, Tevragh Zeina, Nouakchott"
     */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $adresse = null;

    /**
     * Ville
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $ville = null;

    /**
     * Pays (défaut Mauritanie)
     */
    #[ORM\Column(length: 100)]
    private string $pays = 'Mauritanie';

    /**
     * Téléphone principal
     * Ex: "+222 45 25 XX XX"
     */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    /**
     * Email de contact
     */
    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    /**
     * Site web (optionnel)
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $siteWeb = null;

    // ═══════════════════════════════════════════
    // INFORMATIONS LÉGALES
    // ═══════════════════════════════════════════

    /**
     * Numéro d'agrément / enregistrement officiel
     * Délivré par le ministère de l'intérieur en Mauritanie
     */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $numeroAgrement = null;

    /**
     * Date de création de l'association
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateCreation = null;

    /**
     * Nom du président / responsable
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $responsable = null;

    // ═══════════════════════════════════════════
    // VISUEL
    // ═══════════════════════════════════════════

    /**
     * Logo de l'association (nom du fichier)
     * Stocké dans public/uploads/associations/
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    // ═══════════════════════════════════════════
    // ÉTAT
    // ═══════════════════════════════════════════

    /**
     * Association active ou archivée
     */
    #[ORM\Column]
    private bool $isActive = true;

    // ═══════════════════════════════════════════
    // RELATION — Projets
    // ═══════════════════════════════════════════

    /**
     * Tous les projets de cette association
     * OneToMany : Une association a plusieurs projets
     */
    #[ORM\OneToMany(targetEntity: Projet::class, mappedBy: 'association')]
    private Collection $projets;

    // ═══════════════════════════════════════════
    // MÉTADONNÉES
    // ═══════════════════════════════════════════

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    // ═══════════════════════════════════════════
    // CONSTRUCTEUR
    // ═══════════════════════════════════════════

    public function __construct()
    {
        $this->projets   = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // ═══════════════════════════════════════════
    // GETTERS / SETTERS
    // ═══════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getNomAr(): ?string { return $this->nomAr; }
    public function setNomAr(?string $nomAr): static { $this->nomAr = $nomAr; return $this; }

    public function getSigle(): ?string { return $this->sigle; }
    public function setSigle(?string $sigle): static { $this->sigle = $sigle; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $ville): static { $this->ville = $ville; return $this; }

    public function getPays(): string { return $this->pays; }
    public function setPays(string $pays): static { $this->pays = $pays; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getSiteWeb(): ?string { return $this->siteWeb; }
    public function setSiteWeb(?string $siteWeb): static { $this->siteWeb = $siteWeb; return $this; }

    public function getNumeroAgrement(): ?string { return $this->numeroAgrement; }
    public function setNumeroAgrement(?string $numeroAgrement): static { $this->numeroAgrement = $numeroAgrement; return $this; }

    public function getDateCreation(): ?\DateTimeInterface { return $this->dateCreation; }
    public function setDateCreation(?\DateTimeInterface $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function getResponsable(): ?string { return $this->responsable; }
    public function setResponsable(?string $responsable): static { $this->responsable = $responsable; return $this; }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    // ═══════════════════════════════════════════
    // RELATION — Projets
    // ═══════════════════════════════════════════

    /** @return Collection<int, Projet> */
    public function getProjets(): Collection { return $this->projets; }

    public function addProjet(Projet $projet): static
    {
        if (!$this->projets->contains($projet)) {
            $this->projets->add($projet);
            $projet->setAssociation($this);
        }
        return $this;
    }

    public function removeProjet(Projet $projet): static
    {
        if ($this->projets->removeElement($projet)) {
            if ($projet->getAssociation() === $this) {
                $projet->setAssociation(null);
            }
        }
        return $this;
    }

    // ═══════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════

    /** Chemin du logo pour Twig */
    public function getLogoPath(): string
    {
        return $this->logo ? 'uploads/associations/' . $this->logo : '';
    }

    public function hasLogo(): bool
    {
        return $this->logo !== null && $this->logo !== '';
    }

    /** Nom d'affichage : sigle si existe, sinon nom complet */
    public function getDisplayName(): string
    {
        return $this->sigle ?: $this->nom;
    }

    /** Nombre de projets actifs */
    public function countProjetsActifs(): int
    {
        return $this->projets->filter(fn(Projet $p) => in_array($p->getStatut(), [
            Projet::STATUT_VALIDE,
            Projet::STATUT_EN_COURS,
        ]))->count();
    }

    public function __toString(): string
    {
        return $this->sigle ? $this->sigle . ' — ' . $this->nom : $this->nom;
    }
}
