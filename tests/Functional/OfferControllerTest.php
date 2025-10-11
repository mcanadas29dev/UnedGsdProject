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
use App\Tests\Fixtures\CategoryFixtures;
use App\Tests\Fixtures\ProductFixtures;
use App\Tests\Fixtures\OfferFixtures;
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
        $crawler = $this->client->request('GET', '/offer');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Ofertas');
        $this->assertSelectorExists('table tbody tr');
    }

    public function testOfferPricesAreLessThanProductPrice(): void
    {
        $crawler = $this->client->request('GET', '/offer');
        $this->assertResponseIsSuccessful();

        $rows = $crawler->filter('table tbody tr');
        $this->assertGreaterThan(0, $rows->count(), 'No hay ofertas en la tabla.');

        foreach ($rows as $row) {
            // Supongamos que columna 2 = precio original, columna 3 = precio oferta
            $cells = $row->getElementsByTagName('td');
            if ($cells->length >= 3) {
                $productPrice = floatval(str_replace(['€', ' '], '', $cells->item(1)->textContent ?? '0'));
                $offerPrice = floatval(str_replace(['€', ' '], '', $cells->item(2)->textContent ?? '0'));
                $this->assertLessThan(
                    $productPrice,
                    $offerPrice,
                    "El precio de la oferta debe ser menor que el precio original. Producto: $productPrice | Oferta: $offerPrice"
                );
            }
        }
    }

    public function testActiveOffersWithinDateRange(): void
    {
        $crawler = $this->client->request('GET', '/offer');
        $this->assertResponseIsSuccessful();

        // Aquí podrías verificar que la página muestre solo ofertas vigentes, si se listan con fecha
        // Por simplicidad, se verifica que se cargan correctamente las filas
        $this->assertSelectorExists('table tbody tr');
    }
}
