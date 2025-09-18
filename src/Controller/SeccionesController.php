<?php
// src/Controller/SeccionesController.php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/secciones', name: 'secciones_')]
class SeccionesController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Obtener solo las categorías principales (sin padre)
        $categories = $categoryRepository->findBy(['parent' => null], ['name' => 'ASC']);

        return $this->render('secciones/index.html.twig', [
            'categories' => $categories,
        ]);
    }

   
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(string $slug, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        
        if (!$category) {
            throw $this->createNotFoundException('La categoría no fue encontrada.');
        }

        // Si es una categoría padre, mostrar sus subcategorías
        if ($category->getChildren()->count() > 0) {
            return $this->render('secciones/categoria.html.twig', [
                'category' => $category,
                'subcategories' => $category->getChildren(),
            ]);
        }

        // Si es una subcategoría, mostrar sus productos
        // Asumiendo que tienes una relación entre Category y Product
        $products = $productRepository->findBy(['category' => $category], ['name' => 'ASC']);

        return $this->render('secciones/productos.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    #[Route('/categoria/{slug}/productos', name: 'productos', methods: ['GET'])]
    //public function productos(Category $category, ProductRepository $productRepository): Response
    public function productos(string $slug, CategoryRepository $categoryRepository, ProductRepository $productRepository): Response
    {
        $category = $categoryRepository->findOneBy(['slug' => $slug]);
        
        if (!$category) {
            throw $this->createNotFoundException('La categoría no fue encontrada.');
        }

        // Obtener productos de esta categoría y sus subcategorías
        $categories = [$category];
        
        // Añadir todas las subcategorías
        foreach ($category->getChildren() as $child) {
            $categories[] = $child;
        }

        $products = $productRepository->createQueryBuilder('p')
            ->where('p.category IN (:categories)')
            ->setParameter('categories', $categories)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('secciones/productos.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}