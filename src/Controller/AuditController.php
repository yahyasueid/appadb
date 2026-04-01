<?php
// src/Controller/AuditController.php
// ─── JOURNAL D'AUDIT ───
// Admin uniquement

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/audit')]
#[IsGranted('ROLE_ADMIN')]
class AuditController extends AbstractController
{
    #[Route('', name: 'app_audit')]
    public function index(): Response
    {
        return $this->render('pages/audit/index.html.twig');
    }
}
