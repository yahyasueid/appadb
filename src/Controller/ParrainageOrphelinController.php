<?php
// src/Controller/ParrainageOrphelinController.php
// ╔══════════════════════════════════════════════════════════════╗
// ║  CONTROLLER PARRAINAGE ORPHELIN                              ║
// ║                                                              ║
// ║  Routes :                                                    ║
// ║  GET  /parrainage/orphelin/          index (liste)           ║
// ║  POST /parrainage/orphelin/new       new   (créer)           ║
// ║  GET  /parrainage/orphelin/{id}      show  (fiche+rapports)  ║
// ║  GET  /parrainage/orphelin/{id}/data JSON  (modale edit)     ║
// ║  POST /parrainage/orphelin/{id}/edit       modifier          ║
// ║  POST /parrainage/orphelin/{id}/delete     supprimer         ║
// ║  POST /parrainage/orphelin/{id}/approuver  جديد → معتمدة    ║
// ║  POST /parrainage/orphelin/{id}/activer    معتمدة → مكفول   ║
// ║  POST /parrainage/orphelin/{id}/annuler    → ملغي            ║
// ║  POST /parrainage/orphelin/{id}/paiement   ajouter versement ║
// ║  POST /parrainage/orphelin/{id}/rapport    ajouter rapport   ║
// ╚══════════════════════════════════════════════════════════════╝

namespace App\Controller;

use App\Entity\Parrainage;
use App\Entity\ParrainageOrphelin;
use App\Entity\ParrainagePaiement;
use App\Entity\RapportParrainage;
use App\Repository\AssociationRepository;
use App\Repository\ParrainRepository;
use App\Repository\ParrainageOrphelinRepository;
use App\Repository\ParrainageRepository;
use App\Repository\RapportParrainageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/parrainage/orphelin')]
#[IsGranted('ROLE_USER')]
class ParrainageOrphelinController extends AbstractController
{
    // ══════════════════════════════════════════════
    // INDEX — liste groupée par association
    // ══════════════════════════════════════════════

    #[Route('/', name: 'app_parrainage_orphelin_index', methods: ['GET'])]
    public function index(
        Request                      $request,
        AssociationRepository        $assocRepo,
        ParrainageOrphelinRepository $orphelinRepo,
        ParrainRepository            $parrainRepo
    ): Response {
        $associations = $assocRepo->findBy(['isActive' => true], ['nom' => 'ASC']);

        // Filtre optionnel : ?assoc=ID
        $assocActive = null;
        if ($assocId = $request->query->getInt('assoc')) {
            foreach ($associations as $a) {
                if ($a->getId() === $assocId) { $assocActive = $a; break; }
            }
        }
        // Fiches + stats
        $fichesByAssoc  = [];
        $totalOrphelins = 0;
        $totalActifs    = 0;
        $statuts = [
            Parrainage::STATUT_NOUVEAU  => 0,
            Parrainage::STATUT_APPROUVE => 0,
            Parrainage::STATUT_ACTIF    => 0,
            Parrainage::STATUT_ANNULE   => 0,
        ];

        foreach ($associations as $assoc) {
            $fiches = $orphelinRepo->findByAssociation($assoc);
            $fichesByAssoc[$assoc->getId()] = $fiches;
            $totalOrphelins += count($fiches);
            foreach ($fiches as $fiche) {
                $s = $fiche->getParrainage()->getStatut();
                if (array_key_exists($s, $statuts)) $statuts[$s]++;
                if ($fiche->getParrainage()->isActif()) $totalActifs++;
            }
        }

        // Parrains pour modale création
        $parrains = $assocActive
            ? $parrainRepo->findByAssociation($assocActive)
            : $parrainRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('orphelin/index.html.twig', [
            'associations'   => $associations,
            'fichesByAssoc'  => $fichesByAssoc,
            'assocActive'    => $assocActive,
            'totalOrphelins' => $totalOrphelins,
            'totalActifs'    => $totalActifs,
            'statuts'        => $statuts,
            'parrains'       => $parrains,
        ]);
    }

