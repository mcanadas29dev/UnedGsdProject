<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class NewsController extends AbstractController
{
    private string $apiKey;

    public function __construct(string $apiKey){
        $this->apiKey = $apiKey;
    }
    #[Route('/news', name: 'app_news')]
    public function index(HttpClientInterface $httpClient, Request $request): Response
    {
        try
        {
        $category = $request->query->get('category', 'health'); // por defecto salud
        // Validar categoría para evitar peticiones no deseadas
        $allowedCategories = ['business', 'entertainment', 'general', 'health', 'science', 'sports', 'technology'];
        if (!in_array($category, $allowedCategories, true)) {
            $category = 'health';
        }
        $url = 'https://newsapi.org/v2/top-headlines?country=us&pageSize=3&category=' . $category . '&apiKey=' . $this->apiKey;
        $response = $httpClient->request('GET', $url);

        $data = $response->toArray();
        $articles = $data['articles'] ?? [];
         return $this->render('News/index.html.twig', [
            'articles' => $articles,
        ]);
        }
        catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
        // Error de conexión (API caída, red no disponible, etc.)
            $this->addFlash('danger', 'No se pudo conectar con el servicio de noticias.');
        } 
        catch (\Exception $e) {
            // Cualquier otro error general
            $this->addFlash('danger', 'En estos momentos no se pueden cargar las noticias.');
        }
       // En caso de error, redirigimos al carrito
        return $this->redirectToRoute('app_home');
        
    }
}
