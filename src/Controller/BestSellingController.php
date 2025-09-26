<?php
// src/Controller/BestSellingController.php
namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BestSellingController extends AbstractController
{
    #[Route('/lo-mas-vendido', name: 'app_best_selling')]
    public function index(EntityManagerInterface $em): Response
        {
            $query = $em->createQuery(
                'SELECT p.id,p.name, SUM(oi.quantity) AS totalSold
                FROM App\Entity\OrderItem oi
                JOIN oi.product p
                GROUP BY p.id
                ORDER BY totalSold DESC'
            )->setMaxResults(12);

            $results = $query->getResult();

            // Extraer datos para el gráfico
            $labels = array_column($results, 'name');
            $data = array_column($results, 'totalSold');
            $total = array_sum($data);
            return $this->render('store/best_selling.html.twig', [
                'topProducts' => $results,
                'labels' => $labels,
                'data' => $data,
                'total' => $total,
            ]);
        }


}
