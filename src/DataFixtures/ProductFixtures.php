<?php
// src/DataFixtures/ProductFixtures.php
namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Obtenemos la primera categoría creada
        $category = $manager->getRepository(Category::class)->findOneBy([], ['id' => 'ASC']);

        // Creamos productos
        for ($i = 1; $i <= 5; $i++) {
            $product = new Product();
            $product->setName("Producto $i");
            $product->setPrice(mt_rand(100, 1000) / 10);
            $product->setCategory($category);
            $manager->persist($product);
        }

        $manager->flush();
    }

    // Declaramos la dependencia
    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