    // ══════════════════════════════════════════════
    // SHOW — fiche + paiements + rapports
    // ══════════════════════════════════════════════

    #[Route('/{id}', name: 'app_parrainage_orphelin_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        ParrainageOrphelin          $fiche,
        ParrainageRepository        $parrainageRepo,
        RapportParrainageRepository $rapportRepo
    ): Response {
        // findWithDetails charge le parrainage avec paiements JOIN (évite N+1)
        $parrainage = $parrainageRepo->findWithDetails($fiche->getParrainage()->getId());

        // Rapports ordonnés par année + semestre DESC
        $rapports = $rapportRepo->findByParrainage($parrainage);

        return $this->render('orphelin/show.html.twig', [
            'fiche'      => $fiche,
            'parrainage' => $parrainage,
            'rapports'   => $rapports,
        ]);
    }

    // ══════════════════════════════════════════════
    // DATA JSON — pré-remplir la modale edit (fetch JS)
    // ══════════════════════════════════════════════

    #[Route('/{id}/data', name: 'app_parrainage_orphelin_data', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function data(ParrainageOrphelin $fiche): JsonResponse
    {
        return $this->json([
            'id'                    => $fiche->getId(),
            'nomComplet'            => $fiche->getNomComplet(),
            'cin'                   => $fiche->getCin(),
            'dateNaissance'         => $fiche->getDateNaissance()?->format('Y-m-d'),
            'lieuNaissance'         => $fiche->getLieuNaissance(),
            'genre'                 => $fiche->getGenre(),
            'typeOrphelin'          => $fiche->getTypeOrphelin(),
            'nomMere'               => $fiche->getNomMere(),
            'mereMariee'            => $fiche->isMereMariee(),
            'nomTuteur'             => $fiche->getNomTuteur(),
            'relationTuteur'        => $fiche->getRelationTuteur(),
            'dateDecesPere'         => $fiche->getDateDecesPere()?->format('Y-m-d'),
            'dateDecesMere'         => $fiche->getDateDecesMere()?->format('Y-m-d'),
            'nbFreres'              => $fiche->getNbFreres(),
            'nbSoeurs'              => $fiche->getNbSoeurs(),
            'adresse'               => $fiche->getAdresse(),
            'telephone'             => $fiche->getTelephone(),
            'etatSante'             => $fiche->getEtatSante(),
            'ecole'                 => $fiche->getEcole(),
            'niveauScolaire'        => $fiche->getNiveauScolaire(),
            'raisonNonScolarisation'=> $fiche->getRaisonNonScolarisation(),
            'hasPhoto'              => $fiche->hasPhoto(),
        ]);
    }

    // ══════════════════════════════════════════════
    // NEW — créer un dossier (depuis la modale index)
    // ══════════════════════════════════════════════

    #[Route('/new', name: 'app_parrainage_orphelin_new', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function new(
        Request                $request,
        EntityManagerInterface $em,
        AssociationRepository  $assocRepo,
        ParrainRepository      $parrainRepo,
        SluggerInterface       $slugger
    ): Response {
        // ── Vérification CSRF ──────────────────────────────────────────
        if (!$this->isCsrfTokenValid('orphelin_new', $request->request->get('_token'))) {
            $this->flash($request, 'error', 'Token de sécurité invalide.', 'رمز الأمان غير صالح.', 'Invalid security token.');
            return $this->redirectToRoute('app_parrainage_orphelin_index');
        }

        // ── Charger l'association et le parrain depuis la requête ───────
        $assoc   = $assocRepo->find((int) $request->request->get('association'));
        $parrain = $parrainRepo->find((int) $request->request->get('parrain'));
        if (!$assoc || !$parrain) {
            $this->flash($request, 'error', 'Association ou parrain introuvable.', 'الجمعية أو الكافل غير موجود.', 'Association or sponsor not found.');
            return $this->redirectToRoute('app_parrainage_orphelin_index');
        }

        // ── Créer l'entité Parrainage (pivot central) ──────────────────
        // Le parrainage relie : association ↔ parrain ↔ fiche orphelin
        $par = new Parrainage();
        $par->setAssociation($assoc);
        $par->setParrain($parrain);
        $par->setType(Parrainage::TYPE_ORPHELIN);
        $par->setStatut(Parrainage::STATUT_NOUVEAU);   // statut initial : جديد
        $par->setCreePar($this->getUser());

        // Numéro auto : PAR-AAAA-XXXX (séquentiel par association + type)
        $count = $em->getRepository(Parrainage::class)
                    ->count(['association' => $assoc, 'type' => Parrainage::TYPE_ORPHELIN]);
        $par->setNumero(sprintf('PAR-%d-%04d', (int) date('Y'), $count + 1));

        // ── Financement (optionnel) ────────────────────────────────────
        if ($m = $request->request->get('montant_periodique'))
            $par->setMontantPeriodique(number_format((float) $m, 2, '.', ''));
        if ($p = $request->request->get('periodicite')) $par->setPeriodicite($p);
        if ($d = $request->request->get('date_debut'))
            try { $par->setDateDebut(new \DateTime($d)); } catch (\Exception) {}

        // ── Créer la fiche orphelin et remplir ses champs ─────────────
        $fiche = new ParrainageOrphelin();
        $fiche->setParrainage($par);
        $this->hydrate($fiche, $request);

        // ── Upload photo (optionnel) ───────────────────────────────────
        // moveFile() vérifie isValid() en premier pour éviter l'exception MIME
        if ($photo = $request->files->get('photo'))
            if ($fn = $this->moveFile($photo, 'orphelins', $slugger))
                $fiche->setPhoto($fn);

        // ── Persister en base ──────────────────────────────────────────
        $em->persist($par);
        $em->persist($fiche);
        $em->flush();

        $this->flash(
            $request, 'success',
            sprintf('Dossier "%s" créé — réf. %s.', $fiche->getNomComplet(), $par->getNumero()),
            sprintf('تم إنشاء ملف "%s" — المرجع: %s.', $fiche->getNomComplet(), $par->getNumero()),
            sprintf('File "%s" created — ref. %s.', $fiche->getNomComplet(), $par->getNumero())
        );
        return $this->redirectToRoute('app_parrainage_orphelin_show', ['id' => $fiche->getId()]);
    }

    // ══════════════════════════════════════════════
    // EDIT — modifier la fiche (depuis la modale show)
    // ══════════════════════════════════════════════

    #[Route('/{id}/edit', name: 'app_parrainage_orphelin_edit', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function edit(
        ParrainageOrphelin $fiche, Request $request,
        EntityManagerInterface $em, SluggerInterface $slugger
    ): Response {
        // ── Vérification CSRF ──────────────────────────────────────────
        if (!$this->isCsrfTokenValid('orphelin_edit', $request->request->get('_token'))) {
            $this->flash($request, 'error', 'Token de sécurité invalide.', 'رمز الأمان غير صالح.', 'Invalid security token.');
            return $this->show_redirect($fiche);
        }

        // ── Mettre à jour les champs via hydrate() ─────────────────────
        $this->hydrate($fiche, $request);

        // ── Remplacer la photo si une nouvelle est envoyée ─────────────
        // Si le champ photo est vide, la photo actuelle est conservée
        if ($photo = $request->files->get('photo')) {
            if ($fiche->hasPhoto()) $this->removeFile('orphelins', $fiche->getPhoto());
            if ($fn = $this->moveFile($photo, 'orphelins', $slugger)) $fiche->setPhoto($fn);
        }

        $em->flush();
        $this->flash(
            $request, 'success',
            sprintf('Dossier "%s" mis à jour.', $fiche->getNomComplet()),
            sprintf('تم تحديث ملف "%s".', $fiche->getNomComplet()),
            sprintf('File "%s" updated.', $fiche->getNomComplet())
        );
        return $this->show_redirect($fiche);
    }

    // ══════════════════════════════════════════════
    // DELETE
    // ══════════════════════════════════════════════

    #[Route('/{id}/delete', name: 'app_parrainage_orphelin_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function delete(
        ParrainageOrphelin $fiche, Request $request, EntityManagerInterface $em
    ): Response {
        // ── Vérification CSRF ──────────────────────────────────────────
        if (!$this->isCsrfTokenValid('orphelin_delete', $request->request->get('_token'))) {
            $this->flash($request, 'error', 'Token de sécurité invalide.', 'رمز الأمان غير صالح.', 'Invalid security token.');
            return $this->redirectToRoute('app_parrainage_orphelin_index');
        }

        $nom     = $fiche->getNomComplet();
        $assocId = $fiche->getParrainage()->getAssociation()->getId();

        // ── Supprimer la photo physique si elle existe ─────────────────
        if ($fiche->hasPhoto()) $this->removeFile('orphelins', $fiche->getPhoto());

        // ── Supprimer le Parrainage parent ─────────────────────────────
        // La cascade (onDelete: CASCADE) supprime automatiquement :
        //   fiche orphelin, paiements, et rapports associés
        $em->remove($fiche->getParrainage());
        $em->flush();

        $this->flash(
            $request, 'success',
            sprintf('Dossier "%s" supprimé définitivement.', $nom),
            sprintf('تم حذف ملف "%s" نهائياً.', $nom),
            sprintf('File "%s" permanently deleted.', $nom)
        );
        return $this->redirectToRoute('app_parrainage_orphelin_index', ['assoc' => $assocId]);
    }

    // ══════════════════════════════════════════════
    // TRANSITIONS DE STATUT
    // Toutes redirigent vers show après l'action
    // ══════════════════════════════════════════════

    /** جديد → معتمدة — Réservé DIRECTEUR_PARRAINAGES */
    #[Route('/{id}/approuver', name: 'app_parrainage_orphelin_approuver', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function approuver(ParrainageOrphelin $fiche, Request $request, EntityManagerInterface $em): Response
    {
        // CSRF non valide → retour silencieux (pas de message pour éviter l'exposition)
        if (!$this->isCsrfTokenValid('orphelin_approuver', $request->request->get('_token')))
            return $this->show_redirect($fiche);

        $par = $fiche->getParrainage();

        // ── Vérifier que la transition est autorisée ───────────────────
        // canApprouver() → statut doit être جديد
        if (!$par->canApprouver()) {
            $this->flash($request, 'warning',
                'Ce dossier ne peut pas être approuvé depuis son statut actuel.',
                'لا يمكن اعتماد هذا الملف من حالته الحالية.',
                'This file cannot be approved from its current status.'
            );
            return $this->show_redirect($fiche);
        }

        // ── Appliquer la transition : enregistre le validateur + date ──
        $par->approuver($this->getUser());
        $em->flush();

        $this->flash($request, 'success',
            sprintf('"%s" approuvé — معتمدة.', $fiche->getNomComplet()),
            sprintf('تمت الموافقة على "%s" — معتمدة.', $fiche->getNomComplet()),
            sprintf('"%s" approved — معتمدة.', $fiche->getNomComplet())
        );
        return $this->show_redirect($fiche);
    }

    /** معتمدة → مكفول — Réservé DIRECTEUR_PARRAINAGES */
    #[Route('/{id}/activer', name: 'app_parrainage_orphelin_activer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function activer(ParrainageOrphelin $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('orphelin_activer', $request->request->get('_token')))
            return $this->show_redirect($fiche);

        $par = $fiche->getParrainage();

        // ── Vérifier que la transition est autorisée ───────────────────
        // canActiver() → statut doit être معتمدة
        if (!$par->canActiver()) {
            $this->flash($request, 'warning',
                'Ce dossier ne peut pas être activé depuis son statut actuel.',
                'لا يمكن تفعيل هذا الملف من حالته الحالية.',
                'This file cannot be activated from its current status.'
            );
            return $this->show_redirect($fiche);
        }

        // ── Activer le parrainage : date de début = aujourd'hui ────────
        $par->activer();
        $em->flush();

        $this->flash($request, 'success',
            sprintf('"%s" activé — مكفول à partir du %s.', $fiche->getNomComplet(), $par->getDateDebut()?->format('d/m/Y') ?? date('d/m/Y')),
            sprintf('تم تفعيل "%s" — مكفول ابتداءً من %s.', $fiche->getNomComplet(), $par->getDateDebut()?->format('d/m/Y') ?? date('d/m/Y')),
            sprintf('"%s" activated — مكفول as of %s.', $fiche->getNomComplet(), $par->getDateDebut()?->format('d/m/Y') ?? date('d/m/Y'))
        );
        return $this->show_redirect($fiche);
    }

    /** → ملغي — Réservé DIRECTEUR_PARRAINAGES */
    #[Route('/{id}/annuler', name: 'app_parrainage_orphelin_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function annuler(ParrainageOrphelin $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('orphelin_annuler', $request->request->get('_token')))
            return $this->show_redirect($fiche);

        $par = $fiche->getParrainage();

        // ── Vérifier que la transition est autorisée ───────────────────
        // canAnnuler() → statut ≠ مكفول et ≠ ملغي
        if (!$par->canAnnuler()) {
            $this->flash($request, 'warning',
                'Ce dossier ne peut pas être annulé depuis son statut actuel.',
                'لا يمكن إلغاء هذا الملف من حالته الحالية.',
                'This file cannot be cancelled from its current status.'
            );
            return $this->show_redirect($fiche);
        }

        // ── Annuler + enregistrer la raison en notes ───────────────────
        $par->annuler();
        if ($raison = trim((string) $request->request->get('raison', ''))) {
            $notes = trim($par->getNotes() ?? '');
            $par->setNotes($notes ? $notes . "\n[Annulation] " . $raison : '[Annulation] ' . $raison);
        }
        $em->flush();

        $this->flash($request, 'success',
            sprintf('"%s" annulé — ملغي.', $fiche->getNomComplet()),
            sprintf('تم إلغاء "%s" — ملغي.', $fiche->getNomComplet()),
            sprintf('"%s" cancelled — ملغي.', $fiche->getNomComplet())
        );
        return $this->show_redirect($fiche);
    }

    // ══════════════════════════════════════════════
    // PAIEMENT — ajouter un versement (depuis show)
    // ══════════════════════════════════════════════

    #[Route('/{id}/paiement', name: 'app_parrainage_orphelin_paiement', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function paiement(
        ParrainageOrphelin $fiche, Request $request,
        EntityManagerInterface $em, SluggerInterface $slugger
    ): Response {
        if (!$this->isCsrfTokenValid('orphelin_paiement', $request->request->get('_token')))
            return $this->show_redirect($fiche);

        // ── Valider le montant ─────────────────────────────────────────
        $montant = (float) $request->request->get('montant');
        if ($montant <= 0) {
            $this->flash($request, 'error',
                'Le montant doit être un nombre positif.',
                'يجب أن يكون المبلغ عدداً موجباً.',
                'Amount must be a positive number.'
            );
            return $this->show_redirect($fiche);
        }

        // ── Créer l'entité paiement ────────────────────────────────────
        $p = new ParrainagePaiement();
        $p->setParrainage($fiche->getParrainage());
        $p->setMontant(number_format($montant, 2, '.', ''));
        $p->setSaisirPar($this->getUser());
        $p->setMode($request->request->get('mode', ParrainagePaiement::MODE_ESPECES));
        $p->setReference($request->request->get('reference') ?: null);
        $p->setPeriodeConcernee($request->request->get('periode_concernee') ?: null);
        $p->setNotes($request->request->get('notes') ?: null);

        // ── Date du versement ──────────────────────────────────────────
        if ($d = $request->request->get('date_paiement'))
            try { $p->setDatePaiement(new \DateTime($d)); } catch (\Exception) {}

        // ── Upload justificatif (PDF ou image, max 15 Mo) ─────────────
        if ($file = $request->files->get('justificatif'))
            if ($fn = $this->moveFile($file, 'parrainages/paiements', $slugger, 15)) {
                $p->setJustificatifFilename($fn);
                $p->setJustificatifOriginalName($file->getClientOriginalName());
            }

        $em->persist($p);
        $em->flush();

        $this->flash($request, 'success',
            sprintf('Versement de %s MRU enregistré.', number_format($montant, 0, '.', ' ')),
            sprintf('تم تسجيل دفعة بقيمة %s أوقية.', number_format($montant, 0, '.', ' ')),
            sprintf('Payment of %s MRU recorded.', number_format($montant, 0, '.', ' '))
        );
        return $this->show_redirect($fiche);
    }

    // ══════════════════════════════════════════════
    // RAPPORT — ajouter un rapport semestriel (depuis show)
    // ══════════════════════════════════════════════

    #[Route('/{id}/rapport', name: 'app_parrainage_orphelin_rapport', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function rapport(
        ParrainageOrphelin $fiche, Request $request,
        EntityManagerInterface $em, RapportParrainageRepository $rapportRepo,
        SluggerInterface $slugger
    ): Response {
        if (!$this->isCsrfTokenValid('orphelin_rapport', $request->request->get('_token')))
            return $this->show_redirect($fiche);

        $par      = $fiche->getParrainage();
        $annee    = (int) $request->request->get('annee', date('Y'));
        $semestre = (int) $request->request->get('semestre', 1);

        // ── Vérifier la contrainte unique (1 rapport / parrainage / période) ──
        if ($rapportRepo->findOneByPeriode($par, $annee, $semestre)) {
            $this->flash($request, 'warning',
                sprintf('Un rapport S%d %d existe déjà pour ce parrainage.', $semestre, $annee),
                sprintf('يوجد تقرير S%d %d بالفعل لهذه الكفالة.', $semestre, $annee),
                sprintf('A S%d %d report already exists for this sponsorship.', $semestre, $annee)
            );
            return $this->show_redirect($fiche);
        }

        // ── Créer le rapport ───────────────────────────────────────────
        $r = new RapportParrainage();
        $r->setParrainage($par);
        $r->setAnnee($annee);
        $r->setSemestre($semestre);
        $r->setStatut(RapportParrainage::STATUT_BROUILLON);  // statut initial
        $r->setCreePar($this->getUser());
        $r->setTitre(sprintf('Rapport S%d %d — %s', $semestre, $annee, $fiche->getNomComplet()));

        // ── Contenus textuels du rapport ──────────────────────────────
        $r->setSituationGenerale($request->request->get('situation_generale') ?: null);
        $r->setResultatsScolarite($request->request->get('resultats_scolarite') ?: null);
        $r->setSituationSante($request->request->get('situation_sante') ?: null);
        $r->setMessageParrain($request->request->get('message_parrain') ?: null);

        // ── Upload document PDF (max 15 Mo) ────────────────────────────
        if ($doc = $request->files->get('document'))
            if ($fn = $this->moveFile($doc, 'rapports/parrainages', $slugger, 15)) {
                $r->setDocumentFilename($fn);
                $r->setDocumentOriginalName($doc->getClientOriginalName());
            }

        $em->persist($r);
        $em->flush();

        $this->flash($request, 'success',
            sprintf('Rapport S%d %d créé avec succès.', $semestre, $annee),
            sprintf('تم إنشاء تقرير S%d %d بنجاح.', $semestre, $annee),
            sprintf('Report S%d %d created successfully.', $semestre, $annee)
        );
        return $this->show_redirect($fiche);
    }

    // ══════════════════════════════════════════════
    // HELPERS PRIVÉS
    // ══════════════════════════════════════════════

    /**
     * Ajoute un message flash traduit selon la locale courante (fr / ar / en).
     *
     * Usage : $this->flash($request, 'success', 'Dossier créé.', 'تم إنشاء الملف.', 'File created.')
     *
     * Les flash messages sont affichés par base.html.twig (bloc flash-mo).
     */
    private function flash(Request $request, string $type, string $fr, string $ar = '', string $en = ''): void
    {
        $locale = $request->getLocale();
        $msg = match($locale) {
            'ar'    => $ar ?: $fr,
            'en'    => $en ?: $fr,
            default => $fr,
        };
        $this->addFlash($type, $msg);
    }

    /**
     * Hydrate tous les champs de la fiche depuis le formulaire.
     * Utilisé dans new() et edit() pour éviter la duplication.
     */
    private function hydrate(ParrainageOrphelin $f, Request $r): void
    {
        $req = $r->request;

        if ($v = trim((string) $req->get('nom_complet', ''))) $f->setNomComplet($v);

        $f->setCin($req->get('cin') ?: null);
        $f->setGenre($req->get('genre', 'masculin'));
        $f->setLieuNaissance($req->get('lieu_naissance') ?: null);
        $f->setTypeOrphelin($req->get('type_orphelin', ParrainageOrphelin::TYPE_PERE_DECEDE));

        // Dates
        foreach ([
            'date_naissance'  => 'setDateNaissance',
            'date_deces_pere' => 'setDateDecesPere',
            'date_deces_mere' => 'setDateDecesMere',
        ] as $field => $setter) {
            if ($d = $req->get($field)) try { $f->$setter(new \DateTime($d)); } catch (\Exception) {}
        }

        // Famille
        $f->setNomMere($req->get('nom_mere') ?: null);
        $f->setMereMariee((bool) $req->get('mere_mariee', false));
        $f->setNomTuteur($req->get('nom_tuteur') ?: null);
        $f->setRelationTuteur($req->get('relation_tuteur') ?: null);
        $f->setNbFreres(max(0, (int) $req->get('nb_freres', 0)));
        $f->setNbSoeurs(max(0, (int) $req->get('nb_soeurs', 0)));
        $f->setAdresse($req->get('adresse') ?: null);
        $f->setTelephone($req->get('telephone') ?: null);
        $f->setEtatSante($req->get('etat_sante') ?: null);

        // Scolarité
        $f->setEcole($req->get('ecole') ?: null);
        $f->setNiveauScolaire($req->get('niveau_scolaire') ?: null);
        $f->setRaisonNonScolarisation($req->get('raison_non_scolarisation') ?: null);
    }

    /**
     * Redirection rapide vers la page show de la fiche.
     */
    private function show_redirect(ParrainageOrphelin $fiche): Response
    {
        return $this->redirectToRoute('app_parrainage_orphelin_show', ['id' => $fiche->getId()]);
    }

    /**
     * Déplace un fichier uploadé vers public/uploads/{subDir}/
     *
     * IMPORTANT : on vérifie d'abord isValid() AVANT tout appel à getMimeType()
     * car Symfony lève une InvalidArgumentException si le fichier est vide
     * ou si le chemin temporaire n'existe plus (ex: formulaire soumis sans fichier).
     *
     * @param  string $subDir  Sous-dossier dans public/uploads/ (ex: "orphelins")
     * @param  int    $maxMo   Taille max en Mo (défaut 5)
     * @return string|null     Nom du fichier généré, ou null si rejet/erreur
     */
    private function moveFile(UploadedFile $file, string $subDir, SluggerInterface $slugger, int $maxMo = 5): ?string
    {
        // ── 1. Vérifier que le fichier est valide (upload réel sans erreur HTTP)
        //       getMimeType() crashe avec InvalidArgumentException si !isValid()
        if (!$file->isValid()) {
            return null;
        }

        // ── 2. Valider le type MIME (uniquement images + PDF)
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowed, true)) {
            return null;
        }

        // ── 3. Valider la taille maximale
        if ($file->getSize() > $maxMo * 1024 * 1024) {
            return null;
        }

        // ── 4. Générer un nom de fichier unique et sécurisé
        $safe     = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $safe . '-' . uniqid() . '.' . $file->guessExtension();
        $dir      = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subDir;

        // ── 5. Créer le dossier si inexistant
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // ── 6. Déplacer le fichier temporaire vers sa destination finale
        try {
            $file->move($dir, $filename);
            return $filename;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Supprime un fichier physique de public/uploads/{subDir}/
     */
    private function removeFile(string $subDir, ?string $filename): void
    {
        if (!$filename) return;
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subDir . '/' . $filename;
        if (file_exists($path)) @unlink($path);
    }
}