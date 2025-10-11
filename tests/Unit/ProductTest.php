<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Product;
use App\Entity\Category;

class ProductTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $product = new Product();
        //$category = new Category();

        $product->setName('Banana');
        $product->setPrice(1.5);
        //$product->setCategory($category);

        $this->assertSame('Banana', $product->getName());
        $this->assertSame(1.5, $product->getPrice());
        //$this->assertSame($category, $product->getCategory());
    }
}
