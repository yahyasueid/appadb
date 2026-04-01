<?php
// src/Controller/ParrainageFamilleController.php
namespace App\Controller;

use App\Entity\Parrainage;
use App\Entity\ParrainageFamille;
use App\Entity\ParrainagePaiement;
use App\Entity\RapportParrainage;
use App\Repository\AssociationRepository;
use App\Repository\ParrainRepository;
use App\Repository\ParrainageFamilleRepository;
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

#[Route('/parrainage/famille')]
#[IsGranted('ROLE_USER')]
class ParrainageFamilleController extends AbstractController
{
    // ══ INDEX ══════════════════════════════════════

    #[Route('/', name: 'app_parrainage_famille_index', methods: ['GET'])]
    public function index(
        Request $request,
        AssociationRepository $assocRepo,
        ParrainageFamilleRepository $familleRepo,
        ParrainRepository $parrainRepo
    ): Response {
        $associations = $assocRepo->findBy(['isActive' => true], ['nom' => 'ASC']);

        $assocActive = null;
        if ($assocId = $request->query->getInt('assoc'))
            foreach ($associations as $a)
                if ($a->getId() === $assocId) { $assocActive = $a; break; }

        $fichesByAssoc = [];
        $totalFamilles = 0;
        $totalActifs   = 0;
        $statuts = [
            Parrainage::STATUT_NOUVEAU  => 0,
            Parrainage::STATUT_APPROUVE => 0,
            Parrainage::STATUT_ACTIF    => 0,
            Parrainage::STATUT_ANNULE   => 0,
        ];

        foreach ($associations as $assoc) {
            $fiches = $familleRepo->findByAssociation($assoc);
            $fichesByAssoc[$assoc->getId()] = $fiches;
            $totalFamilles += count($fiches);
            foreach ($fiches as $fiche) {
                $s = $fiche->getParrainage()->getStatut();
                if (array_key_exists($s, $statuts)) $statuts[$s]++;
                if ($fiche->getParrainage()->isActif()) $totalActifs++;
            }
        }

        $parrains = $assocActive
            ? $parrainRepo->findByAssociation($assocActive)
            : $parrainRepo->findBy([], ['nom' => 'ASC']);

        return $this->render('famille/index.html.twig', [
            'associations'  => $associations,
            'fichesByAssoc' => $fichesByAssoc,
            'assocActive'   => $assocActive,
            'totalFamilles' => $totalFamilles,
            'totalActifs'   => $totalActifs,
            'statuts'       => $statuts,
            'parrains'      => $parrains,
        ]);
    }

    // ══ SHOW ═══════════════════════════════════════

    #[Route('/{id}', name: 'app_parrainage_famille_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(ParrainageFamille $fiche, ParrainageRepository $parrainageRepo, RapportParrainageRepository $rapportRepo): Response
    {
        $parrainage = $parrainageRepo->findWithDetails($fiche->getParrainage()->getId());
        return $this->render('famille/show.html.twig', [
            'fiche'      => $fiche,
            'parrainage' => $parrainage,
            'rapports'   => $rapportRepo->findByParrainage($parrainage),
        ]);
    }

    // ══ DATA JSON ══════════════════════════════════

    #[Route('/{id}/data', name: 'app_parrainage_famille_data', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function data(ParrainageFamille $fiche): JsonResponse
    {
        return $this->json([
            'id'                 => $fiche->getId(),
            'nomChef'            => $fiche->getNomChef(),
            'cinChef'            => $fiche->getCinChef(),
            'dateNaissance'      => $fiche->getDateNaissance()?->format('Y-m-d'),
            'genre'              => $fiche->getGenre(),
            'niveauEducatif'     => $fiche->getNiveauEducatif(),
            'adresseSkn'         => $fiche->getAdresseSкn(),
            'typeLogement'       => $fiche->getTypeLogement(),
            'etatLogement'       => $fiche->getEtatLogement(),
            'telephone'          => $fiche->getTelephone(),
            'telephone2'         => $fiche->getTelephone2(),
            'revenuMensuelTotal' => $fiche->getRevenuMensuelTotal(),
            'emploiChef'         => $fiche->getEmploiChef(),
            'sourceRevenu'       => $fiche->getSourceRevenu(),
            'raisonDemande'      => $fiche->getRaisonDemande(),
            'etatSante'          => $fiche->getEtatSante(),
            'nombreMembres'      => $fiche->getNombreMembres(),
        ]);
    }

    // ══ NEW ════════════════════════════════════════

