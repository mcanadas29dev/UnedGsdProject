<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig'); // plantilla que extiende base.html.twig
    }

    #[Route('/categories', name: 'app_tienda')]
    public function app_tienda(): Response
    {
          // Obtiene todas las categorías raíz (sin padre)
        $categories = $categoryRepository->findBy(['parent' => null]);

        return $this->render('category/tienda.html.twig', [
            'categories' => $categories,
        ]);
        
    }
}
