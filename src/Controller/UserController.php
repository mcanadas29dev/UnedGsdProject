<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\FormError;
use Knp\Component\Pager\PaginatorInterface;




#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route(name: 'app_user_index1', methods: ['GET'])]
    public function index1(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
        {
        $users = [];
        $hasError = false;
        $search = $request->query->get('q'); // capturamos término de búsqueda
        try {
            
            //dd($search);
            $queryBuilder = $userRepository->createQueryBuilder('u')
                ->orderBy('u.id', 'ASC');

            if ($search) {
                $queryBuilder
                        ->andWhere('u.email LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
            }

            $users = $paginator->paginate(
                $queryBuilder->getQuery(),
                $request->query->getInt('page', 1),
                10
            );
        

        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            $this->addFlash('error', '❌ No se puede conectar a la base de datos. Verifique que el servicio esté iniciado.');
            $hasError = true;
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->addFlash('error', '❌ Error en la base de datos: ' . $e->getMessage());
            $hasError = true;
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Ocurrió un problema al cargar los usuarios: ' . $e->getMessage());
            $hasError = true;
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'hasError' => $hasError,
            'databaseStatus' => !$hasError,
            'search' => $search, // pasamos valor para mantenerlo en el input
        ]);
    }


    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response 
    {
        $users = [];
        $hasError = false;
        $search = $request->query->get('q'); // capturamos término de búsqueda
        try {
            
            dd($search);
            $queryBuilder = $userRepository->createQueryBuilder('u')
                ->orderBy('u.id', 'ASC');

            if ($search) {
                $queryBuilder
                        ->andWhere('u.email LIKE :search')
                        ->setParameter('search', '%' . $search . '%');
            }

            $users = $paginator->paginate(
                $queryBuilder->getQuery(),
                $request->query->getInt('page', 1),
                10
            );
        

        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            $this->addFlash('error', '❌ No se puede conectar a la base de datos. Verifique que el servicio esté iniciado.');
            $hasError = true;
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->addFlash('error', '❌ Error en la base de datos: ' . $e->getMessage());
            $hasError = true;
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Ocurrió un problema al cargar los usuarios: ' . $e->getMessage());
            $hasError = true;
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'hasError' => $hasError,
            'databaseStatus' => !$hasError,
            'search' => $search, // pasamos valor para mantenerlo en el input
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             $plainPassword = $form->get('password')->getData();

            if ($plainPassword) {
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );
            }
            $existingUser = $userRepository->findByEmail($user->getEmail());

            if ($existingUser) {
                $form->get('email')->addError(new FormError('El email ya existe. Por favor, usa otro'));
                $this->addFlash('danger', 'El correo ya existe. Por favor, usa otro.');
            } else {
                $entityManager->persist($user);
                $entityManager->flush();
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
            
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $passwordHasher,EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('password')->getData();
            if ($plain) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plain)
                );
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            //$entityManager->remove($user);
            $user->setIsActive(false); // Soft delete
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
