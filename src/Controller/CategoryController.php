<?php 
// src/Controller/CategoryController.php
namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categories')]
class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'categories_list')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Obtiene todas las categorías raíz (sin padre)
        $categories = $categoryRepository->findBy(['parent' => null]);

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }
}