    #[Route('/new', name: 'app_parrainage_famille_new', methods: ['POST'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function new(Request $request, EntityManagerInterface $em, AssociationRepository $assocRepo, ParrainRepository $parrainRepo, SluggerInterface $slugger): Response
    {
        if (!$this->isCsrfTokenValid('famille_new', $request->request->get('_token'))) {
            $this->flash($request, 'error', 'Token de sécurité invalide.', 'رمز الأمان غير صالح.', 'Invalid security token.');
            return $this->redirectToRoute('app_parrainage_famille_index');
        }

        $assoc   = $assocRepo->find((int) $request->request->get('association'));
        $parrain = $parrainRepo->find((int) $request->request->get('parrain'));
        if (!$assoc || !$parrain) {
            $this->flash($request, 'error', 'Association ou parrain introuvable.', 'الجمعية أو الكافل غير موجود.', 'Association or sponsor not found.');
            return $this->redirectToRoute('app_parrainage_famille_index');
        }

        $par = new Parrainage();
        $par->setAssociation($assoc)->setParrain($parrain)
            ->setType(Parrainage::TYPE_FAMILLE)->setStatut(Parrainage::STATUT_NOUVEAU)->setCreePar($this->getUser());

        $count = $em->getRepository(Parrainage::class)->count(['association' => $assoc, 'type' => Parrainage::TYPE_FAMILLE]);
        $par->setNumero(sprintf('PAR-%d-%04d', (int) date('Y'), $count + 1));

        if ($m = $request->request->get('montant_periodique')) $par->setMontantPeriodique(number_format((float)$m, 2, '.', ''));
        if ($p = $request->request->get('periodicite')) $par->setPeriodicite($p);
        if ($d = $request->request->get('date_debut')) try { $par->setDateDebut(new \DateTime($d)); } catch (\Exception) {}

        $fiche = new ParrainageFamille();
        $fiche->setParrainage($par);
        $this->hydrate($fiche, $request);

        $em->persist($par);
        $em->persist($fiche);
        $em->flush();

        $this->flash($request, 'success',
            sprintf('Dossier famille "%s" créé — réf. %s.', $fiche->getNomChef(), $par->getNumero()),
            sprintf('تم إنشاء ملف الأسرة "%s" — المرجع: %s.', $fiche->getNomChef(), $par->getNumero()),
            sprintf('Family file "%s" created — ref. %s.', $fiche->getNomChef(), $par->getNumero())
        );
        return $this->redirectToRoute('app_parrainage_famille_show', ['id' => $fiche->getId()]);
    }

    // ══ EDIT ═══════════════════════════════════════

