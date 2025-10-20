<?php

namespace App\Tests\Functional;

use App\DataFixtures\CategoryFixtures;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CategoryControllerTest extends WebTestCase
{
    private AbstractDatabaseTool $databaseTool;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = static::createClient();
        
        // Obtener el databaseTool
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        
        // Cargar las fixtures
        $this->databaseTool->loadFixtures([
            CategoryFixtures::class,
        ]);
    }

    /**
     * Test que verifica que la página de listado de categorías carga correctamente
     */
    public function testCategoryIndexLoads(): void
    {
        $this->client->request('GET', '/secciones/');
        
        $this->assertResponseIsSuccessful();
        //$this->assertSelectorTextContains('h1', 'Categorías');
    }

    /**
     * Test que verifica que se cargan las 6 categorías principales
     */
    public function testMainCategoriesAreLoaded(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        // Verificar que existen 6 categorías padre
        $parentCategories = $categoryRepository->findBy(['parent' => null]);
        $this->assertCount(6, $parentCategories, 'Deben existir 6 categorías principales');
    }

    /**
     * Test que verifica que todas las categorías principales esperadas existen
     */
    public function testAllMainCategoriesExist(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $expectedCategories = [
            'Frutas',
            'Verduras y Hortalizas',
            'Usos y Recetas',
            'Cajas y Packs',
            'Ofertas y Novedades',
            'Cultivo y origen'
        ];
        
        foreach ($expectedCategories as $categoryName) {
            $category = $categoryRepository->findOneBy(['name' => $categoryName]);
            $this->assertNotNull($category, "La categoría '{$categoryName}' debe existir");
            $this->assertNull($category->getParent(), "'{$categoryName}' debe ser una categoría principal");
        }
    }

    /**
     * Test que verifica que la categoría Frutas tiene 10 subcategorías
     */
    public function testFrutasHasTenSubcategories(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $frutas = $categoryRepository->findOneBy(['name' => 'Frutas']);
        $this->assertNotNull($frutas, 'La categoría Frutas debe existir');
        
        $frutasChildren = $categoryRepository->findBy(['parent' => $frutas]);
        $this->assertCount(10, $frutasChildren, 'Frutas debe tener 10 subcategorías');
    }

    /**
     * Test que verifica que la categoría Verduras y Hortalizas tiene 9 subcategorías
     */
    public function testVerdurasHasNineSubcategories(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $verduras = $categoryRepository->findOneBy(['name' => 'Verduras y Hortalizas']);
        $this->assertNotNull($verduras, 'La categoría Verduras y Hortalizas debe existir');
        
        $verdurasChildren = $categoryRepository->findBy(['parent' => $verduras]);
        $this->assertCount(9, $verdurasChildren, 'Verduras y Hortalizas debe tener 9 subcategorías');
    }

    /**
     * Test que verifica que los slugs se generan correctamente
     */
    public function testCategorySlugsAreCorrect(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $frutas = $categoryRepository->findOneBy(['name' => 'Frutas']);
        $this->assertEquals('frutas', $frutas->getSlug(), 'El slug de Frutas debe ser "frutas"');
        
        $verdurasHortalizas = $categoryRepository->findOneBy(['name' => 'Verduras y Hortalizas']);
        $this->assertEquals('verduras-y-hortalizas', $verdurasHortalizas->getSlug(), 
            'El slug debe estar en formato kebab-case');
        
        $frutasTemporada = $categoryRepository->findOneBy(['name' => 'Frutas de temporada']);
        $this->assertEquals('frutas-de-temporada', $frutasTemporada->getSlug(), 
            'Las subcategorías también deben tener slugs correctos');
        
        $km0 = $categoryRepository->findOneBy(['name' => 'KM 0 / Local']);
        $this->assertEquals('km-0-local', $km0->getSlug(), 
            'Los slugs deben manejar caracteres especiales y números');
    }

    /**
     * Test que verifica la relación padre-hijo de las categorías
     */
    public function testCategoryParentChildRelationship(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $frutasTemporada = $categoryRepository->findOneBy(['name' => 'Frutas de temporada']);
        
        $this->assertNotNull($frutasTemporada->getParent(), 
            'Las subcategorías deben tener una categoría padre');
        
        $this->assertEquals('Frutas', $frutasTemporada->getParent()->getName(), 
            'El padre de "Frutas de temporada" debe ser "Frutas"');
    }

    /**
     * Test que verifica todas las subcategorías de Frutas
     */
    public function testAllFrutasSubcategoriesExist(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $frutas = $categoryRepository->findOneBy(['name' => 'Frutas']);
        
        $expectedSubcategories = [
            'Frutas de temporada',
            'Frutas tropicales',
            'Frutas cítricas',
            'Frutas de pepita',
            'Frutas de hueso',
            'Frutas del bosque',
            'Frutas exóticas',
            'Frutas ecológicas',
            'Frutas para zumo',
            'Frutas por unidad / por kilo',
        ];
        
        foreach ($expectedSubcategories as $subcategoryName) {
            $subcategory = $categoryRepository->findOneBy([
                'name' => $subcategoryName,
                'parent' => $frutas
            ]);
            $this->assertNotNull($subcategory, 
                "La subcategoría '{$subcategoryName}' debe existir bajo Frutas");
        }
    }

    /**
     * Test que verifica que se puede acceder a una categoría por su slug
     */
    public function testAccessCategoryBySlug(): void
    {
        $this->client->request('GET', '/secciones/frutas');
        
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test que verifica que se puede ver el detalle de una categoría
     */
    public function testCategoryDetailPage(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $frutas = $categoryRepository->findOneBy(['name' => 'Frutas']);
        
        $this->client->request('GET', '/secciones/' . $frutas->getSlug());
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Frutas');
    }

    /**
     * Test que verifica que las categorías sin productos se muestran correctamente
     */
    public function testEmptyCategoriesDisplayCorrectly(): void
    {
        $this->client->request('GET', '/secciones/frutas');
        
        $this->assertResponseIsSuccessful();
        // Si no hay productos, debería mostrar un mensaje apropiado
        $crawler = $this->client->getCrawler();
        
        // Verificar que existe el contenedor de categoría aunque esté vacío
        //$this->assertGreaterThan(0, $crawler->filter('.category-container')->count());
    }

    /**
     * Test que verifica que el total de categorías es correcto
     */
    public function testTotalCategoriesCount(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $allCategories = $categoryRepository->findAll();
        
        // 6 categorías principales + sus subcategorías
        // Frutas: 10, Verduras: 9, Usos: 6, Cajas: 6, Ofertas: 4, Cultivo: 5 = 40 subcategorías
        // Total: 6 + 40 = 46
        $this->assertCount(46, $allCategories, 'Debe haber 46 categorías en total');
    }

    /**
     * Test que verifica subcategorías específicas de diferentes categorías
     */
    public function testSpecificSubcategoriesExist(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        // Test de subcategorías de "Usos y Recetas"
        $usosRecetas = $categoryRepository->findOneBy(['name' => 'Usos y Recetas']);
        $paraEnsaladas = $categoryRepository->findOneBy([
            'name' => 'Para ensaladas',
            'parent' => $usosRecetas
        ]);
        $this->assertNotNull($paraEnsaladas, 'La subcategoría "Para ensaladas" debe existir');
        
        // Test de subcategorías de "Cajas y Packs"
        $cajasPacks = $categoryRepository->findOneBy(['name' => 'Cajas y Packs']);
        $cajaMixta = $categoryRepository->findOneBy([
            'name' => 'Caja mixta fruta y verdura',
            'parent' => $cajasPacks
        ]);
        $this->assertNotNull($cajaMixta, 'La subcategoría "Caja mixta fruta y verdura" debe existir');
    }

    /**
     * Test que verifica que las categorías no tienen hijos recursivos (sin bucles)
     */
    public function testCategoriesHaveNoCircularReferences(): void
    {
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $categoryRepository = $entityManager->getRepository(\App\Entity\Category::class);
        
        $allCategories = $categoryRepository->findAll();
        
        foreach ($allCategories as $category) {
            $visited = [];
            $current = $category;
            
            while ($current !== null) {
                $this->assertNotContains($current->getId(), $visited, 
                    'No debe haber referencias circulares en las categorías');
                $visited[] = $current->getId();
                $current = $current->getParent();
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
        unset($this->client);
    }
}