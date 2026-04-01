<?php

namespace App\Controller;

use App\Entity\ParrainageImam;
use App\Form\ParrainageImamType;
use App\Repository\ParrainageImamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/parrainage/imam')]
final class ParrainageImamController extends AbstractController
{
    #[Route(name: 'app_parrainage_imam_index', methods: ['GET'])]
    public function index(ParrainageImamRepository $parrainageImamRepository): Response
    {
        return $this->render('parrainage_imam/index.html.twig', [
            'parrainage_imams' => $parrainageImamRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_parrainage_imam_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $parrainageImam = new ParrainageImam();
        $form = $this->createForm(ParrainageImamType::class, $parrainageImam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parrainageImam);
            $entityManager->flush();

            return $this->redirectToRoute('app_parrainage_imam_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parrainage_imam/new.html.twig', [
            'parrainage_imam' => $parrainageImam,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_parrainage_imam_show', methods: ['GET'])]
    public function show(ParrainageImam $parrainageImam): Response
    {
        return $this->render('parrainage_imam/show.html.twig', [
            'parrainage_imam' => $parrainageImam,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_parrainage_imam_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ParrainageImam $parrainageImam, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ParrainageImamType::class, $parrainageImam);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_parrainage_imam_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('parrainage_imam/edit.html.twig', [
            'parrainage_imam' => $parrainageImam,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_parrainage_imam_delete', methods: ['POST'])]
    public function delete(Request $request, ParrainageImam $parrainageImam, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$parrainageImam->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($parrainageImam);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_parrainage_imam_index', [], Response::HTTP_SEE_OTHER);
    }
}
