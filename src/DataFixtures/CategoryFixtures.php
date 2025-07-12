<?php
// src/DataFixtures/CategoryFixtures.php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Cocur\Slugify\Slugify;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $slugify = new Slugify();

        // Estructura de árbol
        $data = [
            'Frutas' => [
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
            ],
            'Verduras y Hortalizas' => [
                'Verduras de temporada',
                'Hojas y brotes',
                'Raíces y tubérculos',
                'Bulbos y tallos',
                'Fruto-hortaliza',
                'Flores comestibles',
                'Verduras ecológicas',
                'Verduras para cocinar',
                'Verduras por unidad / por kilo',
            ],
            'Usos y Recetas' => [
                'Para ensaladas',
                'Para smoothies y jugos',
                'Para cocinar/platos calientes',
                'Snacks saludables',
                'Ideal para niños',
                'Recetas y consejos',
            ],
            'Cajas y Packs' => [
                'Caja mixta fruta y verdura',
                'Caja solo fruta',
                'Caja solo verdura',
                'Caja ecológica',
                'Cajas por suscripción semanal / quincenal',
                'Cajas para empresas / oficinas',
            ],
            'Ofertas y Novedades' => [
                'Ofertas de la semana',
                'Novedades',
                'Última oportunidad',
                'Segunda unidad al 50%',
            ],
            'Cultivo y origen' => [
                'Convencional',
                'Ecológico / Bio',
                'KM 0 / Local',
                'Origen nacional',
                'Importación (exóticas)',
            ],
        ];

        foreach ($data as $parentName => $children) {
            $parent = new Category();
            $parent->setName($parentName);
            $parent->setSlug($slugify->slugify($parentName));
            $manager->persist($parent);

            foreach ($children as $childName) {
                $child = new Category();
                $child->setName($childName);
                $child->setSlug($slugify->slugify($childName));
                $child->setParent($parent);
                $manager->persist($child);
            }
        }

        $manager->flush();
    }
}
