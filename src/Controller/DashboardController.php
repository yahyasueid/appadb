<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    /**
     * TABLEAU DE BORD
     * Accessible à tous les utilisateurs connectés
     * Le contenu s'adapte selon le poste
     */
    #[Route('/', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('pages/dashboard/index.html.twig', [
            'user' => $user,
            // Les stats seront chargées depuis la BDD plus tard
        ]);
    }
}
