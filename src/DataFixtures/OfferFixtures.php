<?php

namespace App\DataFixtures;

use App\Entity\Offer;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class OfferFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Recuperar productos creados en ProductFixtures
        /** @var Product[] $products */
        $products = $manager->getRepository(Product::class)->findAll();

        if (empty($products)) {
            // Si no hay productos (por si se ejecuta solo), se crean unos básicos
            for ($i = 1; $i <= 3; $i++) {
                $product = new Product();
                $product->setName("Producto Oferta $i");
                $product->setDescription("Descripción del producto con oferta $i");
                $product->setPrice(100 + $i * 10);
                //$product->setStock(10 + $i);
                $manager->persist($product);
                $products[] = $product;
            }
            $manager->flush();
        }

        // Crear ofertas para algunos productos
        $index = 1;
        foreach ($products as $product) {
            $offer = new Offer();
            $offer->setProduct($product);
            $offer->setOfferPrice($product->getPrice()+$index * 0.8); // 20% de descuento
            $offer->setStartDate(new \DateTime('-3 days'));
            $offer->setEndDate(new \DateTime('+10 days'));

            $manager->persist($offer);
            $this->addReference('offer_' . $index, $offer);
            $index++;
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        // Asegura que se cargan primero las categorías y productos
        return [
            CategoryFixtures::class,
            ProductFixtures::class,
        ];
    }
}

