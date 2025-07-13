<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Repository\ProductRepository;

class CartService
{
    private $session;
    private $productRepository;

    public function __construct(RequestStack $requestStack, ProductRepository $productRepository)
        {
            $this->session = $requestStack->getSession();
            $this->productRepository = $productRepository;
        }

    public function add(int $id): void
        {
            $cart = $this->session->get('cart', []);

            if (!empty($cart[$id])) {
                $cart[$id]++;
            } else {
                $cart[$id] = 1;
            }

            $this->session->set('cart', $cart);
        }

    public function remove(int $id): void
        {
            $cart = $this->session->get('cart', []);

            if (!empty($cart[$id])) {
                unset($cart[$id]);
            }

            $this->session->set('cart', $cart);
        }

    public function clear(): void
        {
            $this->session->remove('cart');
        }

    public function getCart(): array
        {
            $cart = $this->session->get('cart', []);
            $cartWithData = [];

            foreach ($cart as $id => $quantity) {
                $product = $this->productRepository->find($id);

                if ($product) {
                    $cartWithData[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'total' => $product->getPrice() * $quantity,
                    ];
                }
            }

            return $cartWithData;
        }

    public function getTotal(): float
        {
            $total = 0;
            foreach ($this->getCart() as $item) {
                $total += $item['total'];
            }

            return $total;
        }
}
