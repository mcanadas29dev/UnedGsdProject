<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/offer')]
class OfferController extends AbstractController
{
    #[Route('/', name: 'offer_index', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        
        return $this->render('offer/offer.html.twig', [
            'offers' => $offerRepository->findActive(),
            // 'offers' => $offerRepository->findActive(), para ofertas activas
        ]);
        

    }

    #[Route('/admin', name: 'offer_index_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index_admin(OfferRepository $offerRepository, PaginatorInterface $paginator, Request $request): Response
    {
        /*
        return $this->render('offer/index.html.twig', [
            // 'offers' => $offerRepository->findAll(),
            'offers' => $offerRepository->findAll(),
        ]);
        */
         $query = $offerRepository->createQueryBuilder('o')
        ->orderBy('o.startDate', 'DESC')
        ->getQuery();

        $pagination = $paginator->paginate(
            $query, /* consulta */
            $request->query->getInt('page', 1), /* página actual */
            10 /* nº resultados por página */
        );

        return $this->render('offer/index.html.twig', [
            'offers' => $pagination,
        ]);
    }


    #[Route('/new', name: 'offer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($offer);
            $em->flush();

            $this->addFlash('success', 'Oferta creada correctamente');
            return $this->redirectToRoute('offer_index');
        }

        return $this->render('offer/new.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Crear nueva oferta',
        ]);
    }

    #[Route('/{id}', name: 'offer_show', methods: ['GET'])]
    public function show(Offer $offer): Response
    {
        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}/edit', name: 'offer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Offer $offer, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Oferta actualizada correctamente');
            return $this->redirectToRoute('offer_index');
        }

        return $this->render('offer/edit.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Editar oferta',
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}', name: 'offer_delete', methods: ['POST'])]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->request->get('_token'))) {
            $em->remove($offer);
            $em->flush();

            $this->addFlash('danger', 'Oferta eliminada');
        }

        return $this->redirectToRoute('offer_index');
    }
}
