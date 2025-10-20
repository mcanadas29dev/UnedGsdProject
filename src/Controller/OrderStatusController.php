<?php

namespace App\Controller;

use App\Entity\OrderStatus;
use App\Form\OrderStatusType;
use App\Repository\OrderStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/order/status')]
#[IsGranted('ROLE_ADMIN')]
final class OrderStatusController extends AbstractController
{
    #[Route(name: 'app_order_status_index', methods: ['GET'])]
    public function index(
        OrderStatusRepository $orderStatusRepository,
        Request $request,
        PaginatorInterface $paginator)
        : Response {
            // 0. Capturamos el término de búsqueda
             $search = $request->query->get('find_orderStatus'); 

             // 1. Consulta base

            $queryBuilder = $orderStatusRepository->createQueryBuilder('p')
            ->orderBy('p.id');
             if ($search) {
                $queryBuilder->andWhere('p.name LIKE :search')
                            ->setParameter('search', '%' . $search . '%');
            }
            /*
            $query = $orderStatusRepository->createQueryBuilder('os')
                ->orderBy('os.id', 'ASC')
                ->getQuery();
            */
            // 2. Aplicamos la paginación

            $pagination = $paginator->paginate(
                $queryBuilder->getQuery(),
                $request->query->getInt('page', 1),
                10
            );

            /*
            $orderStatuses = $paginator->paginate(
                $queryBuilder,
                $request->query->getInt('page', 1), // Página actual
                10 // Elementos por página
            );
           */

        // 3. Renderizamos la vista
        if ($request->isXmlHttpRequest()) {
            return $this->render('order_status/_table.html.twig', [
                'order_statuses' => $pagination,
            ]);
        }

        return $this->render('order_status/index.html.twig', [
            'order_statuses' => $pagination,
            'search' => $search,
        ]);
        /*
        return $this->render('order_status/index.html.twig', [
            'order_statuses' => $orderStatusRepository->findAll(),
        ]);
        */
    }

    #[Route('/new', name: 'app_order_status_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $orderStatus = new OrderStatus();
        $form = $this->createForm(OrderStatusType::class, $orderStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($orderStatus);
            $entityManager->flush();

            return $this->redirectToRoute('app_order_status_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order_status/new.html.twig', [
            'order_status' => $orderStatus,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_status_show', methods: ['GET'])]
    public function show(OrderStatus $orderStatus): Response
    {
        return $this->render('order_status/show.html.twig', [
            'order_status' => $orderStatus,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_status_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OrderStatus $orderStatus, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OrderStatusType::class, $orderStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_order_status_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('order_status/edit.html.twig', [
            'order_status' => $orderStatus,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_order_status_delete', methods: ['POST'])]
    public function delete(Request $request, OrderStatus $orderStatus, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$orderStatus->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($orderStatus);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_order_status_index', [], Response::HTTP_SEE_OTHER);
    }
}
