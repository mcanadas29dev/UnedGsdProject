<?php
// tests/Functional/ProductControllerTest.php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;

class ProductControllerTest extends WebTestCase
{
    private AbstractDatabaseTool $databaseTool;

protected function setUp(): void
{
    parent::setUp();
    self::bootKernel();

    // Obtener la herramienta de fixtures usando getContainer()
    $container = static::getContainer();
    $this->databaseTool = $container
        ->get(DatabaseToolCollection::class)
        ->get();

    // Cargar fixtures
    $this->databaseTool->loadFixtures([
        CategoryFixtures::class,
        ProductFixtures::class,
    ]);
}

    public function testProductIndexLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/product/');

        // Verifica que la página carga correctamente
        $this->assertResponseIsSuccessful();

        // Verifica que el <h1> contiene "Productos"
        $this->assertSelectorTextContains('h1', 'Productos');

        // Verifica que aparece al menos un producto en la tabla
        $this->assertSelectorExists('table tbody tr');
    }

    public function testSearchProduct(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/product/?q=Producto');

        $this->assertResponseIsSuccessful();

        // Verifica que al menos un producto coincidente aparece
        $this->assertSelectorExists('table tbody tr');
    }
}
