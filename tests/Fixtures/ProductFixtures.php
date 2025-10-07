<?php

namespace App\Tests\Fixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Tomar la primera categoría disponible
        $category = $manager->getRepository(Category::class)->findOneBy([]);

        for ($i = 1; $i <= 5; $i++) {
            $product = new Product();
            $product->setName("TestProduct $i");
            $product->setPrice(mt_rand(1, 100)/10);
            $product->setCategory($category);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
