<?php
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderControllerTest extends WebTestCase
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

    public function testAdminOrderListLoadsCorrectly(): void
    {
        // 1️⃣ Petición a la ruta de admin
        $crawler = $this->client->request('GET', '/admin/orders');

        // Comprobar que responde correctamente
        $this->assertResponseIsSuccessful();
        //$this->assertSelectorExists('tbody tr', 'No se encontraron filas en la tabla de pedidos.');

        //  Verificar que el buscador de pedidos existe
        $this->assertGreaterThan(
            0,
            $crawler->filter('#orderSearch')->count(),
            'No se encontró el campo de búsqueda con id="orderSearch".'
        );

        // 4️⃣ Validar que la tabla tiene columnas suficientes y fechas válidas
        $rows = $crawler->filter('table tbody tr');
        foreach ($rows as $row) {
            $columns = $row->getElementsByTagName('td');
            $this->assertGreaterThanOrEqual(4, $columns->length, 'La fila no tiene suficientes columnas.');

            // Supongamos que las columnas 3 y 4 son fechas (ajusta si tu plantilla cambia)
            $createdAt = trim($columns->item(2)->textContent ?? '');
            $updatedAt = trim($columns->item(3)->textContent ?? '');

            // Asegurar que no estén vacías
            $this->assertNotEmpty($createdAt, 'La fecha de creación no debe estar vacía.');
         

            // Validar formato de fecha
            $this->assertTrue(
                strtotime($createdAt) !== false,
                sprintf('La fecha de creación "%s" no es válida.', $createdAt)
            );
           
        }
    }


}
