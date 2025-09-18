<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ProductRepository;


class HomeController extends AbstractController
{
    //#[Route('/', name: 'app_home', defaults: ['_locale' => 'es'], requirements: ['_locale' => 'es|en|fr|it|de'])]
    //#[Route('/{_locale}', name: 'app_home_locale', requirements: ['_locale' => 'es|en|fr|it|de'])]

    
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('home/index.html.twig', [
            'products' => $products,
        ]);
    }

    
    #[Route('/categories', name: 'app_tienda')]
    public function app_tienda(CategoryRepository $categoryRepository ): Response
    {
          // Obtiene todas las categorías raíz (sin padre)
        $categories = $categoryRepository->findBy(['parent' => null]);

        return $this->render('category/tienda.html.twig', [
            'categories' => $categories,
        ]);
        
    }


}
