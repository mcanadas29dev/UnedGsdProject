<?php

namespace App\DataFixtures;

use App\Entity\Order;
use App\Entity\User;
use App\Entity\OrderStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    

    public function load(ObjectManager $manager): void
    {
        // Obtener referencias creadas en otros fixtures
        $user = new User();
        $status = new OrderStatus();
        $user->setEmail('marcelo@gmail.com');
        /** @var OrderStatus $status */
        $order = $manager->getRepository(Order::class)->findAll();
        $status->setName('pagado'); // definida en OrderStatusFixtures

        // Crear varios pedidos de prueba
        for ($i = 1; $i <= 5; $i++) {
            $order = new Order();
            $order->setUser($user);
            $order->setStatus($status);
            $order->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', $i)));

            // Si tu entidad tiene un campo total o similar
            if (method_exists($order, 'setTotal')) {
                //$order->setTotal(mt_rand(50, 300));
            }

            $manager->persist($order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ProductFixtures::class,
        ];
    }
}
