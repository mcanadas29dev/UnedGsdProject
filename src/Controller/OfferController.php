<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Form\OfferType;
use App\Repository\OfferRepository;
use App\Service\OfferService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/offer')]

class OfferController extends AbstractController
{
    #[Route('/', name: 'offer_index', methods: ['GET'])]
    public function index(OfferRepository $offerRepository): Response
    {
        return $this->render('offer/offer.html.twig', [
            'offers' => $offerRepository->findActive(new \DateTimeImmutable()),
            // 'offers' => $offerRepository->findActive(), para ofertas activas
        ]);
    }

    #[Route('/admin', name: 'offer_index_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index_admin(OfferRepository $offerRepository, PaginatorInterface $paginator, Request $request): Response
    {
        /*

         $query = $offerRepository->createQueryBuilder('o')
        ->orderBy('o.startDate', 'DESC')
        ->getQuery();

        $pagination = $paginator->paginate(
            $query, 
            $request->query->getInt('page', 1), 
            10 
        );

        return $this->render('offer/index.html.twig', [
            'offers' => $pagination,
        ]);
        
        $search = $request->query->get('q');   // búsqueda por producto
        $date   = $request->query->get('date'); // búsqueda por fecha

        $qb = $offerRepository->createQueryBuilder('o')
            ->join('o.product', 'p')
            ->addSelect('p')
            ->orderBy('o.startDate', 'DESC');

        if ($search) {
            $qb->andWhere('p.name LIKE :search')
            ->setParameter('search', '%' . $search . '%');
        }

        if ($date) {
            try {
                
                $dateObj = new \DateTime($date);
                $startOfDay = $dateObj->setTime(0,0,0);
                $endOfDay   = $dateObj->setTime(23,59,59);

                $qb->andWhere('(o.startDate BETWEEN :start AND :end OR o.endDate BETWEEN :start AND :end)')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay);
            } catch (\Exception $e) {
                // fecha inválida, ignorar
            }
        }


        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('offer/index.html.twig', [
            'offers' => $pagination,
            'search' => $search,
            'date'   => $date,
        ]);
        */
            $search = $request->query->get('q');   // búsqueda por producto
            $date   = $request->query->get('date'); // búsqueda por fecha

            $qb = $offerRepository->createQueryBuilder('o')
                ->join('o.product', 'p')
                ->addSelect('p')
                ->orderBy('o.startDate', 'DESC');

            if ($search) {
                $qb->andWhere('p.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
            }

            if ($date) {
                try {
                    $dateObj = new \DateTime($date);
                    $startOfDay = (clone $dateObj)->setTime(0, 0, 0);
                    $endOfDay = (clone $dateObj)->setTime(23, 59, 59);

                    $qb->andWhere('(o.startDate BETWEEN :start AND :end OR o.endDate BETWEEN :start AND :end)')
                    ->setParameter('start', $startOfDay)
                    ->setParameter('end', $endOfDay);
                } catch (\Exception $e) {
                    // fecha inválida
                }
            }

            $pagination = $paginator->paginate(
                $qb->getQuery(),
                $request->query->getInt('page', 1),
                10
            );

            // ⚡️ Si es una petición AJAX, devolvemos solo la tabla
            if ($request->isXmlHttpRequest()) {
                return $this->render('offer/_table.html.twig', [
                    'offers' => $pagination,
                ]);
            }

            // Vista completa
            return $this->render('offer/index.html.twig', [
                'offers' => $pagination,
                'search' => $search,
                'date'   => $date,
            ]);
    }


    #[Route('/new', name: 'offer_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em, OfferService $offerService): Response
    {
        $offer = new Offer();
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($offerService->offerActive($offer)){
                 $this->addFlash('danger', 'Este producto ya tiene una oferta activa, revise');
            }
            else{
                $em->persist($offer);
                $em->flush();
                $this->addFlash('success', 'Oferta creada correctamente');
                return $this->redirectToRoute('offer_index_admin');
            }
            
           
        }

        return $this->render('offer/new.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Crear nueva oferta',
        ]);
    }

    #[Route('/{id}', name: 'offer_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Offer $offer): Response
    {
        return $this->render('offer/show.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}/edit', name: 'offer_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, OfferService $offerService,Offer $offer, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(OfferType::class, $offer);
        $form->handleRequest($request);
        // Revisar para refactorizar en Servicios (Fechas, Precios)
        if ($form->isSubmitted() && $form->isValid()) {
            if($offerService->datesOk($offer)){
                if($offerService->priceOK($offer)){
                    try{
                        $em->flush();
                        $this->addFlash('success', sprintf('Oferta %d actualizada correctamente', $offer->getId()));
                    }catch(Exception $e){
                        $this->addFlash('danger', sprintf('Ha ocurrido un error ', $e->getMessage()));
                    }
                    finally{
                        return $this->redirectToRoute('offer_index_admin');
                    }
                   
                }else{
                    $this->addFlash('danger', 'El precio de oferta no puede ser 0 o negativo'); // Que el precio <=0
                }
            }else{
                    $this->addFlash('danger', 'Revise fechas inicio y fin ');
            }
        }

        return $this->render('offer/edit.html.twig', [
            'form' => $form->createView(),
            'form_title' => 'Editar oferta',
            'offer' => $offer,
        ]);
    }

    #[Route('/{id}', name: 'offer_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Offer $offer, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $offer->getId(), $request->request->get('_token'))) {
            $em->remove($offer);
            $em->flush();

            $this->addFlash('danger', 'Oferta eliminada');
        }

        return $this->redirectToRoute('offer_index_admin');
    }
}
