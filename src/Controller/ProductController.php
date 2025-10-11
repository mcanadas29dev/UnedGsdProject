<?php
namespace App\Controller;

use App\Entity\Offer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/product')]
#[IsGranted('ROLE_ADMIN')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        PaginatorInterface $paginator,
        Request $request ): Response {

        $search = $request->query->get('q'); // capturamos el término de búsqueda

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('p.name LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('product/_table.html.twig', [
                'products' => $pagination,
            ]);
        }

        return $this->render('product/index.html.twig', [
            'products' => $pagination,
            'search' => $search, // enviamos el valor para mantenerlo en la vista
        ]);
    }

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    $product->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Error al subir la imagen');
                }
            }
            
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Producto creado correctamente.');
            return $this->redirectToRoute('product_index');
            
        }
        
        return $this->render('product/new.html.twig', [
            'form' => $form,
            'form_title' => 'Crear nuevo producto',
        ]);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('product_images_directory'),
                        $newFilename
                    );
                    $product->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Error al subir la imagen');
                }
        }

            
            $entityManager->flush();

            $this->addFlash('success', 'Producto actualizado correctamente.');
            return $this->redirectToRoute('product_index');
            
        }
        
        return $this->render('product/edit.html.twig', [ // Reutilizamos la misma vista
            'form' => $form,
            'form_title' => 'Editar producto',
            'product' => $product,
        ]);
    }

    #[Route('/{id}', name: 'product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
              // Si se cumple alguna de estas condiciones NO se puede borrar 
              // Contar cuántos productos han tenido o tiene oferta
              $offerCount = $entityManager->getRepository(Offer::class)->count (['product' => $product]);
              if ($offerCount > 0){
                $this->addFlash('danger', 'No se puede eliminar el producto porque está en alguna oferta');
                return $this->redirectToRoute('product_index');
              }
            // Contar cuántos productos están en pedidos
              $orderCount = $entityManager->getRepository(OrderItem::class)->count(['product' => $product]);
              if ($orderCount > 0){
                $this->addFlash('danger', 'No se puede eliminar el producto porque está en algún pedido');
                return $this->redirectToRoute('product_index');
              }
             
        }
        $entityManager->remove($product);
        $entityManager->flush();
        $this->addFlash('danger', 'Producto eliminado.');
        return $this->redirectToRoute('product_index');
    }
}



// Nueva versión del controlador con seguridad 

/*
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'product_index', methods: ['GET'])]
    public function index(
        ProductRepository $productRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $search = trim($request->query->get('q', ''));

        // Sanitizar entrada básica
        $search = htmlspecialchars($search, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $queryBuilder = $productRepository->createQueryBuilder('p')
            ->orderBy('p.id', 'DESC');

        if ($search !== '') {
            $queryBuilder->andWhere('LOWER(p.name) LIKE LOWER(:search)')
                         ->setParameter('search', '%' . $search . '%');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            max(1, $request->query->getInt('page', 1)), // evita page < 1
            10
        );

        if ($request->isXmlHttpRequest()) {
            return $this->render('product/_table.html.twig', [
                'products' => $pagination,
            ]);
        }

        return $this->render('product/index.html.twig', [
            'products' => $pagination,
            'search' => $search,
        ]);
    }

    #[Route('/new', name: 'product_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . bin2hex(random_bytes(4)) . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('product_images_directory'), $newFilename);
                    $product->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Error al subir la imagen: ' . $e->getMessage());
                }
            }

            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', 'Producto creado correctamente.');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'form' => $form,
            'form_title' => 'Crear nuevo producto',
        ]);
    }

    #[Route('/{id}', name: 'product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . bin2hex(random_bytes(4)) . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('product_images_directory'), $newFilename);
                    $product->setImageName($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Error al subir la imagen: ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Producto actualizado correctamente.');
            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form,
            'form_title' => 'Editar producto',
            'product' => $product,
        ]);
    }

    #[Route('/{id}', name: 'product_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF inválido.');
            return $this->redirectToRoute('product_index');
        }

        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('danger', 'Producto eliminado.');
        return $this->redirectToRoute('product_index');
    }
}

*/
