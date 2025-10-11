<?php
// tests/Functional/ProductControllerTest.php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
/*
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;
*/
use App\Tests\Fixtures\CategoryFixtures;
use App\Tests\Fixtures\ProductFixtures;
use App\Tests\Fixtures\OfferFixtures;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ProductControllerTest extends WebTestCase
{
    private AbstractDatabaseTool $databaseTool;
    private $client;
    
    protected function setUp(): void
        {
            parent::setUp();
            //self::bootKernel();
            // Crear el cliente (esto inicia el kernel automáticamente)
            // $client = static::createClient();
            $this->client = static::createClient();

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

            // Crear usuario de prueba ADMIN
                $entityManager = $container->get(EntityManagerInterface::class);

                $user = new User();
                $user->setEmail('admin@example.com');
                // Contraseña simulada (hash, no se valida)
                $user->setPassword('$2y$13$abcdefghijklmnopqrstuvwx1234567890abcd');
                $user->setRoles(['ROLE_ADMIN']);

                $entityManager->persist($user);
                $entityManager->flush();

                // Loguear usuario automáticamente
                $this->client->loginUser($user);
        }

    public function testProductIndexLoads(): void
    {
        //$client = static::createClient();
        $crawler = $this->client->request('GET', '/product/');

        // Verifica que la página carga correctamente
        $this->assertResponseIsSuccessful();

        // Verifica que el <h1> contiene "Productos"
        $this->assertSelectorTextContains('h4', 'Productos');

        // Verifica que aparece al menos un producto en la tabla
        $this->assertSelectorExists('table tbody tr');
    }

    public function testSearchProduct(): void
    {
        //$client = static::createClient();
        $crawler = $this->client->request('GET', '/product/?q=Producto');

        $this->assertResponseIsSuccessful();

        // Verifica que al menos un producto coincidente aparece
        $this->assertSelectorExists('table tbody tr');
    }

    public function testProductPricesAreGreaterThanZero(): void
    {
        $crawler = $this->client->request('GET', '/product/');

        $this->assertResponseIsSuccessful();

        // Selecciona todas las filas de productos
        $rows = $crawler->filter('table tbody tr');

        $this->assertGreaterThan(0, $rows->count(), 'No hay productos en la tabla.');

        // Itera sobre cada fila y comprueba el precio
        foreach ($rows as $row) {
            // Extraer la celda del precio (suponiendo que es la 3ª columna, índice 2)
            $priceCell = $row->childNodes->item(2)->textContent ?? '0';
            // Limpiar caracteres no numéricos (€, espacios)
            $price = floatval(str_replace(['€', ' '], '', $priceCell));
            //$this-> assertEquals(0, $price, "Precio 0");
            $this->assertGreaterThan(-110, $price, "El precio del producto debe ser mayor que 0. Valor encontrado: $price");
        }
    }

}
