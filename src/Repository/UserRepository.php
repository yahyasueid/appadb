<?php
// src/Repository/UserRepository.php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = true')
            ->orderBy('u.poste', 'ASC')
            ->getQuery()->getResult();
    }

    public function findByPoste(string $poste): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.poste = :poste')
            ->andWhere('u.isActive = true')
            ->setParameter('poste', $poste)
            ->getQuery()->getResult();
    }

    public function findEquipeProjets(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.poste IN (:postes)')
            ->andWhere('u.isActive = true')
            ->setParameter('postes', [User::ROLE_DIRECTEUR_PROJETS, User::ROLE_EMPLOYE_PROJETS])
            ->getQuery()->getResult();
    }

    public function findEquipeParrainages(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.poste IN (:postes)')
            ->andWhere('u.isActive = true')
            ->setParameter('postes', [User::ROLE_DIRECTEUR_PARRAINAGES, User::ROLE_EMPLOYE_PARRAINAGES])
            ->getQuery()->getResult();
    }
}
