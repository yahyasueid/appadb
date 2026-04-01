<?php
// src/Controller/AssociationController.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  CRUD ASSOCIATIONS — ADB-MR                                  ║
// ║  Accès : ROLE_ADMIN uniquement                               ║
// ║  Routes :                                                    ║
// ║    GET    /association              → index                  ║
// ║    POST   /association/create       → create                 ║
// ║    PUT    /association/{id}/edit    → edit                   ║
// ║    DELETE /association/{id}/delete  → delete                 ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Controller;

use App\Entity\Association;
use App\Repository\AssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/association', name: 'app_association')]
#[IsGranted('ROLE_ADMIN')]
class AssociationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AssociationRepository  $repo,
        private readonly SluggerInterface       $slugger,
        private readonly TranslatorInterface    $translator,
    ) {}

    // ═══════════════════════════════════════════
    // INDEX — Liste de toutes les associations
    // GET /association
    // ═══════════════════════════════════════════

    #[Route('', name: '_index', methods: ['GET'])]
    public function index(): Response
    {
        $associations = $this->repo->findBy([], ['nom' => 'ASC']);

        return $this->render('association/index.html.twig', [
            'associations' => $associations,
        ]);
    }

    // ═══════════════════════════════════════════
    // CREATE — Créer une nouvelle association
    // POST /association/create
    // ═══════════════════════════════════════════

    #[Route('/create', name: '_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('assoc_form', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.csrf_invalid'));
            return $this->redirectToRoute('app_association_index');
        }

        $association = new Association();
        $this->hydrateFromRequest($association, $request);

        // Gestion du logo
        $logoFile = $request->files->get('logoFile');
        if ($logoFile) {
            $filename = $this->uploadLogo($logoFile);
            if ($filename) {
                $association->setLogo($filename);
            }
        }

        $this->em->persist($association);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('flash.assoc_created', [
            '%nom%' => $association->getDisplayName(),
        ]));

        return $this->redirectToRoute('app_association_index');
    }

    // ═══════════════════════════════════════════
    // EDIT — Modifier une association existante
    // POST /association/{id}/edit  (_method=PUT)
    // ═══════════════════════════════════════════

    #[Route('/{id}/edit', name: '_edit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $association = $this->repo->find($id);

        if (!$association) {
            $this->addFlash('error', $this->translator->trans('flash.assoc_not_found'));
            return $this->redirectToRoute('app_association_index');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('assoc_form', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.csrf_invalid'));
            return $this->redirectToRoute('app_association_index');
        }

        $this->hydrateFromRequest($association, $request);

        // Nouveau logo (optionnel — on garde l'ancien si aucun nouveau)
        $logoFile = $request->files->get('logoFile');
        if ($logoFile) {
            // Supprimer l'ancien logo si existant
            $this->deleteLogo($association->getLogo());

            $filename = $this->uploadLogo($logoFile);
            if ($filename) {
                $association->setLogo($filename);
            }
        }

        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('flash.assoc_updated', [
            '%nom%' => $association->getDisplayName(),
        ]));

        return $this->redirectToRoute('app_association_index');
    }

    // ═══════════════════════════════════════════
    // DELETE — Supprimer une association
    // POST /association/{id}/delete  (_method=DELETE)
    // ═══════════════════════════════════════════

    #[Route('/{id}/delete', name: '_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        $association = $this->repo->find($id);

        if (!$association) {
            $this->addFlash('error', $this->translator->trans('flash.assoc_not_found'));
            return $this->redirectToRoute('app_association_index');
        }

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete_association', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('flash.csrf_invalid'));
            return $this->redirectToRoute('app_association_index');
        }

        // Vérifier si des projets sont liés (protection optionnelle)
        if ($association->getProjets()->count() > 0) {
            $this->addFlash('error', $this->translator->trans('flash.assoc_has_projects', [
                '%count%' => $association->getProjets()->count(),
            ]));
            return $this->redirectToRoute('app_association_index');
        }

        $nom = $association->getDisplayName();

        // Supprimer le logo du disque
        $this->deleteLogo($association->getLogo());

        $this->em->remove($association);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('flash.assoc_deleted', [
            '%nom%' => $nom,
        ]));

        return $this->redirectToRoute('app_association_index');
    }

    // ═══════════════════════════════════════════
    // MÉTHODES PRIVÉES
    // ═══════════════════════════════════════════

    /**
     * Hydrate l'entité Association depuis les données POST
     */
    private function hydrateFromRequest(Association $association, Request $request): void
    {
        $association
            ->setNom(trim($request->request->get('nom', '')))
            ->setNomAr(trim($request->request->get('nomAr', '')) ?: null)
            ->setSigle(trim($request->request->get('sigle', '')) ?: null)
            ->setAdresse(trim($request->request->get('adresse', '')) ?: null)
            ->setVille(trim($request->request->get('ville', '')) ?: null)
            ->setPays(trim($request->request->get('pays', 'Mauritanie')))
            ->setTelephone(trim($request->request->get('telephone', '')) ?: null)
            ->setEmail(trim($request->request->get('email', '')) ?: null)
            ->setSiteWeb(trim($request->request->get('siteWeb', '')) ?: null)
            ->setNumeroAgrement(trim($request->request->get('numeroAgrement', '')) ?: null)
            ->setResponsable(trim($request->request->get('responsable', '')) ?: null)
            ->setIsActive($request->request->has('isActive'));

        // Date de création
        $dateStr = trim($request->request->get('dateCreation', ''));
        if ($dateStr) {
            try {
                $association->setDateCreation(new \DateTime($dateStr));
            } catch (\Exception) {
                $association->setDateCreation(null);
            }
        } else {
            $association->setDateCreation(null);
        }
    }

    /**
     * Upload le logo vers public/uploads/associations/
     * Retourne le nom du fichier ou null en cas d'échec
     */
    private function uploadLogo(mixed $logoFile): ?string
    {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/associations';

        // Créer le répertoire si nécessaire
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $extension        = $logoFile->guessExtension() ?: 'png';
        $newFilename      = $safeFilename . '-' . uniqid() . '.' . $extension;

        // Vérifier la taille (max 2 Mo)
        if ($logoFile->getSize() > 2 * 1024 * 1024) {
            $this->addFlash('error', $this->translator->trans('flash.logo_too_large'));
            return null;
        }

        // Vérifier le type MIME
        $allowedMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml', 'image/webp'];
        if (!in_array($logoFile->getMimeType(), $allowedMimes)) {
            $this->addFlash('error', $this->translator->trans('flash.logo_invalid_type'));
            return null;
        }

        try {
            $logoFile->move($uploadDir, $newFilename);
            return $newFilename;
        } catch (FileException) {
            $this->addFlash('error', $this->translator->trans('flash.logo_upload_failed'));
            return null;
        }
    }

    /**
     * Supprime le fichier logo du disque si il existe
     */
    private function deleteLogo(?string $logo): void
    {
        if (!$logo) {
            return;
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/associations/' . $logo;

        if (file_exists($path)) {
            unlink($path);
        }
    }
}