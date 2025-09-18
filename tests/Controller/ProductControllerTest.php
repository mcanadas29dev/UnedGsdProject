<?php
// tests/Controller/ProductControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    public function testProductIndexLoads(): void
    {
        // Crea un cliente HTTP simulado
        $client = static::createClient();

        // Realiza una petición GET a la ruta de productos
        $crawler = $client->request('GET', '/tienda');

        // Verifica que la respuesta fue 200 OK
        $this->assertResponseIsSuccessful();

        // Verifica que en la página aparece el título esperado
        $this->assertSelectorTextContains('h1', 'Nuestra Tienda');
    }
}
