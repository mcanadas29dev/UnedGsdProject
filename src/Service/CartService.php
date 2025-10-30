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
    /*
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
    */
    public function add(int $id, int $quantity = 1): void
        {
            $cart = $this->session->get('cart', []);

            if (!empty($cart[$id])) {
                $cart[$id] += $quantity;
            } else {
                $cart[$id] = $quantity;
            }

            // Si la cantidad baja a 0 o menos, eliminamos el producto
            if ($cart[$id] <= 0) {
                unset($cart[$id]);
            }

            $this->session->set('cart', $cart);
        }

    public function set(int $id, int $quantity): void
        {
            $cart = $this->session->get('cart', []);
            if ($quantity <= 0) {
                unset($cart[$id]);
            } else {
                $cart[$id] = $quantity;
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
                        'total' => $product->getCurrentPrice() * $quantity,
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

    public function getItemCount(): int
        {
            $cart = $this->session->get('cart', []);
            return count($cart); // número de productos distintos
        }
}