    #[Route('/{id}/edit', name: 'app_parrainage_famille_edit', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function edit(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('famille_edit', $request->request->get('_token'))) {
            $this->flash($request, 'error', 'Token de sécurité invalide.', 'رمز الأمان غير صالح.', 'Invalid security token.');
            return $this->show_redirect($fiche);
        }
        $this->hydrate($fiche, $request);
        $em->flush();
        $this->flash($request, 'success',
            sprintf('Dossier "%s" mis à jour.', $fiche->getNomChef()),
            sprintf('تم تحديث ملف "%s".', $fiche->getNomChef()),
            sprintf('File "%s" updated.', $fiche->getNomChef())
        );
        return $this->show_redirect($fiche);
    }

    // ══ DELETE ═════════════════════════════════════

    #[Route('/{id}/delete', name: 'app_parrainage_famille_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function delete(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('famille_delete', $request->request->get('_token'))) {
            $this->flash($request, 'error', 'Token de sécurité invalide.', 'رمز الأمان غير صالح.', 'Invalid security token.');
            return $this->redirectToRoute('app_parrainage_famille_index');
        }
        $nom     = $fiche->getNomChef();
        $assocId = $fiche->getParrainage()->getAssociation()->getId();
        $em->remove($fiche->getParrainage());
        $em->flush();
        $this->flash($request, 'success',
            sprintf('Dossier famille "%s" supprimé définitivement.', $nom),
            sprintf('تم حذف ملف الأسرة "%s" نهائياً.', $nom),
            sprintf('Family file "%s" permanently deleted.', $nom)
        );
        return $this->redirectToRoute('app_parrainage_famille_index', ['assoc' => $assocId]);
    }

    // ══ TRANSITIONS ════════════════════════════════

    #[Route('/{id}/approuver', name: 'app_parrainage_famille_approuver', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function approuver(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('famille_approuver', $request->request->get('_token'))) return $this->show_redirect($fiche);
        $par = $fiche->getParrainage();
        if (!$par->canApprouver()) {
            $this->flash($request, 'warning', 'Ce dossier ne peut pas être approuvé depuis son statut actuel.', 'لا يمكن اعتماد هذا الملف من حالته الحالية.', 'Cannot approve from current status.');
            return $this->show_redirect($fiche);
        }
        $par->approuver($this->getUser()); $em->flush();
        $this->flash($request, 'success', sprintf('"%s" approuvé — معتمدة.', $fiche->getNomChef()), sprintf('تمت الموافقة على "%s" — معتمدة.', $fiche->getNomChef()), sprintf('"%s" approved.', $fiche->getNomChef()));
        return $this->show_redirect($fiche);
    }

    #[Route('/{id}/activer', name: 'app_parrainage_famille_activer', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function activer(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('famille_activer', $request->request->get('_token'))) return $this->show_redirect($fiche);
        $par = $fiche->getParrainage();
        if (!$par->canActiver()) {
            $this->flash($request, 'warning', 'Ce dossier ne peut pas être activé depuis son statut actuel.', 'لا يمكن تفعيل هذا الملف من حالته الحالية.', 'Cannot activate from current status.');
            return $this->show_redirect($fiche);
        }
        $par->activer(); $em->flush();
        $date = $par->getDateDebut()?->format('d/m/Y') ?? date('d/m/Y');
        $this->flash($request, 'success', sprintf('"%s" activé — مكفول à partir du %s.', $fiche->getNomChef(), $date), sprintf('تم تفعيل "%s" — مكفول ابتداءً من %s.', $fiche->getNomChef(), $date), sprintf('"%s" activated as of %s.', $fiche->getNomChef(), $date));
        return $this->show_redirect($fiche);
    }

    #[Route('/{id}/annuler', name: 'app_parrainage_famille_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_DIRECTEUR_PARRAINAGES')]
    public function annuler(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('famille_annuler', $request->request->get('_token'))) return $this->show_redirect($fiche);
        $par = $fiche->getParrainage();
        if (!$par->canAnnuler()) {
            $this->flash($request, 'warning', 'Ce dossier ne peut pas être annulé depuis son statut actuel.', 'لا يمكن إلغاء هذا الملف من حالته الحالية.', 'Cannot cancel from current status.');
            return $this->show_redirect($fiche);
        }
        $par->annuler();
        if ($raison = trim((string) $request->request->get('raison', ''))) {
            $notes = trim($par->getNotes() ?? '');
            $par->setNotes($notes ? $notes . "\n[Annulation] " . $raison : '[Annulation] ' . $raison);
        }
        $em->flush();
        $this->flash($request, 'success', sprintf('"%s" annulé — ملغي.', $fiche->getNomChef()), sprintf('تم إلغاء "%s" — ملغي.', $fiche->getNomChef()), sprintf('"%s" cancelled.', $fiche->getNomChef()));
        return $this->show_redirect($fiche);
    }

    // ══ PAIEMENT ═══════════════════════════════════

    #[Route('/{id}/paiement', name: 'app_parrainage_famille_paiement', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function paiement(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        if (!$this->isCsrfTokenValid('famille_paiement', $request->request->get('_token'))) return $this->show_redirect($fiche);
        $montant = (float) $request->request->get('montant');
        if ($montant <= 0) {
            $this->flash($request, 'error', 'Le montant doit être un nombre positif.', 'يجب أن يكون المبلغ عدداً موجباً.', 'Amount must be positive.');
            return $this->show_redirect($fiche);
        }
        $p = new ParrainagePaiement();
        $p->setParrainage($fiche->getParrainage())->setMontant(number_format($montant, 2, '.', ''))->setSaisirPar($this->getUser())
          ->setMode($request->request->get('mode', ParrainagePaiement::MODE_ESPECES))
          ->setReference($request->request->get('reference') ?: null)
          ->setPeriodeConcernee($request->request->get('periode_concernee') ?: null)
          ->setNotes($request->request->get('notes') ?: null);
        if ($d = $request->request->get('date_paiement')) try { $p->setDatePaiement(new \DateTime($d)); } catch (\Exception) {}
        if ($file = $request->files->get('justificatif'))
            if ($fn = $this->moveFile($file, 'parrainages/paiements', $slugger, 15)) { $p->setJustificatifFilename($fn); $p->setJustificatifOriginalName($file->getClientOriginalName()); }
        $em->persist($p); $em->flush();
        $this->flash($request, 'success', sprintf('Versement de %s MRU enregistré.', number_format($montant, 0, '.', ' ')), sprintf('تم تسجيل دفعة بقيمة %s أوقية.', number_format($montant, 0, '.', ' ')), sprintf('Payment of %s MRU recorded.', number_format($montant, 0, '.', ' ')));
        return $this->show_redirect($fiche);
    }

    // ══ RAPPORT ════════════════════════════════════

    #[Route('/{id}/rapport', name: 'app_parrainage_famille_rapport', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EMPLOYE_PARRAINAGES')]
    public function rapport(ParrainageFamille $fiche, Request $request, EntityManagerInterface $em, RapportParrainageRepository $rapportRepo, SluggerInterface $slugger): Response
    {
        if (!$this->isCsrfTokenValid('famille_rapport', $request->request->get('_token'))) return $this->show_redirect($fiche);
        $par = $fiche->getParrainage();
        $annee = (int) $request->request->get('annee', date('Y'));
        $semestre = (int) $request->request->get('semestre', 1);
        if ($rapportRepo->findOneByPeriode($par, $annee, $semestre)) {
            $this->flash($request, 'warning', sprintf('Un rapport S%d %d existe déjà.', $semestre, $annee), sprintf('يوجد تقرير S%d %d بالفعل.', $semestre, $annee), sprintf('A S%d %d report already exists.', $semestre, $annee));
            return $this->show_redirect($fiche);
        }
        $r = new RapportParrainage();
        $r->setParrainage($par)->setAnnee($annee)->setSemestre($semestre)->setStatut(RapportParrainage::STATUT_BROUILLON)->setCreePar($this->getUser())
          ->setTitre(sprintf('Rapport S%d %d — %s', $semestre, $annee, $fiche->getNomChef()))
          ->setSituationGenerale($request->request->get('situation_generale') ?: null)
          ->setResultatsScolarite($request->request->get('resultats_scolarite') ?: null)
          ->setSituationSante($request->request->get('situation_sante') ?: null)
          ->setMessageParrain($request->request->get('message_parrain') ?: null);
        if ($doc = $request->files->get('document'))
            if ($fn = $this->moveFile($doc, 'rapports/parrainages', $slugger, 15)) { $r->setDocumentFilename($fn); $r->setDocumentOriginalName($doc->getClientOriginalName()); }
        $em->persist($r); $em->flush();
        $this->flash($request, 'success', sprintf('Rapport S%d %d créé.', $semestre, $annee), sprintf('تم إنشاء تقرير S%d %d.', $semestre, $annee), sprintf('Report S%d %d created.', $semestre, $annee));
        return $this->show_redirect($fiche);
    }

    // ══ HELPERS ════════════════════════════════════

    private function flash(Request $request, string $type, string $fr, string $ar = '', string $en = ''): void
    {
        $locale = $request->getLocale();
        $this->addFlash($type, match($locale) { 'ar' => $ar ?: $fr, 'en' => $en ?: $fr, default => $fr });
    }

    private function hydrate(ParrainageFamille $f, Request $r): void
    {
        $req = $r->request;
        if ($v = trim((string) $req->get('nom_chef', ''))) $f->setNomChef($v);
        $f->setCinChef($req->get('cin_chef') ?: null);
        $f->setGenre($req->get('genre') ?: null);
        $f->setNiveauEducatif($req->get('niveau_educatif') ?: null);
        if ($d = $req->get('date_naissance')) try { $f->setDateNaissance(new \DateTime($d)); } catch (\Exception) {}
        $f->setAdresseSкn($req->get('adresse_skn') ?: null);
        $f->setTypeLogement($req->get('type_logement') ?: null);
        $f->setEtatLogement($req->get('etat_logement') ?: null);
        $f->setTelephone($req->get('telephone') ?: null);
        $f->setTelephone2($req->get('telephone2') ?: null);
        if ($rev = $req->get('revenu_mensuel_total')) $f->setRevenuMensuelTotal(number_format((float)$rev, 2, '.', ''));
        $f->setEmploiChef($req->get('emploi_chef') ?: null);
        $f->setSourceRevenu($req->get('source_revenu') ?: null);
        $f->setRaisonDemande($req->get('raison_demande') ?: null);
        $f->setEtatSante($req->get('etat_sante') ?: null);
        if (($n = $req->get('nombre_membres')) !== null) $f->setNombreMembres(max(1, (int)$n));
    }

    private function show_redirect(ParrainageFamille $fiche): Response
    {
        return $this->redirectToRoute('app_parrainage_famille_show', ['id' => $fiche->getId()]);
    }

    private function moveFile(UploadedFile $file, string $subDir, SluggerInterface $slugger, int $maxMo = 5): ?string
    {
        if (!$file->isValid()) return null;
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
        if (!in_array($file->getMimeType(), $allowed, true)) return null;
        if ($file->getSize() > $maxMo * 1024 * 1024) return null;
        $safe = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $safe . '-' . uniqid() . '.' . $file->guessExtension();
        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subDir;
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        try { $file->move($dir, $filename); return $filename; } catch (\Exception) { return null; }
    }

    private function removeFile(string $subDir, ?string $filename): void
    {
        if (!$filename) return;
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subDir . '/' . $filename;
        if (file_exists($path)) @unlink($path);
    }
}