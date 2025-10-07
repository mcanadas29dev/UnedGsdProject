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
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private LoggerInterface $logger;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    /*
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response 
    {
        $users = [];
        $hasError = false;
        $search = $this->sanitizeSearchInput($request->query->get('q', ''));
        
        try {
            $queryBuilder = $userRepository->createQueryBuilder('u')
                ->orderBy('u.id', 'ASC');

            if ($search) {
                // Escapar caracteres especiales de LIKE
                $escapedSearch = $this->escapeLikeParameter($search);
                $queryBuilder
                    ->andWhere('u.email LIKE :search')
                    ->setParameter('search', '%' . $escapedSearch . '%');
            }

            $users = $paginator->paginate(
                $queryBuilder->getQuery(),
                $request->query->getInt('page', 1),
                10
            );

            // Log de búsqueda para auditoría
            if ($search) {
                $this->logger->info('User search performed', [
                    'admin_id' => $this->getUser()?->getId(),
                    'search_term' => $search,
                    'ip' => $request->getClientIp()
                ]);
            }

        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            $this->addFlash('error', 'No se puede conectar a la base de datos. Por favor, inténtelo más tarde.');
            $hasError = true;
            $this->logger->error('Database connection error', [
                'exception' => $e->getMessage(),
                'admin_id' => $this->getUser()?->getId()
            ]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->addFlash('error', 'Error al acceder a la base de datos. Por favor, contacte al administrador.');
            $hasError = true;
            $this->logger->error('Database error in user index', [
                'exception' => $e->getMessage(),
                'admin_id' => $this->getUser()?->getId()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ocurrió un problema al cargar los usuarios. Por favor, inténtelo más tarde.');
            $hasError = true;
            $this->logger->error('Unexpected error in user index', [
                'exception' => $e->getMessage(),
                'admin_id' => $this->getUser()?->getId()
            ]);
        }

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'hasError' => $hasError,
            'databaseStatus' => !$hasError,
            'search' => htmlspecialchars($search, ENT_QUOTES, 'UTF-8'), // XSS protection
        ]);
    }
    */
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository, 
        PaginatorInterface $paginator, 
        Request $request
    ): Response 
    {
        $users = [];
        $hasError = false;
        $search = $this->sanitizeSearchInput($request->query->get('q', ''));
        // 👇 También aceptamos el parámetro 'find_user' (usado por tu JS)
        $findUser = $this->sanitizeSearchInput($request->query->get('find_user', ''));

        // Usamos uno u otro
        $searchTerm = $findUser ?: $search;

        try {
            $queryBuilder = $userRepository->createQueryBuilder('u')
                ->orderBy('u.id', 'ASC');

            if ($searchTerm) {
                // Escapar caracteres especiales de LIKE
                $escapedSearch = $this->escapeLikeParameter($searchTerm);
                $queryBuilder
                    ->andWhere('u.email LIKE :search')
                    ->setParameter('search', '%' . $escapedSearch . '%');
            }

            $users = $paginator->paginate(
                $queryBuilder->getQuery(),
                $request->query->getInt('page', 1),
                10
            );

            if ($searchTerm) {
                $this->logger->info('User search performed', [
                    'admin_id' => $this->getUser()?->getId(),
                    'search_term' => $searchTerm,
                    'ip' => $request->getClientIp()
                ]);
            }

        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            $this->addFlash('error', 'No se puede conectar a la base de datos. Por favor, inténtelo más tarde.');
            $hasError = true;
            $this->logger->error('Database connection error', [
                'exception' => $e->getMessage(),
                'admin_id' => $this->getUser()?->getId()
            ]);
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->addFlash('error', 'Error al acceder a la base de datos. Por favor, contacte al administrador.');
            $hasError = true;
            $this->logger->error('Database error in user index', [
                'exception' => $e->getMessage(),
                'admin_id' => $this->getUser()?->getId()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ocurrió un problema al cargar los usuarios. Por favor, inténtelo más tarde.');
            $hasError = true;
            $this->logger->error('Unexpected error in user index', [
                'exception' => $e->getMessage(),
                'admin_id' => $this->getUser()?->getId()
            ]);
        }

        // 👇 NUEVO BLOQUE: si es una petición AJAX, devolvemos solo la tabla
        if ($request->isXmlHttpRequest()) {
            return $this->render('user/_table.html.twig', [
                'users' => $users,
            ]);
        }

        // Renderizado normal (no AJAX)
        return $this->render('user/index.html.twig', [
            'users' => $users,
            'hasError' => $hasError,
            'databaseStatus' => !$hasError,
            'search' => htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'),
        ]);
    }


    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validación adicional de contraseña
            $plainPassword = $form->get('password')->getData();
            
            if (!$plainPassword || strlen($plainPassword) < 8) {
                $form->get('password')->addError(new FormError('La contraseña debe tener al menos 8 caracteres.'));
                $this->addFlash('danger', 'La contraseña no cumple con los requisitos mínimos.');
                
                return $this->render('user/new.html.twig', [
                    'user' => $user,
                    'form' => $form,
                ]);
            }

            // Verificar duplicado de email
            $existingUser = $userRepository->findByEmail($user->getEmail());

            if ($existingUser) {
                $form->get('email')->addError(new FormError('El email ya existe. Por favor, usa otro.'));
                $this->addFlash('danger', 'El correo ya existe. Por favor, usa otro.');
            } else {
                // Hash de contraseña
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );
                
                $entityManager->persist($user);
                $entityManager->flush();

                // Log de auditoría
                $this->logger->info('New user created', [
                    'created_user_id' => $user->getId(),
                    'created_user_email' => $user->getEmail(),
                    'admin_id' => $this->getUser()?->getId(),
                    'ip' => $request->getClientIp()
                ]);

                $this->addFlash('success', 'Usuario creado exitosamente.');
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
        // Verificar que el usuario no esté desactivado
        /*
        if (!$user->isActive()) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } */

        $this->logger->info('User profile viewed', [
            'viewed_user_id' => $user->getId(),
            'admin_id' => $this->getUser()?->getId()
        ]);

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        GoogleAuthenticatorInterface $googleAuthenticator
    ): Response
    {
        // Verificar que el usuario esté activo
        /*
        if (!$user->isActive()) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        } */

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar contraseña si se cambia
            $plainPassword = $form->get('password')->getData();
            
            if ($plainPassword) {
                // Validar longitud mínima
                if (strlen($plainPassword) < 8) {
                    $form->get('password')->addError(new FormError('La contraseña debe tener al menos 8 caracteres.'));
                    $this->addFlash('danger', 'La contraseña no cumple con los requisitos mínimos.');
                    
                    return $this->render('user/edit.html.twig', [
                        'user' => $user,
                        'form' => $form->createView(),
                    ]);
                }
                
                $user->setPassword(
                    $this->passwordHasher->hashPassword($user, $plainPassword)
                );

                $this->logger->info('User password changed', [
                    'user_id' => $user->getId(),
                    'admin_id' => $this->getUser()?->getId(),
                    'ip' => $request->getClientIp()
                ]);
            }

            // Obtener el valor del checkbox 2FA
            $enable2fa = $form->get('isGoogleAuthenticatorEnabled')->getData();

            if ($enable2fa && !$user->isGoogleAuthenticatorEnabled()) {
                // Se activó 2FA: generar secreto y códigos de respaldo
                $secret = $googleAuthenticator->generateSecret();
                $user->setGoogleAuthenticatorSecret($secret);
                $user->setGoogleAuthenticatorEnabled(true);

                // Generar códigos de respaldo seguros
                $backupCodes = $this->generateSecureBackupCodes(8);
                $user->setBackupCodes($backupCodes);

                $this->logger->info('2FA enabled for user', [
                    'user_id' => $user->getId(),
                    'admin_id' => $this->getUser()?->getId()
                ]);

            } elseif (!$enable2fa && $user->isGoogleAuthenticatorEnabled()) {
                // Se desactivó 2FA: limpiar secreto y códigos
                $user->setGoogleAuthenticatorSecret(null);
                $user->setBackupCodes([]);
                $user->setGoogleAuthenticatorEnabled(false);

                $this->logger->info('2FA disabled for user', [
                    'user_id' => $user->getId(),
                    'admin_id' => $this->getUser()?->getId()
                ]);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Usuario actualizado exitosamente.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $form->get('isGoogleAuthenticatorEnabled')->setData($user->isGoogleAuthenticatorEnabled());

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        User $user, 
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Token de seguridad inválido.');
            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        // Verificar que no sea el último admin
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $activeAdmins = $userRepository->count([
                'isActive' => true,
                // Aquí deberías implementar una consulta que cuente admins activos
            ]);
            
            if ($activeAdmins <= 1) {
                $this->addFlash('error', 'No se puede desactivar el último administrador del sistema.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        // Soft delete
        $user->setIsActive(false);
        $entityManager->flush();

        // Log de auditoría
        $this->logger->warning('User deactivated', [
            'deactivated_user_id' => $user->getId(),
            'deactivated_user_email' => $user->getEmail(),
            'admin_id' => $this->getUser()?->getId(),
            'ip' => $request->getClientIp()
        ]);

        $this->addFlash('success', 'Usuario desactivado exitosamente.');
        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Sanitiza la entrada de búsqueda
     */
    private function sanitizeSearchInput(string $input): string
    {
        // Eliminar espacios en blanco al inicio y final
        $input = trim($input);
        
        // Limitar longitud
        if (strlen($input) > 100) {
            $input = substr($input, 0, 100);
        }
        
        // Eliminar caracteres de control
        $input = preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
        
        return $input;
    }

    /**
     * Escapa caracteres especiales de LIKE para evitar búsquedas maliciosas
     */
    private function escapeLikeParameter(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Genera códigos de respaldo seguros
     */
    private function generateSecureBackupCodes(int $count): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            // Generar códigos más largos y seguros
            $codes[] = bin2hex(random_bytes(8)); // 16 caracteres hex
        }
        return $codes;
    }
}

/*
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
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;


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
    public function edit(
        Request $request, 
        User $user, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        GoogleAuthenticatorInterface $googleAuthenticator): Response
        {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar contraseña si se cambia
            $plain = $form->get('password')->getData();
            if ($plain) {
                $user->setPassword(
                    $passwordHasher->hashPassword($user, $plain)
                );
            }

            // Obtener el valor del checkbox 2FA
            $enable2fa = $form->get('isGoogleAuthenticatorEnabled')->getData();

            if ($enable2fa && !$user->isGoogleAuthenticatorEnabled()) {
                // Se activó 2FA: generar secreto y códigos de respaldo
                $secret = $googleAuthenticator->generateSecret();
                $user->setGoogleAuthenticatorSecret($secret);
                $user->setGoogleAuthenticatorEnabled(true);

                // Generar códigos de respaldo (por ejemplo 8 códigos aleatorios)
                $backupCodes = [];
                for ($i = 0; $i < 8; $i++) {
                    $backupCodes[] = bin2hex(random_bytes(4)); // 8 caracteres hex
                }
                $user->setBackupCodes($backupCodes);
                

            } elseif (!$enable2fa && $user->isGoogleAuthenticatorEnabled()) {
                // Se desactivó 2FA: limpiar secreto y códigos
                $user->setGoogleAuthenticatorSecret(null);
                $user->setBackupCodes([]);
                $user->setGoogleAuthenticatorEnabled(false);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }
        $form->get('isGoogleAuthenticatorEnabled')->setData($user->isGoogleAuthenticatorEnabled()); // Para que se muestre en edit

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            
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

*/
