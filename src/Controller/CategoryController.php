<?php
// src/Controller/CategoryController.php

namespace App\Controller;

use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/categories', name: 'categories_')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'list', methods: ['GET'])]
    public function list(CategoryRepository $repo , PaginatorInterface $paginator, Request $request): Response
    {
        $query = $request->query->get('find_categorie');
    
        $qb = $repo->createQueryBuilder('c')
            ->where('c.id IS NOT NULL');

        if ($query) {
            $qb->andWhere('c.name LIKE :search')
            ->setParameter('search', '%' . $query . '%');
        }

        $pagination = $paginator->paginate(
            $qb, // QueryBuilder
            $request->query->getInt('page', 1), // Página actual, por defecto 1
            10 // Elementos por página
        );

        return $this->render('category/list.html.twig', [
            'categories' => $pagination,
            'find_categorie' => $query
        ]);

        //////////////////////////////////
        //$categories = $repo->findAll();

        //return $this->render('category/list.html.twig', [
        //    'categories' => $categories,
        //]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            $this->addFlash('success', 'Categoría creada correctamente.');

            return $this->redirectToRoute('categories_list');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Categoría actualizada correctamente.');

            return $this->redirectToRoute('categories_list');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $em->remove($category);
            $em->flush();
            $this->addFlash('success', 'Categoría eliminada correctamente.');
        }

        return $this->redirectToRoute('categories_list');
    }

    #[Route('/tienda', name: 'tienda_', methods: ['GET'])]
    public function tienda(CategoryRepository $categoryRepository): Response
    {
        // Obtiene todas las categorías raíz (sin padre)
        $categories = $categoryRepository->findBy(['parent' => null]);

        return $this->render('category/tienda.html.twig', [
            'categories' => $categories,
        ]);
    }
}
