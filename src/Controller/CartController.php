<?php

namespace App\Controller;



use App\Repository\ProductRepository;
use App\Repository\OrderStatusRepository;
use App\Repository\OfferRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Entity\Product;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatus;
use App\Entity\Offer;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/cart', name: 'cart_')]
class CartController extends AbstractController
{
    
    #[Route('/', name: 'index')]
    public function index(CartService $cartService, OfferRepository $offerRepository): Response
    {
        $cartItems = $cartService->getCart();
        $now = new \DateTimeImmutable();

        foreach ($cartItems as &$item) {
            $product = $item['product'];
            $offer = $offerRepository->findActiveForProduct($product, $now);

            if ($offer) {
                $item['originalPrice'] = $product->getPrice();
                $item['price'] = $offer->getOfferPrice();
            } else {
                $item['price'] = $product->getPrice();
            }
        }

        return $this->render('cart/index.html.twig', [
            //'cartItems' => $cartService->getCart(),
            'total' => $cartService->getTotal(),
            'cartItems' => $cartItems,
            'cart_quantity' => $cartService->getItemCount(), // 🔹 productos distintos
            //'total' => array_reduce($cartItems, fn($total, $item) => $total + ($item['price'] * $item['quantity']), 0),
            
        ]);
    }

    #[Route('/add/{id}', name: 'add')]
    public function add(int $id, CartService $cartService): Response
    {
        $cartService->add($id);
        return $this->redirectToRoute('cart_index');
    }

    // Se añade de la Tienda
    #[Route('/carrito/agregar/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function addToCart(Request $request, Product $product, CartService $cartService): Response
        {
            
            $quantity = $request->request->getInt('quantity', 1);
            $cartService->add($product->getId(), $quantity);

            return $this->redirectToRoute('app_tienda');
        }

    #[Route('/remove/{id}', name: 'remove')]
    public function remove(int $id, CartService $cartService): Response
    {
        $cartService->remove($id);
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/clear', name: 'clear')]
    public function clear(CartService $cartService): Response
    {
        $cartService->clear();
        return $this->redirectToRoute('cart_index');
    }

    #[Route('/checkout', name: 'checkout')]
    public function checkout(Request $request, CartService $cartService, OfferRepository $offerRepository): Response
    {
        try {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $cartItems = $cartService->getCart();
        $lineItems = [];
        $now = new \DateTimeImmutable(); 

        foreach ($cartItems as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];

            // Usa el método del repositorio
            $offer = $offerRepository->findActiveForProduct($product, $now);

            $price = $offer ? $offer->getOfferPrice() : $product->getPrice();

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int)($price * 100),
                    'product_data' => [
                        'name' => $product->getName(),
                    ],
                ],
                'quantity' => $quantity,
            ];
        }
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [$lineItems],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('cart_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            //'success_url' => $this->generateUrl('cart_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('cart_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $this->getUser()->getUserIdentifier(),
        ]);
        $stripeSessionIdFromStripe = $session->id;
        return $this->redirect($session->url, 303);
        }
     catch (\Stripe\Exception\ApiErrorException $e) {
            // Error específico de Stripe
            $this->addFlash('danger', 'Error en el proceso de pago: ' . $e->getMessage());
        }
        catch (\Exception $e) {
            // Cualquier otro error
            $this->addFlash('danger', 'Ocurrió un error inesperado: Revise Configuración y conexión con Stripe');
        }

    // En caso de error, redirigimos al carrito
    return $this->redirectToRoute('cart_index');
    }

    #[Route('/success', name: 'success')]
    public function success(
        Request $request, 
        CartService $cartService, 
        EntityManagerInterface $em, 
        OrderStatusRepository $orderStatusRepo,
        OfferRepository $offerRepository): Response
        {
            
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
            
            $user = $this->getUser();
            $cartItems = $cartService->getCart();

            if (empty($cartItems)) {
                $this->addFlash('warning', 'El carrito está vacío.');
                return $this->redirectToRoute('cart_index');
            }

            // Buscar el estado "pagado"
            $paidStatus = $orderStatusRepo->findOneBy(['name' => 'pagado']);

            if (!$paidStatus) {
                throw new \Exception('No existe el estado "pagado". Crea este estado en la base de datos.');
            }

            // Crear nuevo pedido
            $order = new Order();
            $order->setUser($user);
            $order->setStatus($paidStatus);
            $order->setCreatedAt(new \DateTimeImmutable());

            $total = 0;
            $now = new \DateTimeImmutable();
            foreach ($cartItems as $item) {

                $product = $item['product'];
                $quantity = $item['quantity'];

                $offer = $offerRepository->findActiveForProduct($product, $now);
                $price = $offer ? $offer->getOfferPrice() : $product->getPrice();

                //$price = $product->getPrice();

                $orderItem = new OrderItem();
                $orderItem->setOrder($order);
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setPrice($price);
                //$orderItem->setSubtotal($price * $quantity);

                $em->persist($orderItem);

                $total += $price * $quantity;
            }

            //$order->setTotal($total);
            $sessionId = $request->query->get('session_id');
            if (!$sessionId) {
                throw $this->createNotFoundException('No session ID provided in URL');
            }
            $order->setStripeSessionId($sessionId);
            //$order->setStripeSessionId($session->id);
            $em->persist($order);
            $em->flush();

            $cartService->clear(); // vaciar el carrito al pagar
            return $this->render('cart/success.html.twig');
        }

    #[Route('/cancel', name: 'cancel')]
    public function cancel(): Response
        {
            return $this->render('cart/cancel.html.twig');
        }

    #[Route('/increment/{id}', name: 'increment')]
    public function increment(int $id, CartService $cartService): Response
        {
            $cartService->add($id, 1);
            return $this->redirectToRoute('cart_index');
        }
 
        #[Route('/decrement/{id}', name: 'decrement')]
    public function decrement(int $id, CartService $cartService): Response
        {
            $cartService->add($id, -1);
            return $this->redirectToRoute('cart_index');
        }
    
    #[Route('/update/{id}', name: 'update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cartService): Response
        {
            $quantity = max(1, $request->request->getInt('quantity', 1));
            $cartService->set($id, $quantity);
            return $this->redirectToRoute('cart_index');
        }
    
    #[Route('/update/{id}', name: 'update_ajax', methods: ['POST'])]
    public function updateAjax(int $id, Request $request, CartService $cartService, OfferRepository $offerRepository): Response
        {
            $quantity = max(1, $request->request->getInt('quantity', 1));
            $cartService->set($id, $quantity);

            $cartItems = $cartService->getCart();
            $now = new \DateTimeImmutable();
            foreach ($cartItems as &$item) {
                $product = $item['product'];
                $offer = $offerRepository->findActiveForProduct($product, $now);
                $item['price'] = $offer ? $offer->getOfferPrice() : $product->getPrice();
            }

            $total = array_reduce($cartItems, fn($t, $item) => $t + ($item['price'] * $item['quantity']), 0);

            return $this->json([
                'subtotal' => number_format($cartItems[$id]['price'] * $cartItems[$id]['quantity'], 2, ',', '.') . ' €',
                'total' => number_format($total, 2, ',', '.') . ' €'
            ]);
        }

        
}
