<?php
// src/Controller/ProfileController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ProfileController — Gestion du profil utilisateur connecté.
 *
 * Regroupe toutes les actions liées au compte de l'utilisateur :
 *   - Affichage du profil
 *   - Modification des informations personnelles (nom, email, téléphone)
 *   - Changement de mot de passe (avec vérification de l'ancien)
 *   - Upload / suppression de la photo de profil
 *
 * Toutes les routes sont protégées par IS_AUTHENTICATED_FULLY,
 * ce qui exige une authentification fraîche (pas via "remember me").
 *
 * Préfixe de route : /profile  →  app_profile_*
 */
#[Route('/profile', name: 'app_profile')]
class ProfileController extends AbstractController
{
    /**
     * Injection des dépendances via le constructeur (autowiring Symfony).
     *
     * @param EntityManagerInterface      $em       Accès à la base de données (Doctrine)
     * @param UserPasswordHasherInterface $hasher   Hachage et vérification des mots de passe
     * @param SluggerInterface            $slugger  Noms de fichiers sûrs (sans espaces ni caractères spéciaux)
     * @param TranslatorInterface         $t        Traduction des messages flash (FR / AR / EN)
     */
    public function __construct(
        private EntityManagerInterface       $em,
        private UserPasswordHasherInterface  $hasher,
        private SluggerInterface             $slugger,
        private TranslatorInterface          $t,
    ) {}

    // ═══════════════════════════════════════════════════════════
    //  1. AFFICHAGE DU PROFIL
    //     GET /profile  →  app_profile_show
    // ═══════════════════════════════════════════════════════════

    /**
     * Affiche la page "Mon Profil" avec les 3 formulaires :
     *   - Informations personnelles
     *   - Changement de mot de passe
     *   - Upload de photo
     *
     * L'utilisateur courant est passé à la vue via {{ user }}.
     */
    #[Route('', name: '_show', methods: ['GET'])]
    public function show(): Response
    {
        // Exige une authentification pleine (pas via "remember me")
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        return $this->render('profile/show.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  2. MODIFIER LES INFORMATIONS PERSONNELLES
    //     POST /profile/edit  →  app_profile_edit
    // ═══════════════════════════════════════════════════════════

    /**
     * Traite le formulaire de modification des informations personnelles.
     *
     * Champs modifiables : prénom, nom, email, téléphone.
     * L'email doit être valide et unique dans la base de données.
     *
     * Sécurité : vérifie le token CSRF avant tout traitement.
     */
    #[Route('/edit', name: '_edit', methods: ['POST'])]
    public function edit(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user L'utilisateur actuellement connecté */
        $user = $this->getUser();

        // ── 1. Vérification du token CSRF ───────────────────────
        // Protège contre les attaques Cross-Site Request Forgery.
        // Le token est généré dans le template : {{ csrf_token('profile_edit') }}
        if (!$this->isCsrfTokenValid('profile_edit', $request->request->get('_token'))) {
            $this->addFlash('error', $this->t->trans('profile.flash_csrf_invalid'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 2. Récupération et nettoyage des champs ─────────────
        $prenom = trim($request->request->get('prenom', ''));
        $nom    = trim($request->request->get('nom', ''));
        $email  = trim($request->request->get('email', ''));
        $tel    = trim($request->request->get('telephone', ''));

        // ── 3. Validation : champs obligatoires ─────────────────
        if (empty($prenom) || empty($nom) || empty($email)) {
            $this->addFlash('error', $this->t->trans('profile.flash_fields_required'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 4. Validation : format email ────────────────────────
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', $this->t->trans('profile.flash_email_invalid'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 5. Unicité de l'email ────────────────────────────────
        // Vérifie qu'aucun autre compte n'utilise déjà cette adresse email.
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing && $existing->getId() !== $user->getId()) {
            $this->addFlash('error', $this->t->trans('profile.flash_email_taken'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 6. Mise à jour de l'entité ──────────────────────────
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setEmail($email);

        // setTelephone est optionnel selon la version de l'entité User
        if (method_exists($user, 'setTelephone')) {
            $user->setTelephone($tel ?: null);
        }

        $this->em->flush();

        $this->addFlash('success', $this->t->trans('profile.flash_info_updated'));
        return $this->redirectToRoute('app_profile_show');
    }

    // ═══════════════════════════════════════════════════════════
    //  3. CHANGER LE MOT DE PASSE
    //     POST /profile/password  →  app_profile_password
    // ═══════════════════════════════════════════════════════════

    /**
     * Traite le formulaire de changement de mot de passe.
     *
     * Étapes de validation :
     *   1. Token CSRF valide
     *   2. Ancien mot de passe correct (via le hasher Symfony)
     *   3. Nouveau mot de passe ≥ 8 caractères
     *   4. Confirmation identique au nouveau mot de passe
     *
     * Le nouveau mot de passe est haché avant enregistrement.
     */
    #[Route('/password', name: '_password', methods: ['POST'])]
    public function changePassword(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // ── 1. Vérification CSRF ─────────────────────────────────
        if (!$this->isCsrfTokenValid('profile_password', $request->request->get('_token'))) {
            $this->addFlash('error', $this->t->trans('profile.flash_csrf_invalid'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 2. Récupération des 3 champs ────────────────────────
        $current = $request->request->get('current_password', '');
        $new     = $request->request->get('new_password', '');
        $confirm = $request->request->get('confirm_password', '');

        // ── 3. Vérification de l'ancien mot de passe ────────────
        // isPasswordValid() compare la valeur fournie avec le hash stocké en base.
        if (!$this->hasher->isPasswordValid($user, $current)) {
            $this->addFlash('error', $this->t->trans('profile.flash_pw_current_wrong'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 4. Vérification de la longueur minimale ─────────────
        if (strlen($new) < 8) {
            $this->addFlash('error', $this->t->trans('profile.flash_pw_too_short'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 5. Vérification de la confirmation ──────────────────
        if ($new !== $confirm) {
            $this->addFlash('error', $this->t->trans('profile.flash_pw_mismatch'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 6. Hachage et sauvegarde ────────────────────────────
        // hashPassword() utilise l'algorithme configuré dans security.yaml
        $user->setPassword($this->hasher->hashPassword($user, $new));
        $this->em->flush();

        $this->addFlash('success', $this->t->trans('profile.flash_pw_updated'));
        return $this->redirectToRoute('app_profile_show');
    }

    // ═══════════════════════════════════════════════════════════
    //  4. UPLOAD DE LA PHOTO DE PROFIL
    //     POST /profile/photo  →  app_profile_photo
    // ═══════════════════════════════════════════════════════════

    /**
     * Traite l'upload d'une nouvelle photo de profil.
     *
     * Validations effectuées :
     *   - Token CSRF
     *   - Fichier présent
     *   - Type MIME : image/jpeg, image/png, image/webp, image/gif
     *   - Taille maximale : 3 Mo
     *
     * Comportement :
     *   - Supprime l'ancienne photo du disque si elle existe
     *   - Génère un nom de fichier unique et sécurisé
     *   - Crée le dossier public/uploads/users/ si absent
     *   - Met à jour l'entité User en base
     */
    #[Route('/photo', name: '_photo', methods: ['POST'])]
    public function uploadPhoto(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // ── 1. Vérification CSRF ─────────────────────────────────
        if (!$this->isCsrfTokenValid('profile_photo', $request->request->get('_token'))) {
            $this->addFlash('error', $this->t->trans('profile.flash_csrf_invalid'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 2. Vérification : fichier présent ───────────────────
        $file = $request->files->get('photo');
        if (!$file) {
            $this->addFlash('error', $this->t->trans('profile.flash_photo_missing'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 3. Vérification du type MIME ────────────────────────
        // getMimeType() lit les octets réels du fichier (plus fiable que l'extension).
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file->getMimeType(), $allowed)) {
            $this->addFlash('error', $this->t->trans('profile.flash_photo_format'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 4. Vérification de la taille (max 3 Mo) ─────────────
        if ($file->getSize() > 3 * 1024 * 1024) {
            $this->addFlash('error', $this->t->trans('profile.flash_photo_size'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 5. Suppression de l'ancienne photo ──────────────────
        // Suppression physique du fichier disque pour éviter l'accumulation.
        if ($user->hasPhoto()) {
            $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getPhotoPath();
            if (file_exists($oldPath)) {
                @unlink($oldPath); // @ pour ignorer silencieusement les erreurs FS
            }
        }

        // ── 6. Génération d'un nom de fichier sécurisé ──────────
        // slug() → chaîne ASCII sans espaces ni caractères dangereux
        // uniqid() → unicité même en cas d'uploads simultanés
        $ext      = $file->guessExtension() ?? 'jpg';
        $safeId   = $this->slugger->slug((string) $user->getId());
        $filename = 'avatar_' . $safeId . '_' . uniqid() . '.' . $ext;

        // ── 7. Déplacement du fichier ────────────────────────────
        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true); // Crée récursivement si le dossier est absent
        }
        $file->move($dir, $filename);

        // ── 8. Mise à jour de l'entité ──────────────────────────
        // setPhoto est optionnel selon la version de l'entité User
        if (method_exists($user, 'setPhoto')) {
            $user->setPhoto($filename);
        }

        $this->em->flush();

        $this->addFlash('success', $this->t->trans('profile.flash_photo_updated'));
        return $this->redirectToRoute('app_profile_show');
    }

    // ═══════════════════════════════════════════════════════════
    //  5. SUPPRIMER LA PHOTO DE PROFIL
    //     POST /profile/photo/delete  →  app_profile_photo_delete
    // ═══════════════════════════════════════════════════════════

    /**
     * Supprime la photo de profil de l'utilisateur connecté.
     *
     * - Supprime le fichier physique du disque
     * - Réinitialise le champ photo à null en base
     * - Action idempotente : ne fait rien si pas de photo
     */
    #[Route('/photo/delete', name: '_photo_delete', methods: ['POST'])]
    public function deletePhoto(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        // ── 1. Vérification CSRF ─────────────────────────────────
        if (!$this->isCsrfTokenValid('profile_photo_del', $request->request->get('_token'))) {
            $this->addFlash('error', $this->t->trans('profile.flash_csrf_invalid'));
            return $this->redirectToRoute('app_profile_show');
        }

        // ── 2. Suppression si photo existante ───────────────────
        if ($user->hasPhoto()) {
            // Suppression physique du fichier
            $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $user->getPhotoPath();
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }

            // Remise à null en base de données
            if (method_exists($user, 'setPhoto')) {
                $user->setPhoto(null);
            }

            $this->em->flush();
            $this->addFlash('success', $this->t->trans('profile.flash_photo_deleted'));
        }

        return $this->redirectToRoute('app_profile_show');
    }
}