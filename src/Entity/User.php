<?php
// src/Entity/User.php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // ╔══════════════════════════════════════════════════╗
    // ║  LES 6 POSTES DE L'ASSOCIATION                   ║
    // ╚══════════════════════════════════════════════════╝

    public const ROLE_ADMIN                = 'ROLE_ADMIN';
    public const ROLE_DIRECTEUR_PROJETS    = 'ROLE_DIRECTEUR_PROJETS';
    public const ROLE_EMPLOYE_PROJETS      = 'ROLE_EMPLOYE_PROJETS';
    public const ROLE_DIRECTEUR_PARRAINAGES = 'ROLE_DIRECTEUR_PARRAINAGES';
    public const ROLE_EMPLOYE_PARRAINAGES  = 'ROLE_EMPLOYE_PARRAINAGES';
    public const ROLE_COMPTABLE            = 'ROLE_COMPTABLE';

    // ╔══════════════════════════════════════════════════╗
    // ║  CONFIGURATION DE CHAQUE POSTE                   ║
    // ╚══════════════════════════════════════════════════╝

    public const POSTES = [

        self::ROLE_ADMIN => [
            'label_fr'           => 'Administrateur Général',
            'label_ar'           => 'المدير العام',
            'label_en'           => 'General Administrator',
            'droits_fr'          => 'Accès complet à tout le système',
            'droits_ar'          => 'وصول كامل لكل النظام',
            'droits_en'          => 'Full access to the entire system',
            'peut_valider'       => true,
            'peut_creer_compte'  => true,
            'peut_gerer_caisse'  => true,
            'peut_gerer_projets' => true,
            'peut_gerer_parrainages' => true,
            'peut_voir_audit'    => true,
            'peut_voir_rapports' => true,
            'peut_exporter'      => true,
            'peut_parametres'    => true,
            'icone'              => 'bi-shield-lock-fill',
            'couleur'            => '#14532D',
        ],

        self::ROLE_DIRECTEUR_PROJETS => [
            'label_fr'           => 'Directeur des Projets',
            'label_ar'           => 'مدير المشاريع',
            'label_en'           => 'Project Director',
            'droits_fr'          => 'Gestion complète des projets + validation',
            'droits_ar'          => 'إدارة كاملة للمشاريع + مصادقة',
            'droits_en'          => 'Full management of projects + validation',
            'peut_valider'       => true,
            'peut_creer_compte'  => false,
            'peut_gerer_caisse'  => false,
            'peut_gerer_projets' => true,
            'peut_gerer_parrainages' => false,
            'peut_voir_audit'    => true,
            'peut_voir_rapports' => true,
            'peut_exporter'      => true,
            'peut_parametres'    => false,
            'icone'              => 'bi-kanban-fill',
            'couleur'            => '#2E7D32',
        ],

        self::ROLE_EMPLOYE_PROJETS => [
            'label_fr'           => 'Employé Projets',
            'label_ar'           => 'موظف المشاريع',
            'label_en'           => 'Project Employee',
            'droits_fr'          => 'Saisie et suivi des projets — Sans validation',
            'droits_ar'          => 'إدخال ومتابعة المشاريع — بدون مصادقة',
            'droits_en'          => 'Entry and monitoring of projects — Without validation',
            'peut_valider'       => false,
            'peut_creer_compte'  => false,
            'peut_gerer_caisse'  => false,
            'peut_gerer_projets' => true,
            'peut_gerer_parrainages' => false,
            'peut_voir_audit'    => false,
            'peut_voir_rapports' => true,
            'peut_exporter'      => true,
            'peut_parametres'    => false,
            'icone'              => 'bi-kanban',
            'couleur'            => '#4CAF50',
        ],

        self::ROLE_DIRECTEUR_PARRAINAGES => [
            'label_fr'           => 'Directeur des Parrainages',
            'label_ar'           => 'مدير الكفالات',
            'label_en'           => 'Sponsorship Director',
            'droits_fr'          => 'Gestion complète des parrainages + validation',
            'droits_ar'          => 'إدارة كاملة للكفالات + مصادقة',
            'droits_en'          => 'Full management of sponsorships + validation',
            'peut_valider'       => true,
            'peut_creer_compte'  => false,
            'peut_gerer_caisse'  => false,
            'peut_gerer_projets' => false,
            'peut_gerer_parrainages' => true,
            'peut_voir_audit'    => true,
            'peut_voir_rapports' => true,
            'peut_exporter'      => true,
            'peut_parametres'    => false,
            'icone'              => 'bi-people-fill',
            'couleur'            => '#0288D1',
        ],

        self::ROLE_EMPLOYE_PARRAINAGES => [
            'label_fr'           => 'Employé Parrainages',
            'label_ar'           => 'موظف الكفالات',
            'label_en'           => 'Sponsorship Employee',
            'droits_fr'          => 'Saisie et suivi des parrainages — Sans validation',
            'droits_ar'          => 'إدخال ومتابعة الكفالات — بدون مصادقة',
            'droits_en'          => 'Entry and monitoring of sponsorships — Without validation',
            'peut_valider'       => false,
            'peut_creer_compte'  => false,
            'peut_gerer_caisse'  => false,
            'peut_gerer_projets' => false,
            'peut_gerer_parrainages' => true,
            'peut_voir_audit'    => false,
            'peut_voir_rapports' => true,
            'peut_exporter'      => true,
            'peut_parametres'    => false,
            'icone'              => 'bi-people',
            'couleur'            => '#03A9F4',
        ],
//        ROLE_BENEFICIAIRE => [
//        ],
        self::ROLE_COMPTABLE => [
            'label_fr'           => 'Comptable',
            'label_ar'           => 'المحاسب',
            'label_en'           => 'Accountant',
            'droits_fr'          => 'Comptabilité, caisse, rapports financiers',
            'droits_ar'          => 'المحاسبة، الصندوق، التقارير المالية',
            'droits_en'          => 'Accounting, cash management, financial reports',
            'peut_valider'       => false,
            'peut_creer_compte'  => false,
            'peut_gerer_caisse'  => true,
            'peut_gerer_projets' => false,
            'peut_gerer_parrainages' => false,
            'peut_voir_audit'    => false,
            'peut_voir_rapports' => true,
            'peut_exporter'      => true,
            'peut_parametres'    => false,
            'icone'              => 'bi-calculator-fill',
            'couleur'            => '#D4A017',
        ],
    ];

    // ===================================
    // PROPRIÉTÉS
    // ===================================

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 50)]
    private ?string $poste = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    // ╔══════════════════════════════════════════════════╗
    // ║  PHOTO DE PROFIL                                 ║
    // ║  Stocke le nom du fichier (ex: "avatar_12.jpg") ║
    // ║  Fichier physique → public/uploads/avatars/      ║
    // ╚══════════════════════════════════════════════════╝
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isActive = true;
    }

    // ===================================
    // GETTERS / SETTERS
    // ===================================

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }
    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        if ($this->poste) { $roles[] = $this->poste; }
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }
    public function getFullName(): string { return $this->prenom . ' ' . $this->nom; }
    public function getInitials(): string { return mb_strtoupper(mb_substr($this->prenom ?? '', 0, 1) . mb_substr($this->nom ?? '', 0, 1)); }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): static { $this->telephone = $telephone; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }
    public function getLastLogin(): ?\DateTimeInterface { return $this->lastLogin; }
    public function setLastLogin(?\DateTimeInterface $lastLogin): static { $this->lastLogin = $lastLogin; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    // ===================================
    // PHOTO DE PROFIL
    // ===================================

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * Chemin relatif pour Twig : {{ asset(app.user.photoPath) }}
     */
    public function getPhotoPath(): string
    {
        return $this->photo
            ? 'uploads/avatars/' . $this->photo
            : '';
    }

    /**
     * L'utilisateur a-t-il une photo ?
     */
    public function hasPhoto(): bool
    {
        return $this->photo !== null && $this->photo !== '';
    }

    /**
     * URL d'affichage : photo si elle existe, sinon avatar initiales
     * Usage dans Twig : {{ app.user.avatarDisplay }}
     */
    public function getAvatarDisplay(): string
    {
        if ($this->hasPhoto()) {
            return 'uploads/avatars/' . $this->photo;
        }
        // Retourne vide → le template utilise les initiales
        return '';
    }

    // ===================================
    // POSTE
    // ===================================

    public function getPoste(): ?string { return $this->poste; }

    public function setPoste(string $poste): static
    {
        if (!array_key_exists($poste, self::POSTES)) {
            throw new \InvalidArgumentException(
                sprintf('Poste invalide: "%s". Postes: %s', $poste, implode(', ', array_keys(self::POSTES)))
            );
        }
        $this->poste = $poste;
        return $this;
    }

    public function getPosteLabel(): string { return self::POSTES[$this->poste]['label_fr'] ?? 'Inconnu'; }
    public function getPosteLabelAr(): string { return self::POSTES[$this->poste]['label_ar'] ?? 'غير معروف'; }
    public function getDroitsLabel(): string { return self::POSTES[$this->poste]['droits_fr'] ?? ''; }
    public function getPosteIcone(): string { return self::POSTES[$this->poste]['icone'] ?? 'bi-person'; }
    public function getPosteCouleur(): string { return self::POSTES[$this->poste]['couleur'] ?? '#666'; }

    // Permet d'obtenir un tableau [ "Admin" => "ROLE_ADMIN", "Directeur Projets" => "ROLE_DIRECTEUR_PROJETS", ... ]
    // public static function getPostesChoices(): array
    // {
    //     $choices = [];
    //     foreach (self::POSTES as $role => $config) {

    //         $choices[$config['label_fr']] = $role;
    //     }
    //     return $choices;
    // }

    // APRES (clés de traduction — Symfony traduit automatiquement)
public static function getPostesChoices(): array
{
    return [
        'poste.admin'           => self::ROLE_ADMIN,
        'poste.dir_projets'     => self::ROLE_DIRECTEUR_PROJETS,
        'poste.emp_projets'     => self::ROLE_EMPLOYE_PROJETS,
        'poste.dir_parrainages' => self::ROLE_DIRECTEUR_PARRAINAGES,
        'poste.emp_parrainages' => self::ROLE_EMPLOYE_PARRAINAGES,
        'poste.comptable'       => self::ROLE_COMPTABLE,
    ];
}

    // ===================================
    // VÉRIFICATIONS PAR POSTE
    // ===================================

    public function isAdmin(): bool                 { return $this->poste === self::ROLE_ADMIN; }
    public function isDirecteurProjets(): bool       { return $this->poste === self::ROLE_DIRECTEUR_PROJETS; }
    public function isEmployeProjets(): bool         { return $this->poste === self::ROLE_EMPLOYE_PROJETS; }
    public function isDirecteurParrainages(): bool   { return $this->poste === self::ROLE_DIRECTEUR_PARRAINAGES; }
    public function isEmployeParrainages(): bool     { return $this->poste === self::ROLE_EMPLOYE_PARRAINAGES; }
    public function isComptable(): bool              { return $this->poste === self::ROLE_COMPTABLE; }

    // ===================================
    // PERMISSIONS (lues depuis POSTES)
    // ===================================

    public function canValidate(): bool          { return self::POSTES[$this->poste]['peut_valider'] ?? false; }
    public function canCreateAccounts(): bool     { return self::POSTES[$this->poste]['peut_creer_compte'] ?? false; }
    public function canManageCash(): bool         { return self::POSTES[$this->poste]['peut_gerer_caisse'] ?? false; }
    public function canManageProjects(): bool     { return self::POSTES[$this->poste]['peut_gerer_projets'] ?? false; }
    public function canManageSponsorships(): bool { return self::POSTES[$this->poste]['peut_gerer_parrainages'] ?? false; }
    public function canViewAudit(): bool          { return self::POSTES[$this->poste]['peut_voir_audit'] ?? false; }
    public function canViewReports(): bool        { return self::POSTES[$this->poste]['peut_voir_rapports'] ?? false; }
    public function canExport(): bool             { return self::POSTES[$this->poste]['peut_exporter'] ?? false; }
    public function canManageSettings(): bool     { return self::POSTES[$this->poste]['peut_parametres'] ?? false; }

    public function __toString(): string { return $this->getFullName() . ' (' . $this->getPosteLabel() . ')'; }
}