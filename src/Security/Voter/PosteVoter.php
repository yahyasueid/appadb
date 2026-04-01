<?php
// src/Security/Voter/PosteVoter.php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PosteVoter extends Voter
{
    public const VALIDER              = 'VALIDER';
    public const CREER_COMPTE         = 'CREER_COMPTE';
    public const GERER_CAISSE         = 'GERER_CAISSE';
    public const GERER_PROJETS        = 'GERER_PROJETS';
    public const GERER_PARRAINAGES    = 'GERER_PARRAINAGES';
    public const VALIDER_PROJETS      = 'VALIDER_PROJETS';
    public const VALIDER_PARRAINAGES  = 'VALIDER_PARRAINAGES';
    public const VOIR_AUDIT           = 'VOIR_AUDIT';
    public const VOIR_RAPPORTS        = 'VOIR_RAPPORTS';
    public const EXPORTER             = 'EXPORTER';
    public const GERER_PARAMETRES     = 'GERER_PARAMETRES';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::VALIDER, self::CREER_COMPTE, self::GERER_CAISSE,
            self::GERER_PROJETS, self::GERER_PARRAINAGES,
            self::VALIDER_PROJETS, self::VALIDER_PARRAINAGES,
            self::VOIR_AUDIT, self::VOIR_RAPPORTS,
            self::EXPORTER, self::GERER_PARAMETRES,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        return match ($attribute) {
            // Admin peut tout
            self::CREER_COMPTE     => $user->isAdmin(),
            self::GERER_PARAMETRES => $user->isAdmin(),
            self::VOIR_AUDIT       => $user->isAdmin() || $user->isDirecteurProjets() || $user->isDirecteurParrainages(),

            // Validation = Directeurs + Admin
            self::VALIDER          => $user->canValidate(),
            self::VALIDER_PROJETS  => $user->isAdmin() || $user->isDirecteurProjets(),
            self::VALIDER_PARRAINAGES => $user->isAdmin() || $user->isDirecteurParrainages(),

            // Modules
            self::GERER_PROJETS     => $user->canManageProjects(),
            self::GERER_PARRAINAGES => $user->canManageSponsorships(),
            self::GERER_CAISSE      => $user->canManageCash(),

            // Rapports & Export
            self::VOIR_RAPPORTS => $user->canViewReports(),
            self::EXPORTER      => $user->canExport(),

            default => false,
        };
    }
}
