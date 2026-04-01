<?php
// src/Controller/RapportController.php
// ─── RAPPORTS ───
// Accessible à tous les postes connectés

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/rapports')]
#[IsGranted('ROLE_USER')]
class RapportController extends AbstractController
{
    #[Route('', name: 'app_rapports')]
    public function index(): Response
    {
        return $this->render('pages/rapports/index.html.twig');
    }

    #[Route('/export/{type}', name: 'app_rapport_export', requirements: ['type' => 'pdf|word|excel'])]
    public function export(string $type): Response
    {
        // TODO: générer le fichier export
        return new Response('Export ' . $type);
    }
}
