<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Entity\Offer;
use App\Entity\Product;

class OfferTest extends TestCase
{
    public function testOfferSettersAndGetters(): void
    {
        $offer = new Offer();
        $product = new Product();
        $product->setName('Producto prueba')->setPrice(100);

        $start = new \DateTime('2025-01-01');
        $end = new \DateTime('2025-12-31');

        $offer->setProduct($product);
        $offer->setOfferPrice(80.5);
        $offer->setStartDate($start);
        $offer->setEndDate($end);

        $this->assertSame($product, $offer->getProduct());
        $this->assertEquals(80.5, $offer->getOfferPrice());
        $this->assertEquals($start, $offer->getStartDate());
        $this->assertEquals($end, $offer->getEndDate());
    }

    public function testOfferPriceCannotBeZeroOrNegative(): void
    {
        $offer = new Offer();
        $this->expectException(\TypeError::class);
        $offer->setOfferPrice(0);
    }

    public function testOfferDatesAreChronological(): void
    {
        $offer = new Offer();
        $start = new \DateTime('2025-01-10');
        $end = new \DateTime('2025-01-05');

        $offer->setStartDate($start);
        $offer->setEndDate($end);

        $this->assertTrue(
            $offer->getEndDate() >= $offer->getStartDate(),
            'La fecha de fin debe ser posterior o igual a la de inicio'
        );
    }
}
