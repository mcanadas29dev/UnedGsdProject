<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response

    {   /*
        $httpClient = HttpClient::create();
        $faostatItems = [];

        try {
            $response = $httpClient->request(
                'GET',
                'https://fenixservices.fao.org/faostat/api/v1/en/domains/QC/items'
            );

            $data = $response->toArray();
            $faostatItems = array_slice($data['data'], 0, 4); // 4 primeros productos
        } catch (\Exception $e) {
            // Puedes registrar el error o dejar el array vacío
        }

        return $this->render('home/index.html.twig', [
            'faostat_items' => $faostatItems
        ]);
            */
        
        //return $this->render('home/index.html.twig'); // plantilla que extiende base.html.twig 
        $finder = new Finder();
        $finder->files()->in($this->getParameter('kernel.project_dir') . '/public/images/productos');

        $images = [];
        foreach ($finder as $file) {
            $images[] = 'productos/' . $file->getFilename();
        }

        return $this->render('home/index.html.twig', [
            'images' => $images,
        ]);
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
