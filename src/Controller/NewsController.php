<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NewsController extends AbstractController
{
    private string $apiKey;

    public function __construct(string $apiKey){
        $this->apiKey = $apiKey;
    }
    #[Route('/news', name: 'app_news')]
    public function index(HttpClientInterface $httpClient): Response
    {
        // Usando NewsAPI.org como ejemplo
        // usuario mcanadasdev
        
        //$apiKey = '3078b6b1045441618cddd64184a6bf73';
        $url = 'https://newsapi.org/v2/top-headlines?country=us&category=health&pageSize=6&apiKey=' . $this->apiKey;

        $response = $httpClient->request('GET', $url);
        $data = $response->toArray();

        $articles = $data['articles'] ?? [];

        return $this->render('News/index.html.twig', [
            'articles' => $articles,
        ]);
    }
}
