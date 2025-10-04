<?php

namespace App\Controller;

use Knp\Component\Pager\PaginatorInterface;
use App\Repository\OrderRepository;
use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


class OrderController extends AbstractController
{
    // Acceso para usuarios normales (NO admins)
    #[Route('/orders', name: 'app_order_user')]
    //#[IsGranted('ROLE_USER')]
    public function userOrders(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        /* 04-10-2025
        $query = $request->query->get('find_order');
        // Construimos un QueryBuilder en lugar de usar findBy()
        $qb = $orderRepository->createQueryBuilder('o')
        ->andWhere('o.user = :user')
        ->setParameter('user', $user)
        ->orderBy('o.createdAt', 'DESC');
        
        if ($query) {
            $qb->andWhere('o.id LIKE :search OR o.email LIKE :search OR o.status LIKE :search')
                ->setParameter('search', '%' . $query . '%');
        }
        */ 
        // Tiene que mostrar solo sus pedidos
        $query = $request->query->get('find_order');
            $qb = $orderRepository->createQueryBuilder('o')
            ->join('o.user', 'u')     // JOIN con User
            ->join('o.status', 's')   // JOIN con Status si necesitas filtrar por estado
            ->where('o.user = :currentUser')
            ->setParameter('currentUser', $user)
            ->orderBy('o.createdAt', 'DESC');

            if ($query) {
                $qb->andWhere('o.id LIKE :search OR u.email LIKE :search OR s.name LIKE :search')
                    ->setParameter('search', '%' . $query . '%');
            }
        
        $pagination = $paginator->paginate(
            $qb, // QueryBuilder
            $request->query->getInt('page', 1), // Página actual, por defecto 1
            10 // Elementos por página
        );
        
        return $this->render('order/index.html.twig', [
            'orders' => $pagination,
            'tituloAlmacen' => 'Listado de Pedidos de ' . $user->getUserIdentifier(),
            'find_order' => $query
        ]);
    }
    

    // Acceso para usuarios normales (NO admins)
    #[Route('/orders_original', name: 'app_order_user_original')]
    #[IsGranted('ROLE_USER')]
    public function userOrders_original(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();

        if (!$user || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Solo los usuarios pueden acceder a esta página.');
        }

        $orders = $orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            
        );

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'tituloAlmacen' => 'Mis Pedidos',
        ]);
    }

    // Acceso para admins para ver todos los pedidos
    /*
    #[Route('/admin/orders', name: 'app_order_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminOrders(OrderRepository $orderRepository): Response
    {
        $orders = $orderRepository->findAll();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
            'tituloAlmacen' => 'Listado de Pedidos',
        ]);
    } */

    // Pedidos con paginación 

    #[Route('/admin/orders', name: 'app_order_index')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminOrders(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
       
            $query = $request->query->get('find_order');
            $qb = $orderRepository->createQueryBuilder('o')
            ->join('o.user', 'u')     // JOIN con User
            ->join('o.status', 's')   // JOIN con Status si necesitas filtrar por estado
            ->orderBy('o.createdAt', 'DESC');

            if ($query) {
                $qb->andWhere('o.id LIKE :search OR u.email LIKE :search OR s.name LIKE :search')
                    ->setParameter('search', '%' . $query . '%');
            }

        
        $pagination = $paginator->paginate(
            $qb, // QueryBuilder
            $request->query->getInt('page', 1), // Página actual, por defecto 1
            10 // Elementos por página
        );
        
      
        
        return $this->render('order/index.html.twig', [
            'orders' => $pagination,
            'tituloAlmacen' => 'Pedidos',
            'find_order' => $query
        ]);
    }

    // Acceso para Personal de almacén Pickers para ver los pedidos pagados que tienen que preparar.
    #[Route('/almacen/orders', name: 'app_order_storage')]
    #[IsGranted('ROLE_PICKER')]
    public function pickerOrders(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    { 
        /*
        $query = $request->query->get('find_order');
        $qb = $orderRepository->createQueryBuilder('o')
            ->where('o.id IS NOT NULL')
            ->orderBy('o.createdAt', 'DESC'); // Ordenar por fecha más reciente
        
        if ($query) {
            $qb->andWhere('o.id LIKE :search OR o.user.email LIKE :search OR o.status LIKE :search')
                ->setParameter('search', '%' . $query . '%');
        }
        */
         $query = $request->query->get('find_order');
            $qb = $orderRepository->createQueryBuilder('o')
            ->join('o.user', 'u')     // JOIN con User
            ->join('o.status', 's')   // JOIN con Status si necesitas filtrar por estado
            ->orderBy('o.createdAt', 'DESC');

            if ($query) {
                $qb->andWhere('o.id LIKE :search OR u.email LIKE :search OR s.name LIKE :search')
                    ->setParameter('search', '%' . $query . '%');
            }
        
        $pagination = $paginator->paginate(
            $qb, // QueryBuilder
            $request->query->getInt('page', 1), // Página actual, por defecto 1
            10 // Elementos por página
        );
        
        return $this->render('order/index.html.twig', [
            'orders' => $pagination,
            'tituloAlmacen' => 'Storage - Listado de Pedidos',
            'find_order' => $query
        ]);
    }

    // Acceso para Personal de almacén Pickers para ver los detalles de lospedidos pagados que tienen que preparar.
    #[Route('/almacen/orders/{id}', name: 'app_order_show_store')]
    //#[IsGranted('ROLE_PICKER')]
    public function pickerOrdersshow(Order $order): Response
    {
        $user = $this->getUser();

        // Solo puede acceder el dueño del pedido o un admin
        if ($order->getUser() !== $user && !$this->isGranted('ROLE_PICKER') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No puedes ver este pedido.');
        }

        return $this->render('order/detail.html.twig', [
            'order' => $order,
        ]);
       
    }

    // Detalle de pedido con control de acceso
    #[Route('/orders/{id}', name: 'app_order_show')]
    #[IsGranted('ROLE_USER')]
    public function show(Order $order): Response
    {
        $user = $this->getUser();

        // Solo puede acceder el dueño del pedido o un admin
        if ($order->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No puedes ver este pedido.');
        }

        return $this->render('order/detail.html.twig', [
            'order' => $order,
        ]);
    }
}
