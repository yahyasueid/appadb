<?php
// src/Controller/ParrainageController.php
// ─── Module PARRAINAGES ───
// Employé Parrainages → saisie, liste, détail
// Dir. Parrainages    → + validation
// Admin               → tout

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parrainages')]
#[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
class ParrainageController extends AbstractController
{
    // ═══ LISTE DES PARRAINAGES ═══
    #[Route('', name: 'app_parrainages')]
    public function index(): Response
    {
        return $this->render('pages/parrainages/index.html.twig');
    }

    // ═══ NOUVEAU BÉNÉFICIAIRE ═══
    #[Route('/nouveau', name: 'app_parrainage_new')]
    public function new(): Response
    {
        return $this->render('pages/parrainages/new.html.twig');
    }

    // ═══ DÉTAIL BÉNÉFICIAIRE ═══
    #[Route('/{id}', name: 'app_parrainage_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        return $this->render('pages/parrainages/show.html.twig', ['id' => $id]);
    }

    // ═══ MODIFIER BÉNÉFICIAIRE ═══
    #[Route('/{id}/modifier', name: 'app_parrainage_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id): Response
    {
        return $this->render('pages/parrainages/edit.html.twig', ['id' => $id]);
    }

    // ═══ RAPPORTS (social, scolaire, financier) ═══
    #[Route('/{id}/rapport-social', name: 'app_parrainage_rapport_social', requirements: ['id' => '\d+'])]
    public function rapportSocial(int $id): Response
    {
        return $this->render('pages/parrainages/rapport_social.html.twig', ['id' => $id]);
    }

    #[Route('/{id}/rapport-scolaire', name: 'app_parrainage_rapport_scolaire', requirements: ['id' => '\d+'])]
    public function rapportScolaire(int $id): Response
    {
        return $this->render('pages/parrainages/rapport_scolaire.html.twig', ['id' => $id]);
    }

    #[Route('/{id}/rapport-financier', name: 'app_parrainage_rapport_financier', requirements: ['id' => '\d+'])]
    public function rapportFinancier(int $id): Response
    {
        return $this->render('pages/parrainages/rapport_financier.html.twig', ['id' => $id]);
    }

    // ═══ VALIDATION → Dir. Parrainages + Admin ═══
    #[Route('/validation', name: 'app_parrainages_validation')]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function validation(): Response
    {
        return $this->render('pages/parrainages/validation.html.twig');
    }

    #[Route('/{id}/valider', name: 'app_parrainage_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function valider(int $id): Response
    {
        $this->addFlash('success', 'Parrainage validé avec succès.');
        return $this->redirectToRoute('app_parrainages_validation');
    }

    #[Route('/{id}/rejeter', name: 'app_parrainage_rejeter', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function rejeter(int $id): Response
    {
        $this->addFlash('warning', 'Parrainage rejeté.');
        return $this->redirectToRoute('app_parrainages_validation');
    }
}
