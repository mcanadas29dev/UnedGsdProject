<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StoreController extends AbstractController
{
    #[Route('/tienda', name: 'app_tienda')]
    public function index(ProductRepository $productRepository): Response
    {
        /*
        $products = $productRepository->findAll();

        return $this->render('store/index.html.twig', [
            'products' => $products,
        ]);
        */
        $products = $productRepository->findProductsNotInOffer();
        return $this->render('store/index.html.twig', [
            'products' => $products,
        ]);
    }

    ## INCLUIR EL /tienda para que muestre las categorias como tarjeta y con paginacion
    ## y haciendo click en la tarjeta de una categoria muestren los articulos tal como 
    ## estan ahora 
}
