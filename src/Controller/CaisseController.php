<?php
// src/Controller/CaisseController.php
// ─── Module CAISSE / FINANCE ───
// Comptable → opérations caisse
// Admin     → tout

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/caisse')]
#[IsGranted('ROLE_COMPTABLE')]
class CaisseController extends AbstractController
{
    // ═══ JOURNAL DE CAISSE ═══
    #[Route('', name: 'app_caisse')]
    public function index(): Response
    {
        return $this->render('pages/caisse/index.html.twig');
    }

    // ═══ ALIMENTATION (entrée d'argent) ═══
    #[Route('/alimentation', name: 'app_caisse_alimentation', methods: ['GET', 'POST'])]
    public function alimentation(): Response
    {
        return $this->render('pages/caisse/alimentation.html.twig');
    }

    // ═══ DÉPENSE (sortie d'argent) ═══
    #[Route('/depense', name: 'app_caisse_depense', methods: ['GET', 'POST'])]
    public function depense(): Response
    {
        return $this->render('pages/caisse/depense.html.twig');
    }

    // ═══ DÉTAIL OPÉRATION ═══
    #[Route('/{id}', name: 'app_caisse_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->render('pages/caisse/show.html.twig', ['id' => $id]);
    }
}
