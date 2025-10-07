<?php

namespace App\Tests\Fixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Cocur\Slugify\Slugify;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $slugify = new Slugify();

        $categories = ['Frutas', 'Verduras', 'Ofertas'];

        foreach ($categories as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($slugify->slugify($name));
            $manager->persist($category);
        }

        $manager->flush();
    }
}
