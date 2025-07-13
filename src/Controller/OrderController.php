<?php
// src/Controller/OrderController.php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OrderController extends AbstractController
{
    #[Route('/orders', name: 'app_order_index')]
    #[IsGranted('ROLE_USER')] // Solo usuarios logueados
    public function index(OrderRepository $orderRepository): Response
        {
            // Obtener pedidos del usuario actual
            $user = $this->getUser();

            //$orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
            $orders = $orderRepository->findAll();
            return $this->render('order/index.html.twig', [
                'orders' => $orders,
            ]);
        }

    // En OrderController.php

    #[Route('/orders/{id}', name: 'app_order_show')]
    //#[IsGranted('ROLE_USER')]
    public function show(\App\Entity\Order $order): Response
        {
            // Verifica que el pedido pertenezca al usuario actual
            //$this->denyAccessUnlessGranted('view', $order);

            return $this->render('order/detail.html.twig', [
                'order' => $order,
            ]);
        }

}
