<?php
// src/DataFixtures/UserFixtures.php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $comptes = [
            [
                'email'     => 'admin@association.mr',
                'nom'       => 'Ould Ahmed',
                'prenom'    => 'Mohamed',
                'poste'     => User::ROLE_ADMIN,
                'telephone' => '+222 36 12 34 56',
                'password'  => 'Admin@2026',
            ],
            [
                'email'     => 'dir.projets@association.mr',
                'nom'       => 'Ould Cheikh',
                'prenom'    => 'Ahmed',
                'poste'     => User::ROLE_DIRECTEUR_PROJETS,
                'telephone' => '+222 36 98 76 54',
                'password'  => 'DirProjets@2026',
            ],
            [
                'email'     => 'emp.projets@association.mr',
                'nom'       => 'Mint Mohamed',
                'prenom'    => 'Aissata',
                'poste'     => User::ROLE_EMPLOYE_PROJETS,
                'telephone' => '+222 46 11 22 33',
                'password'  => 'EmpProjets@2026',
            ],
            [
                'email'     => 'dir.parrainages@association.mr',
                'nom'       => 'Ba',
                'prenom'    => 'Oumar',
                'poste'     => User::ROLE_DIRECTEUR_PARRAINAGES,
                'telephone' => '+222 22 44 55 66',
                'password'  => 'DirParrain@2026',
            ],
            [
                'email'     => 'emp.parrainages@association.mr',
                'nom'       => 'Mint Sidi',
                'prenom'    => 'Fatima',
                'poste'     => User::ROLE_EMPLOYE_PARRAINAGES,
                'telephone' => '+222 46 78 90 12',
                'password'  => 'EmpParrain@2026',
            ],
            [
                'email'     => 'comptable@association.mr',
                'nom'       => 'Diop',
                'prenom'    => 'Ousmane',
                'poste'     => User::ROLE_COMPTABLE,
                'telephone' => '+222 22 11 22 33',
                'password'  => 'Comptable@2026',
            ],
        ];

        foreach ($comptes as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setNom($data['nom']);
            $user->setPrenom($data['prenom']);
            $user->setPoste($data['poste']);
            $user->setTelephone($data['telephone']);
            $user->setRoles([$data['poste']]);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );
            $user->setIsActive(true);
            $manager->persist($user);
        }

        $manager->flush();

        echo "\n╔══════════════════════════════════════════════════════════╗\n";
        echo "║  6 COMPTES CRÉÉS                                        ║\n";
        echo "╠══════════════════════════════════════════════════════════╣\n";
        foreach ($comptes as $c) {
            $label = User::POSTES[$c['poste']]['label_fr'];
            echo "║  " . str_pad($label, 28) . " | " . str_pad($c['email'], 36) . "║\n";
        }
        echo "╚══════════════════════════════════════════════════════════╝\n\n";
    }
}
