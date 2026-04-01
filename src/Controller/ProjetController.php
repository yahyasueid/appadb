<?php
// src/Controller/ProjetController.php
// ╔══════════════════════════════════════════════════════════════════════╗
// ║  GESTION PROJETS — ADB-MR                                            ║
// ║                                                                      ║
// ║  Accès : ROLE_ADMIN sur tout le contrôleur                           ║
// ║                                                                      ║
// ║  Logique _from (champ caché dans chaque formulaire POST) :           ║
// ║    _from=show  → redirige vers app_projet_show après l'action        ║
// ║    _from=index → redirige vers app_projets (défaut)                  ║
// ║                                                                      ║
// ║  Différences index vs show prises en compte :                        ║
// ║                                                                      ║
// ║  ① edit() gère statut + progression (formulaire index les fusionne) ║
// ║    statut()  reste dédié à la modale statut du show                  ║
// ║                                                                      ║
// ║  ② hydrate() distingue CREATE (dateContrat toujours présent)         ║
// ║    et EDIT (JS index ne renvoie pas les dates → mise à jour          ║
// ║    conditionnelle pour éviter d'écraser avec une valeur vide)        ║
// ║                                                                      ║
// ║  ③ addPhoto() enregistre aussi la taille du fichier (setTaille)      ║
// ║    pour que getTailleFormatee() fonctionne dans les deux templates    ║
// ║                                                                      ║
// ║  ④ show() passe montantRestant en variable Twig distincte            ║
// ║    → utilisé par max= du champ montant dans la modale paiement       ║
// ╚══════════════════════════════════════════════════════════════════════╝

namespace App\Controller;

