<?php
// src/Controller/ParametreController.php
// ─── PARAMÈTRES SYSTÈME ───
// Admin uniquement

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parametres')]
#[IsGranted('ROLE_ADMIN')]
class ParametreController extends AbstractController
{
    #[Route('', name: 'app_parametres')]
    public function index(): Response
    {
        return $this->render('pages/parametres/index.html.twig');
    }

    #[Route('/sauvegarder', name: 'app_parametres_save', methods: ['POST'])]
    public function save(): Response
    {
        // TODO: sauvegarder
        $this->addFlash('success', 'Paramètres enregistrés.');
        return $this->redirectToRoute('app_parametres');
    }
}
