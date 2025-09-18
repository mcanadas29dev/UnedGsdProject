<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testSetAndGetName()
    {
        $product = new Product();
        $product->setName('Manzana');

        $this->assertEquals('Manzana', $product->getName());
    }

    public function testSetAndGetPrice()
    {
        $product = new Product();
        $product->setPrice(2.50);

        $this->assertEquals(2.50, $product->getPrice());
    }

    // tests/Entity/ProductTest.php
    public function testPriceSetterGetter() {
        $product = new Product();
        $product->setPrice(19.99);
        $this->assertEquals(19.99, $product->getPrice());
    }

}