use App\Entity\Association;
use App\Entity\Projet;
use App\Entity\ProjetFichier;
use App\Entity\ProjetPaiement;
use App\Entity\ProjetPhoto;
use App\Entity\ProjetVideo;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_DIRECTEUR_PROJETS')]
class ProjetController extends AbstractController
{
    // ─────────────────────────────────────────────────────────────────
    // Injection des services par le constructeur (Symfony DI)
    //   $em          → EntityManager : persist / flush / find / remove
    //   $projetRepo  → Repository custom avec requêtes DQL dédiées
    //   $slugger     → Génère des noms de fichiers sûrs (ASCII/URL)
    //   $translator  → Traductions i18n FR / AR / EN
    // ─────────────────────────────────────────────────────────────────
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProjetRepository       $projetRepo,
        private readonly SluggerInterface       $slugger,
        private readonly TranslatorInterface    $translator,
    ) {}

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  INDEX  ·  GET /projets
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Variables passées au template :
    //    associations          → <select> modale création
    //    projetsParAssociation → [assoc_id => [Projet, ...]] pour les sections
    //    totalProjets / enCours / termines / totalBudget → chips dashboard
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projets', name: 'app_projets', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $assocRepo    = $this->em->getRepository(Association::class);
        $associations = $assocRepo->findBy(['isActive' => true], ['nom' => 'ASC']);

        // ── Filtre par association (paramètre GET ?assoc={id}) ──
        $assocId      = (int) $request->query->get('assoc', 0);
        $assocActive  = null;   // association sélectionnée ou null = toutes

        if ($assocId > 0) { 
            $assocActive = $assocRepo->find($assocId);
        }

        // ── Récupération des projets ──
        if ($assocActive !== null) {
            // Uniquement les projets de l'association sélectionnée
            $projets = $this->projetRepo->findByAssociation($assocActive);
        } else {
            // Toutes les associations : eager loading complet
            $projets = $this->projetRepo->findAllWithRelations();
        }

        // ── Statistiques (globales ou restreintes à l'association) ──
        $stats = $this->projetRepo->getStatsGlobales();

        // ── Regroupement par association_id pour le template ──
        $grouped = [];
        foreach ($projets as $p) {
            $aid = $p->getAssociation()?->getId();
            if ($aid !== null) {
                $grouped[$aid][] = $p;
            }
        }

        return $this->render('projet/index.html.twig', [
            'associations'          => $associations,
            'assocActive'           => $assocActive,   // null = "toutes"
            'projetsParAssociation' => $grouped,
            'totalProjets'          => $stats['total'],
            'enCours'               => $stats['en_cours'],
            'termines'              => $stats['termines'],
            'totalBudget'           => $stats['total_budget'],
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  SHOW  ·  GET /projet/{id}
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Page rapport complète avec toutes les modales d'action.
    //
    //  montantRestant est passé comme variable Twig indépendante afin
    //  d'alimenter l'attribut max= du champ <input name="montant">
    //  dans la modale paiement du show. Cela permet une validation
    //  côté client avant même la soumission du formulaire.
    //
    //  Le template utilise aussi directement :
    //    projet.montantTotalPaye  → SUM paiements
    //    projet.montantRestant    → max(0, coutTotal - totalPaye)
    //    projet.tauxPaiement      → %  (0–100)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}', name: 'app_projet_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $projet = $this->findOr404($id);

        return $this->render('projet/show.html.twig', [
            'projet'         => $projet,
            // Séparé de projet.montantRestant pour pouvoir écrire
            // max="{{ montantRestant }}" directement dans l'input
            'montantRestant' => $projet->getMontantRestant(),
        ]);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  CREATE  ·  POST /projet/create
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Formulaire de la modale "Nouveau projet" (index uniquement).
    //  Tous les champs sont présents et non-vides (le form les initialise).
    //  → hydrateCreate() est utilisé : dates OBLIGATOIRES.
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/create', name: 'app_projet_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if (!$this->isCsrf('projet_create', $request)) {
            return $this->flash('app_projets', 'error', 'flash.csrf_invalid');
        }

        $assoc = $this->em->find(Association::class,
            (int) $request->request->get('association_id'));
        if (!$assoc) {
            return $this->flash('app_projets', 'error', 'flash.assoc_not_found');
        }

        // ── Numéro : fourni par le formulaire, doit être unique ──────
        $numero = strtoupper(trim($request->request->get('numero', '')));
        if ($numero === '') {
            return $this->flash('app_projets', 'error', 'flash.numero_required');
        }
        if ($this->projetRepo->findOneBy(['numero' => $numero])) {
            return $this->flash('app_projets', 'error', 'flash.numero_exists');
        }

        $projet = (new Projet())
            ->setAssociation($assoc)
            ->setCreePar($this->getUser())
            ->setNumero($numero);

        $this->hydrateCreate($projet, $request);

        $this->em->persist($projet);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.projet_created', [
            '%nom%' => $projet->getNom(),
        ]));

        return $this->redirectToRoute('app_projets');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  EDIT  ·  POST /projet/{id}/edit
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ⚠️  COMPORTEMENT DIFFÉRENT SELON LE FORMULAIRE APPELANT
    //
    //  ▸ Depuis l'INDEX (modale edit)
    //    Le JS ouvrirEdit() remplit : nom, donateur, coutTotal, lieu,
    //    duree, description, statut, progression, assocId, type.
    //    Il NE remplit PAS editDateContrat ni editDateFin (ces champs
    //    sont présents dans le HTML mais leur valeur reste vide car
    //    le JS n'y écrit pas). Le formulaire envoie également statut
    //    et progression dans ce même POST.
    //
    //  ▸ Depuis le SHOW (modale edit)
    //    Tous les champs sont pré-remplis via value="{{ projet.xxx }}"
    //    directement dans le Twig, donc dates toujours présentes.
    //    En revanche, statut et progression NE sont PAS envoyés ici
    //    (ils passent par la route dédiée statut()).
    //
    //  Solution : hydrateEdit() met à jour les dates UNIQUEMENT si la
    //  valeur POST est non-vide. Statut + progression sont traités
    //  en fin de méthode, uniquement si présents dans la requête.
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}/edit', name: 'app_projet_edit', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $projet = $this->findOr404($id);

        if (!$this->isCsrf('projet_edit_' . $id, $request)) {
            return $this->backTo($request, $id, 'flash.csrf_invalid', true);
        }

        // ── Numéro : mise à jour si fourni + unicité ─────────────────
        $numero = strtoupper(trim($request->request->get('numero', '')));
        if ($numero !== '' && $numero !== $projet->getNumero()) {
            $existing = $this->projetRepo->findOneBy(['numero' => $numero]);
            if ($existing && $existing->getId() !== $projet->getId()) {
                return $this->backTo($request, $id, 'flash.numero_exists', true);
            }
            $projet->setNumero($numero);
        }

        // Association : mise à jour si une valeur valide est fournie
        $assoc = $this->em->find(Association::class,
            (int) $request->request->get('association_id', 0));
        if ($assoc) {
            $projet->setAssociation($assoc);
        }

        // Champs principaux — dates en mode conditionnel (voir hydrateEdit)
        $this->hydrateEdit($projet, $request);

        // ── Statut (envoyé par l'index uniquement) ───────────────────
        // La modale show a sa propre route statut() pour cela.
        // On traite le statut ici S'IL est présent dans la requête.
        $statut = $request->request->get('statut');
        if ($statut !== null && $statut !== '' && array_key_exists($statut, Projet::STATUTS)) {
            $projet->setStatut($statut);
            // Mémorise le validateur à la première validation
            if ($statut === Projet::STATUT_VALIDE && !$projet->getValidePar()) {
                $projet->setValidePar($this->getUser());
            }
        }

        // ── Progression (envoyée par l'index dans le même form) ──────
        $progRaw = $request->request->get('progression');
        if ($progRaw !== null && $progRaw !== '') {
            $projet->setProgression(max(0, min(100, (int) $progRaw)));
        }

        $this->em->flush();

        $this->addFlash('success', $this->t('flash.projet_updated', [
            '%nom%' => $projet->getNom(),
        ]));

        return $this->backTo($request, $id);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  STATUT  ·  POST /projet/{id}/statut
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Route dédiée utilisée UNIQUEMENT par la sidebar statut du SHOW.
    //  (L'index passe statut + progression via edit() dans le même form.)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}/statut', name: 'app_projet_statut', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function statut(int $id, Request $request): Response
    {
        $projet = $this->findOr404($id);

        if (!$this->isCsrf('proj_statut_' . $id, $request)) {
            return $this->backTo($request, $id, 'flash.csrf_invalid', true);
        }

        $statut = $request->request->get('statut');
        if (array_key_exists($statut, Projet::STATUTS)) {
            $projet->setStatut($statut);
            if ($statut === Projet::STATUT_VALIDE && !$projet->getValidePar()) {
                $projet->setValidePar($this->getUser());
            }
        }

        // Clamp progression dans [0, 100]
        $prog = (int) $request->request->get('progression', $projet->getProgression());
        $projet->setProgression(max(0, min(100, $prog)));

        $this->em->flush();
        $this->addFlash('success', $this->t('flash.statut_updated'));

        return $this->backTo($request, $id);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  DELETE  ·  POST /projet/{id}/delete
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Supprime les fichiers physiques AVANT de supprimer l'entité.
    //  Les entités enfants (photos, paiements…) sont gérées par
    //  cascade: remove de Doctrine.
    //  Toujours redirige vers l'index (la page show n'existe plus).
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}/delete', name: 'app_projet_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request): Response
    {
        $projet = $this->findOr404($id);

        if (!$this->isCsrf('delete_projet_' . $id, $request)) {
            return $this->backTo($request, $id, 'flash.csrf_invalid', true);
        }

        $nom = $projet->getNom();

        // Nettoyer les fichiers physiques AVANT remove() pour éviter les orphelins
        foreach ($projet->getPhotos()    as $ph) $this->rmFile('projets/photos',    $ph->getFilename());
        foreach ($projet->getFichiers()  as $fi) $this->rmFile('projets/fichiers',  $fi->getFilename());
        foreach ($projet->getPaiements() as $pa) $this->rmFile('projets/paiements', $pa->getJustificatifFilename());

        $this->em->remove($projet);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.projet_deleted', ['%nom%' => $nom]));

        // Redirection forcée vers l'index : la page show n'existe plus
        return $this->redirectToRoute('app_projets');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PAIEMENT — Ajouter  ·  POST /projet/{id}/paiement/create
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //
    //  ⚙️  CONTRAINTE BUDGÉTAIRE (double validation client + serveur)
    //  ────────────────────────────────────────────────────────────────
    //  Côté client (show uniquement) :
    //    <input name="montant" max="{{ montantRestant }}">
    //    → Bloque la soumission avant même l'envoi (UX)
    //
    //  Côté serveur (ici, pour les deux templates) :
    //    getMontantRestant() → max(0, coutTotal - SUM paiements)
    //    Méthode de l'entité, calcul en mémoire sur la collection
    //    Doctrine (pas de requête BDD supplémentaire).
    //    → Garantit la sécurité même si le client-side est contourné
    //
    //  Exemples :
    //    Budget = 100 000, payé = 80 000, restant = 20 000
    //    → Paiement 25 000 → ❌ REFUSÉ  (25 000 > 20 000)
    //    → Paiement 20 000 → ✅ ACCEPTÉ (solde le budget)
    //    → Paiement  5 000 → ✅ ACCEPTÉ (restant = 15 000)
    //
    //  Champs reçus (identiques index et show) :
    //    montant, datePaiement, mode, reference, notes, justificatifFile
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}/paiement/create', name: 'app_projet_paiement_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addPaiement(int $id, Request $request): Response
    {
        $projet = $this->findOr404($id);

        // ── 1. CSRF ───────────────────────────────────────────────────
        if (!$this->isCsrf('paiement_form_' . $id, $request)) {
            return $this->backTo($request, $id, 'flash.csrf_invalid', true);
        }

        // ── 2. Montant strictement positif ────────────────────────────
        $montant = (float) $request->request->get('montant', 0);
        if ($montant <= 0) {
            return $this->backTo($request, $id, 'flash.paiement_invalid_amount', true);
        }

        // ── 3. Contrainte budgétaire (validation serveur) ─────────────
        $restant = $projet->getMontantRestant();
        if ($montant > $restant) {
            $this->addFlash('error', $this->t('flash.paiement_depasse_budget', [
                '%montant%' => number_format($montant, 0, ',', ' '),
                '%restant%' => number_format($restant, 0, ',', ' '),
            ]));
            return $this->backTo($request, $id);
        }

        // ── 4. Création du paiement ───────────────────────────────────
        $paiement = (new ProjetPaiement())
            ->setProjet($projet)
            ->setMontant(number_format($montant, 2, '.', ''))
            ->setMode($request->request->get('mode', ProjetPaiement::MODE_VIREMENT))
            ->setReference(trim($request->request->get('reference', '')) ?: null)
            ->setNotes(trim($request->request->get('notes', '')) ?: null)
            ->setSaisirPar($this->getUser());

        // Date du paiement (optionnel — défaut BDD = aujourd'hui)
        $ds = $request->request->get('datePaiement');
        if ($ds) {
            try { $paiement->setDatePaiement(new \DateTime($ds)); } catch (\Exception) {}
        }

        // ── 5. Justificatif PDF (optionnel, max 5 Mo) ─────────────────
        $file = $request->files->get('justificatifFile');
        if ($file) {
            $fn = $this->upload($file, 'projets/paiements', ['application/pdf'], 5);
            if ($fn) {
                $paiement->setJustificatifFilename($fn);
                $paiement->setJustificatifOriginalName($file->getClientOriginalName());
            }
        }

        $this->em->persist($paiement);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.paiement_added', [
            '%montant%' => number_format($montant, 0, ',', ' '),
        ]));

        return $this->backTo($request, $id);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PAIEMENT — Supprimer  ·  POST /projet/paiement/{id}/delete
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ⚠️  {id} = ID du paiement (pas du projet)
    //  Le projet parent est retrouvé via $paiement->getProjet()
    //  pour la redirection backTo().
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/paiement/{id}/delete', name: 'app_projet_paiement_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePaiement(int $id, Request $request): Response
    {
        $paiement = $this->em->find(ProjetPaiement::class, $id);
        if (!$paiement) {
            return $this->flash('app_projets', 'error', 'flash.not_found');
        }

        $projetId = $paiement->getProjet()->getId();

        if (!$this->isCsrf('delete_paiement_' . $id, $request)) {
            return $this->backTo($request, $projetId, 'flash.csrf_invalid', true);
        }

        $this->rmFile('projets/paiements', $paiement->getJustificatifFilename());
        $this->em->remove($paiement);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.paiement_deleted'));

        return $this->backTo($request, $projetId);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PHOTO — Ajouter (multiple)  ·  POST /projet/{id}/photo/create
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Les deux templates utilisent name="photoFiles[]" avec multiple.
    //  Symfony normalise automatiquement en tableau via files->get().
    //
    //  setTaille() est renseigné pour que getTailleFormatee() retourne
    //  une valeur lisible dans les deux templates (ex: "1.2 Mo").
    //
    //  Algorithme :
    //    1. Vérifie la limite 20 AVANT tout accès fichier
    //    2. Calcule slots = 20 - count actuel (places restantes)
    //    3. Boucle : upload → skipped si slots épuisés
    //    4. flush() une seule transaction pour toutes les photos
    //    5. Flash contextuel : 0 / partiel / 1 seule / plusieurs
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}/photo/create', name: 'app_projet_photo_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addPhoto(int $id, Request $request): Response
    {
        $projet = $this->findOr404($id);

        if (!$this->isCsrf('photo_form_' . $id, $request)) {
            return $this->backTo($request, $id, 'flash.csrf_invalid', true);
        }

        $currentCount = $projet->getPhotos()->count();
        if ($currentCount >= 20) {
            return $this->backTo($request, $id, 'flash.photos_limit_reached', true);
        }

        // Symfony normalise photoFiles[] → tableau ou objet unique
        $rawFiles = $request->files->get('photoFiles') ?? [];
        if (!is_array($rawFiles)) {
            $rawFiles = [$rawFiles];
        }
        $files = array_filter($rawFiles, fn($f) => $f !== null);

        if (empty($files)) {
            return $this->backTo($request, $id, 'flash.photos_none_valid', true);
        }

        $legende = trim($request->request->get('legende', '')) ?: null;
        $pos     = $currentCount;       // position de départ dans la galerie
        $slots   = 20 - $currentCount;  // places disponibles
        $total   = count($files);
        $added   = 0;
        $skipped = 0;

        foreach ($files as $file) {
            if ($added >= $slots) { $skipped++; continue; }

            $fn = $this->upload($file, 'projets/photos',
                ['image/jpeg', 'image/png', 'image/webp'], 5);

            if ($fn) {
                $this->em->persist(
                    (new ProjetPhoto())
                        ->setProjet($projet)
                        ->setFilename($fn)
                        ->setOriginalName($file->getClientOriginalName())
                        ->setLegende($legende)
                        ->setPosition($pos++)
                        // setTaille() requis pour getTailleFormatee() dans les templates
                       // ->setTaille($file->getSize())
                );
                $added++;
            }
        }

        $this->em->flush();

        // Flash contextuel selon le résultat
        if ($added === 0) {
            $this->addFlash('error', $this->t('flash.photos_none_valid'));
        } elseif ($skipped > 0) {
            $this->addFlash('warning', $this->t('flash.photos_partial', [
                '%added%' => $added, '%skipped%' => $skipped,
            ]));
        } elseif ($added === 1 && $total === 1) {
            $this->addFlash('success', $this->t('flash.photos_added_one'));
        } else {
            $this->addFlash('success', $this->t('flash.photos_added', [
                '%count%' => $added, '%total%' => $total,
            ]));
        }

        return $this->backTo($request, $id);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  PHOTO — Supprimer  ·  POST /projet/photo/{id}/delete
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ⚠️  {id} = ID de la photo (pas du projet)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/photo/{id}/delete', name: 'app_projet_photo_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePhoto(int $id, Request $request): Response
    {
        $photo = $this->em->find(ProjetPhoto::class, $id);
        if (!$photo) {
            return $this->flash('app_projets', 'error', 'flash.not_found');
        }

        $projetId = $photo->getProjet()->getId();

        if (!$this->isCsrf('delete_photo_' . $id, $request)) {
            return $this->backTo($request, $projetId, 'flash.csrf_invalid', true);
        }

        $this->rmFile('projets/photos', $photo->getFilename());
        $this->em->remove($photo);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.photo_deleted'));

        return $this->backTo($request, $projetId);
    }

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  FICHIER — Ajouter  ·  POST /projet/{id}/fichier/create
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
//  Les deux templates envoient name="fichierFile" (PDF uniquement).
//  setTaille() + setMimeType() → métadonnées affichées dans les listes.
//  Catégorie validée contre ProjetFichier::CATEGORIES, fallback rapport.
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
#[Route('/projet/{id}/fichier/create', name: 'app_projet_fichier_create', methods: ['POST'], requirements: ['id' => '\d+'])]
public function addFichier(int $id, Request $request): Response
{
    $projet = $this->findOr404($id);

    // ── Vérification CSRF  ────────────────────────────────────────── 
    if (!$this->isCsrf('fichier_form_' . $id, $request)) {
        // ✅ Utiliser directement la méthode Symfony native
if (!$this->isCsrfTokenValid('fichier_form_' . $id, $request->request->get('_token'))) {
    return $this->backTo($request, $id, 'flash.csrf_invalid', true);
}
    }



    // ── Récupérer le fichier uploadé ───────────────────────────────
    $file = $request->files->get('fichierFile');
    if (!$file) {
        return $this->backTo($request, $id, 'flash.no_file', true);
    }

    // ── CRITIQUE : lire les métadonnées AVANT upload() ────────────
    //
    //    $this->upload() appelle $file->move() en interne.
    //    Après move(), le fichier temporaire PHP_UPLOAD_TMP est supprimé.
    //    Tout appel à getMimeType() ou getSize() APRÈS move() lève :
    //      InvalidArgumentException: The "" file does not exist
    //
    //    Solution : extraire mime, taille et nom original AVANT l'upload.
    //
    if (!$file->isValid()) {
        // Le fichier n'a pas été transmis correctement (erreur HTTP upload)
        return $this->backTo($request, $id, 'flash.no_file', true);
    }

    $originalName = $file->getClientOriginalName(); // nom affiché dans l'UI
    $mimeType     = $file->getMimeType();            // lu AVANT move()
    $taille       = $file->getSize();               // lu AVANT move()

    // ── Valider et normaliser la catégorie ─────────────────────────
    $cat = $request->request->get('categorie', ProjetFichier::CAT_RAPPORT);
    if (!array_key_exists($cat, ProjetFichier::CATEGORIES)) {
        $cat = ProjetFichier::CAT_RAPPORT;
    }

    // ── Uploader le fichier (déplace le temporaire vers son dest.) ─
    //    upload() retourne null si MIME ou taille invalide,
    //    et ajoute lui-même le flash d'erreur approprié.
    $fn = $this->upload($file, 'projets/fichiers', ['application/pdf'], 10);
    if (!$fn) {
        return $this->backTo($request, $id);
    }

    // ── Persister la fiche fichier en base ─────────────────────────
    //    On utilise les variables pré-lues ($mimeType, $taille, $originalName)
    //    car $file->getMimeType() crasherait ici (fichier déjà déplacé).
    $this->em->persist(
        (new ProjetFichier())
            ->setProjet($projet)
            ->setCategorie($cat)
            ->setFilename($fn)
            ->setOriginalName($originalName)
            ->setMimeType($mimeType)
            ->setTaille($taille)
    );
    $this->em->flush();

    $this->addFlash('success', $this->t('flash.fichier_added', [
        '%nom%' => $originalName,
    ]));

    return $this->backTo($request, $id);
}

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  FICHIER — Supprimer  ·  POST /projet/fichier/{id}/delete
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ⚠️  {id} = ID du fichier (pas du projet)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/fichier/{id}/delete', name: 'app_projet_fichier_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteFichier(int $id, Request $request): Response
    {
        $fichier = $this->em->find(ProjetFichier::class, $id);
        if (!$fichier) {
            return $this->flash('app_projets', 'error', 'flash.not_found');
        }

        $projetId = $fichier->getProjet()->getId();

        if (!$this->isCsrf('delete_fichier_' . $id, $request)) {
            return $this->backTo($request, $projetId, 'flash.csrf_invalid', true);
        }

        $this->rmFile('projets/fichiers', $fichier->getFilename());
        $this->em->remove($fichier);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.fichier_deleted'));

        return $this->backTo($request, $projetId);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  VIDÉO — Ajouter  ·  POST /projet/{id}/video/create
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  Les deux templates envoient name="url" et name="titre".
    //  ProjetVideo gère lui-même l'extraction de l'ID YouTube et
    //  la génération du lien thumbnail via getYoutubeId() / getThumbnailUrl().
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/{id}/video/create', name: 'app_projet_video_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addVideo(int $id, Request $request): Response
    {
        $projet = $this->findOr404($id);

        if (!$this->isCsrf('video_form_' . $id, $request)) {
            return $this->backTo($request, $id, 'flash.csrf_invalid', true);
        }

        $url = trim($request->request->get('url', ''));
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->backTo($request, $id, 'flash.invalid_url', true);
        }

        $this->em->persist(
            (new ProjetVideo())
                ->setProjet($projet)
                ->setUrl($url)
                ->setTitre(trim($request->request->get('titre', '')) ?: null)
        );
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.video_added'));

        return $this->backTo($request, $id);
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  VIDÉO — Supprimer  ·  POST /projet/video/{id}/delete
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  ⚠️  {id} = ID de la vidéo (pas du projet)
    //  Pas de fichier physique à supprimer (lien YouTube uniquement).
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    #[Route('/projet/video/{id}/delete', name: 'app_projet_video_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteVideo(int $id, Request $request): Response
    {
        $video = $this->em->find(ProjetVideo::class, $id);
        if (!$video) {
            return $this->flash('app_projets', 'error', 'flash.not_found');
        }

        $projetId = $video->getProjet()->getId();

        if (!$this->isCsrf('delete_video_' . $id, $request)) {
            return $this->backTo($request, $projetId, 'flash.csrf_invalid', true);
        }

        $this->em->remove($video);
        $this->em->flush();

        $this->addFlash('success', $this->t('flash.video_deleted'));

        return $this->backTo($request, $projetId);
    }

    // ══════════════════════════════════════════════════════════════════
    //  HELPERS PRIVÉS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Trouve un projet par ID ou lève une 404.
     * Point d'entrée unique pour toutes les actions qui reçoivent {id}.
     */
    private function findOr404(int $id): Projet
    {
        $projet = $this->projetRepo->find($id);
        if (!$projet) {
            throw $this->createNotFoundException('Projet introuvable.');
        }
        return $projet;
    }

    /**
     * Hydratation pour CREATE uniquement.
     *
     * Le formulaire de création initialise toujours tous les champs
     * (dateContrat a value="{{ 'now'|date('Y-m-d') }}", dateFin peut
     * être vide mais c'est intentionnel). Les dates sont donc traitées
     * de façon obligatoire ici.
     */
    private function hydrateCreate(Projet $p, Request $r): void
    {
        $this->hydrateCommun($p, $r);

        // dateContrat toujours présente en création (valeur par défaut = aujourd'hui)
        try {
            $p->setDateContrat(new \DateTime($r->request->get('dateContrat')));
        } catch (\Exception) {
            $p->setDateContrat(new \DateTime());
        }

        // dateFin optionnelle
        $dateFin = $r->request->get('dateFin');
        $p->setDateFin($dateFin ? new \DateTime($dateFin) : null);
    }

    /**
     * Hydratation pour EDIT uniquement.
     *
     * ⚠️  Le JS ouvrirEdit() de l'index NE renseigne PAS les champs
     * editDateContrat et editDateFin (ils existent dans le HTML mais
     * restent vides). Pour éviter d'écraser des dates valides par une
     * chaîne vide (ce qui crasherait new DateTime(null)), on ne met à
     * jour les dates QUE si la valeur POST est non-vide.
     *
     * Le show, lui, pré-remplit les dates via value="{{ projet.dateXxx }}"
     * directement dans le Twig — elles sont donc toujours présentes dans ce cas.
     */
    private function hydrateEdit(Projet $p, Request $r): void
    {
        $this->hydrateCommun($p, $r);

        // dateContrat : mise à jour conditionnelle
        $dc = $r->request->get('dateContrat');
        if ($dc !== null && $dc !== '') {
            try { $p->setDateContrat(new \DateTime($dc)); } catch (\Exception) {}
        }

        // dateFin : mise à jour conditionnelle (null = effacer la date)
        $df = $r->request->get('dateFin');
        if ($df !== null) {
            // Chaîne non-vide → nouvelle date ; chaîne vide → effacer la date fin
            $p->setDateFin($df !== '' ? new \DateTime($df) : null);
        }
        // Si 'dateFin' absent de la requête (null retourné par get()) → ne pas toucher
    }

    /**
     * Champs communs à CREATE et EDIT (sans les dates).
     *
     * Champs : nom, type, donateur, coutTotal, lieu, pays, duree, description.
     * Ces champs sont toujours présents et non-vides dans les deux contextes.
     */
    private function hydrateCommun(Projet $p, Request $r): void
    {
        $p->setNom(trim($r->request->get('nom', '')));
        $p->setType($r->request->get('type', Projet::TYPE_AUTRE));
        $p->setDonateur(trim($r->request->get('donateur', '')));
        $p->setCoutTotal(number_format((float) $r->request->get('coutTotal', 0), 2, '.', ''));
        $p->setLieu(trim($r->request->get('lieu', '')));
        $p->setPays($r->request->get('pays', 'Mauritanie'));
        $p->setDuree(trim($r->request->get('duree', '')));
        $p->setDescription(trim($r->request->get('description', '')) ?: null);

        // Année : mise à jour si présente dans la requête
        $anneeRaw = $r->request->get('annee');
        if ($anneeRaw !== null && $anneeRaw !== '') {
            $annee = (int) $anneeRaw;
            if ($annee >= 2000 && $annee <= 2099) {
                $p->setAnnee($annee);
            }
        }
    }

    /**
     * Upload sécurisé d'un fichier.
     *
     * Validations dans l'ordre :
     *  1. Taille ≤ $maxMb Mo
     *  2. Type MIME dans la liste blanche $mimes (contrôle strict)
     *  3. Création du répertoire si absent (mkdir récursif)
     *  4. Nom final : slugify(nomOriginal) + uniqid() + extension
     *     → évite les collisions, les espaces et les noms malveillants
     *
     * @param  mixed    $file   UploadedFile Symfony
     * @param  string   $subdir Sous-dossier cible sous public/uploads/
     * @param  string[] $mimes  Types MIME autorisés
     * @param  int      $maxMb  Taille max en mégaoctets
     * @return string|null      Nom du fichier enregistré, null si erreur
     */
    private function upload(mixed $file, string $subdir, array $mimes, int $maxMb): ?string
    {
        if ($file->getSize() > $maxMb * 1024 * 1024) {
            $this->addFlash('error', "Fichier trop volumineux (max {$maxMb} Mo).");
            return null;
        }

        if (!in_array($file->getMimeType(), $mimes, true)) {
            $this->addFlash('error', 'Type de fichier non autorisé.');
            return null;
        }

        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $safe = $this->slugger->slug(
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
        );
        $fn = $safe . '-' . uniqid() . '.' . ($file->guessExtension() ?: 'bin');

        try {
            $file->move($dir, $fn);
            return $fn;
        } catch (FileException) {
            $this->addFlash('error', "Échec de l'upload.");
            return null;
        }
    }

    /**
     * Supprime silencieusement un fichier physique du disque.
     * Toujours appelé AVANT remove() pour éviter les fichiers orphelins.
     * Ne fait rien si $fn est null ou si le fichier n'existe pas.
     */
    private function rmFile(string $subdir, ?string $fn): void
    {
        if (!$fn) return;
        $path = $this->getParameter('kernel.project_dir')
            . '/public/uploads/' . $subdir . '/' . $fn;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Redirige vers la bonne page selon le champ caché _from.
     *
     *   _from=show  → app_projet_show (page rapport du projet)
     *   _from=index → app_projets (liste — comportement par défaut)
     *
     * Ajoute un flash avant de rediriger si $errKey est fourni.
     *
     * @param bool $isError true → flash 'error', false → flash 'success'
     */
    private function backTo(
        Request $request,
        int     $projetId,
        string  $errKey  = '',
        bool    $isError = false,
    ): Response {
        if ($errKey) {
            $this->addFlash($isError ? 'error' : 'success', $this->t($errKey));
        }

        return $request->request->get('_from') === 'show'
            ? $this->redirectToRoute('app_projet_show', ['id' => $projetId])
            : $this->redirectToRoute('app_projets');
    }

    /**
     * Flash + redirection directe vers une route (sans logique _from).
     * Utilisé quand l'entité parente est introuvable (sous-entité 404).
     */
    private function flash(string $route, string $type, string $key, array $p = []): Response
    {
        $this->addFlash($type, $this->t($key, $p));
        return $this->redirectToRoute($route);
    }

    /**
     * Valide le token CSRF de la requête POST.
     * Chaque action a son propre tokenId unique pour empêcher la
     * réutilisation cross-action d'un token intercepté.
     */
    private function isCsrf(string $tokenId, Request $r): bool
    {
        return $this->isCsrfTokenValid($tokenId, $r->request->get('_token'));
    }

    /**
     * Raccourci pour $this->translator->trans().
     * Réduit la verbosité des appels dans les méthodes du contrôleur.
     */
    private function t(string $key, array $p = []): string
    {
        return $this->translator->trans($key, $p);
    }

    /**
     * Génère le prochain numéro de projet pour l'année en cours.
     * Format : PRJ-YYYY-NNN  (ex: PRJ-2026-001)
     *
     * L'annee est initialisée dans Projet::__construct() → pas besoin de
     * la passer au constructeur.
     *
     * ⚠️  Race condition théorique en création simultanée → la contrainte
     *     UNIQUE sur la colonne numero garantit l'unicité finale en BDD.
     */
    private function nextNumero(): string
    {
        $annee = (int) date('Y');
        $count = (int) $this->projetRepo->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.annee = :a')
            ->setParameter('a', $annee)
            ->getQuery()
            ->getSingleScalarResult();

        return sprintf('PRJ-%d-%03d', $annee, $count + 1);
    }
}