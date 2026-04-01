<?php
// src/Controller/UtilisateurController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepo,
        private TranslatorInterface $translator,
    ) {}

    /** @return User */
    protected function getCurrentUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user;
    }

    // ═══════════════════════════════════════════
    // LISTE
    // ═══════════════════════════════════════════
    #[Route('', name: 'app_utilisateurs')]
    public function index(Request $request): Response
    {
        $utilisateurs = $this->userRepo->findBy([], ['poste' => 'ASC', 'nom' => 'ASC']);
        $stats = $this->buildStats($utilisateurs);

        $newForm = $this->createForm(UserType::class, new User(), [
            'is_edit' => false,
            'action'  => $this->generateUrl('app_utilisateur_new'),
        ]);

        $editForms = [];
        foreach ($utilisateurs as $u) {
            $editForms[$u->getId()] = $this->createForm(UserType::class, $u, [
                'is_edit' => true,
                'action'  => $this->generateUrl('app_utilisateur_edit', ['id' => $u->getId()]),
            ])->createView();
        }

        return $this->render('pages/utilisateurs/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'stats'        => $stats,
            'total'        => count($utilisateurs),
            'actifs'       => count(array_filter($utilisateurs, fn(User $u) => $u->isActive())),
            'newForm'      => $newForm->createView(),
            'editForms'    => $editForms,
        ]);
    }

    // ═══════════════════════════════════════════
    // CRÉER
    // ═══════════════════════════════════════════
    #[Route('/nouveau', name: 'app_utilisateur_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles([$user->getPoste()]);
            $user->setIsActive(true);
            $this->handlePhoto($form, $user);

            $this->em->persist($user);
            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('utilisateurs.flash_created', ['%name%' => $user->getFullName()]));
            return $this->redirectToRoute('app_utilisateurs');
        }

        // Erreurs → ré-afficher avec modale ouverte
        return $this->renderIndexWithError($form, null, 'new');
    }

    // ═══════════════════════════════════════════
    // MODIFIER
    // ═══════════════════════════════════════════
    #[Route('/{id}/modifier', name: 'app_utilisateur_edit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) { throw $this->createNotFoundException(); }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }
            $user->setRoles([$user->getPoste()]);

            if ($form->get('removePhoto')->getData()) {
                $this->removePhoto($user);
            } else {
                $this->handlePhoto($form, $user);
            }

            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('utilisateurs.flash_updated', ['%name%' => $user->getFullName()]));
            return $this->redirectToRoute('app_utilisateurs');
        }

        return $this->renderIndexWithError(null, $form, 'edit-' . $id, $id);
    }

    // ═══════════════════════════════════════════
    // TOGGLE
    // ═══════════════════════════════════════════
    #[Route('/{id}/toggle', name: 'app_utilisateur_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(int $id): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) { throw $this->createNotFoundException(); }

        if ($user->getId() === $this->getCurrentUser()->getId()) {
            $this->addFlash('error', $this->translator->trans('utilisateurs.flash_self_error'));
            return $this->redirectToRoute('app_utilisateurs');
        }

        $user->setIsActive(!$user->isActive());
        $this->em->flush();

        $key = $user->isActive() ? 'utilisateurs.flash_toggled_on' : 'utilisateurs.flash_toggled_off';
        $this->addFlash('success', $this->translator->trans($key, ['%name%' => $user->getFullName()]));

        return $this->redirectToRoute('app_utilisateurs');
    }

    // ═══════════════════════════════════════════
    // RESET MOT DE PASSE
    // ═══════════════════════════════════════════
    #[Route('/{id}/reset-password', name: 'app_utilisateur_reset_password', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function resetPassword(int $id): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) { throw $this->createNotFoundException(); }

        $posteLabel = str_replace(' ', '', User::POSTES[$user->getPoste()]['label_fr'] ?? 'User');
        $tempPassword = $posteLabel . '@' . date('Y');

        $user->setPassword($this->passwordHasher->hashPassword($user, $tempPassword));
        $this->em->flush();

        $this->addFlash('success',
            $this->translator->trans('utilisateurs.flash_reset', ['%name%' => $user->getFullName()])
            . ' → ' . $tempPassword
        );

        return $this->redirectToRoute('app_utilisateurs');
    }

    // ═══════════════════════════════════════════
    // SUPPRIMER (soft delete)
    // ═══════════════════════════════════════════
    #[Route('/{id}/supprimer', name: 'app_utilisateur_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->userRepo->find($id);
        if (!$user) { throw $this->createNotFoundException(); }

        if ($user->getId() === $this->getCurrentUser()->getId()) {
            $this->addFlash('error', $this->translator->trans('utilisateurs.flash_self_error'));
            return $this->redirectToRoute('app_utilisateurs');
        }

        if (!$this->isCsrfTokenValid('delete-user-' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('utilisateurs.flash_csrf_error'));
            return $this->redirectToRoute('app_utilisateurs');
        }

        $this->removePhoto($user);
        $user->setIsActive(false);
        $this->em->flush();

        $this->addFlash('warning', $this->translator->trans('utilisateurs.flash_deleted', ['%name%' => $user->getFullName()]));
        return $this->redirectToRoute('app_utilisateurs');
    }

    // ═══════════════════════════════════════════
    // MÉTHODES PRIVÉES
    // ═══════════════════════════════════════════

    /**
     * Re-render la page avec la modale ouverte en cas d'erreur de formulaire
     */
    private function renderIndexWithError(?object $newFormWithErrors, ?object $editFormWithErrors, string $openModal, ?int $editId = null): Response
    {
        $utilisateurs = $this->userRepo->findBy([], ['poste' => 'ASC', 'nom' => 'ASC']);
        $stats = $this->buildStats($utilisateurs);

        // New form
        $newForm = $newFormWithErrors
            ? $newFormWithErrors->createView()
            : $this->createForm(UserType::class, new User(), [
                'is_edit' => false,
                'action'  => $this->generateUrl('app_utilisateur_new'),
            ])->createView();

        // Edit forms
        $editForms = [];
        foreach ($utilisateurs as $u) {
            if ($editFormWithErrors && $u->getId() === $editId) {
                $editForms[$u->getId()] = $editFormWithErrors->createView();
            } else {
                $editForms[$u->getId()] = $this->createForm(UserType::class, $u, [
                    'is_edit' => true,
                    'action'  => $this->generateUrl('app_utilisateur_edit', ['id' => $u->getId()]),
                ])->createView();
            }
        }

        return $this->render('pages/utilisateurs/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'stats'        => $stats,
            'total'        => count($utilisateurs),
            'actifs'       => count(array_filter($utilisateurs, fn(User $u) => $u->isActive())),
            'newForm'      => $newForm,
            'editForms'    => $editForms,
            'openModal'    => $openModal,
        ]);
    }

    private function handlePhoto($form, User $user): void
    {
        /** @var UploadedFile|null $file */
        $file = $form->get('photoFile')->getData();
        if (!$file) { return; }

        $this->removePhoto($user);

        $extension = $file->guessExtension() ?: 'jpg';
        $filename = sprintf('avatar_%d_%s.%s', $user->getId() ?? 0, uniqid(), $extension);

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

        $file->move($uploadDir, $filename);
        $user->setPhoto($filename);
    }

    private function removePhoto(User $user): void
    {
        if (!$user->hasPhoto()) { return; }
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars/' . $user->getPhoto();
        if (file_exists($path)) { unlink($path); }
        $user->setPhoto(null);
    }

    private function buildStats(array $utilisateurs): array
    {
        // Mapping role → clé de traduction (poste.admin, poste.dir_projets, etc.)
        $transKeys = [
            'ROLE_ADMIN'                => 'poste.admin',
            'ROLE_DIRECTEUR_PROJETS'    => 'poste.dir_projets',
            'ROLE_EMPLOYE_PROJETS'      => 'poste.emp_projets',
            'ROLE_DIRECTEUR_PARRAINAGES'=> 'poste.dir_parrainages',
            'ROLE_EMPLOYE_PARRAINAGES'  => 'poste.emp_parrainages',
            'ROLE_COMPTABLE'            => 'poste.comptable',
        ];

        $stats = [];
        foreach (User::POSTES as $role => $config) {
            $stats[$role] = [
                'label'     => $config['label_fr'],
                'trans_key' => $transKeys[$role] ?? $config['label_fr'],
                'couleur'   => $config['couleur'],
                'icone'     => $config['icone'],
                'count'     => count(array_filter($utilisateurs, fn(User $u) => $u->getPoste() === $role)),
            ];
        }
        return $stats;
    }
}
