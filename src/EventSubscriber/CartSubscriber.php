<?php
namespace App\EventSubscriber;

use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class CartSubscriber implements EventSubscriberInterface
{
    private CartService $cartService;
    private Environment $twig;

    public function __construct(CartService $cartService, Environment $twig)
    {
        $this->cartService = $cartService;
        $this->twig = $twig;
    }

    public function onKernelController(ControllerEvent $event)
    {
        $cartItems = $this->cartService->getCart();
        $totalQuantity = 0;

        foreach ($cartItems as $item) {
            $totalQuantity += $item['quantity'];
        }

        $this->twig->addGlobal('cart_quantity', $totalQuantity);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
