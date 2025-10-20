<?php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
/*
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;
use App\DataFixtures\OfferFixtures;
*/
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;
use App\DataFixtures\OfferFixtures;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OfferControllerTest extends WebTestCase
{
    private AbstractDatabaseTool $databaseTool;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container
            ->get(DatabaseToolCollection::class)
            ->get();

        // Cargar datos
        $this->databaseTool->loadFixtures([
            CategoryFixtures::class,
            ProductFixtures::class,
            OfferFixtures::class,
        ]);

        // Crear usuario admin
        $entityManager = $container->get(EntityManagerInterface::class);
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword('$2y$13$abcdefghijklmnopqrstuvwx1234567890abcd'); // hash simulado
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->persist($user);
        $entityManager->flush();

        // Loguear
        $this->client->loginUser($user);
    }

    public function testOfferIndexLoads(): void
    {
        $crawler = $this->client->request('GET', '/offer/admin');

        $this->assertResponseIsSuccessful();
        
        $this->assertSelectorTextContains('h4', 'Ofertas');
        $this->assertSelectorExists('table tbody tr');
        // Comprobar que existe el elemento con id="offerSearch"
        $this->assertEquals(
            1,
            $crawler->filter('#offerSearch')->count(),
            'No se encontró ningún elemento con id="offerSearch".'
        );
    }

    public function testActiveOffersWithinDateRange(): void
    {
        $crawler = $this->client->request('GET', '/admin/offer');
        $this->assertResponseIsSuccessful();

        // Obtener las filas de la tabla
        $rows = $crawler->filter('table tbody tr');
        $this->assertGreaterThan(0, $rows->count(), 'No se encontraron filas en la tabla.');

        foreach ($rows as $row) {
            $columns = $row->getElementsByTagName('td');

            // Comprobar que hay al menos 4 columnas
            $this->assertGreaterThanOrEqual(6, $columns->length, 'La fila no tiene suficientes columnas.');

            // Columna 3 (índice 2) = fecha inicio, columna 4 (índice 3) = fecha fin
            $startDate = trim($columns->item(3)->textContent);
            $endDate   = trim($columns->item(4)->textContent);

            $this->assertNotEmpty($startDate, 'La fecha de inicio no debe estar vacía.');
            $this->assertNotEmpty($endDate, 'La fecha de fin no debe estar vacía.');

            // Validar que sean fechas correctas
            $this->assertTrue(
                strtotime($startDate) !== false,
                sprintf('La fecha de inicio "%s" no es válida.', $startDate)
            );
            $this->assertTrue(
                strtotime($endDate) !== false,
                sprintf('La fecha de fin "%s" no es válida.', $endDate)
            );
        }
    }

    public function testDateStartNotGreaterDateEnd(): void 
    {
        $crawler = $this->client->request('GET', '/admin/offer');
        $this->assertResponseIsSuccessful();

        // Obtener las filas de la tabla
        $rows = $crawler->filter('table tbody tr');
        $this->assertGreaterThan(0, $rows->count(), 'No se encontraron filas en la tabla.');

        foreach ($rows as $row) {
            $columns = $row->getElementsByTagName('td');

            // Comprobar que hay al menos 6 columnas
            $this->assertGreaterThanOrEqual(6, $columns->length, 'La fila no tiene suficientes columnas.');

            // Columna 4 (índice 3) = fecha inicio, columna 5 (índice 4) = fecha fin
            $startDate = trim($columns->item(3)->textContent);
            $endDate   = trim($columns->item(4)->textContent);

            $this->assertNotEmpty($startDate, 'La fecha de inicio no debe estar vacía.');
            $this->assertNotEmpty($endDate, 'La fecha de fin no debe estar vacía.');

            // Validar que sean fechas correctas
            $this->assertTrue(
                strtotime($startDate) !== false,
                sprintf('La fecha de inicio "%s" no es válida.', $startDate)
            );
            $this->assertTrue(
                strtotime($endDate) !== false,
                sprintf('La fecha de fin "%s" no es válida.', $endDate)
            );

            // Validar que la fecha de inicio sea menor que la fecha de fin
            $this->assertLessThan(
                strtotime($endDate),
                strtotime($startDate),
                sprintf('La fecha de inicio "%s" debe ser menor que la fecha de fin "%s".', $startDate, $endDate)
            );
        }

    }

}
