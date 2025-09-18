<?php

namespace App\Controller;
use App\Entity\User;
use App\Repository\UserRepository; 
use App\Form\RegistrationFormType;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(Request $request, UserRepository $userRepository, 
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $em): Response
    {

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validar si el email ya existe
            $existingUser = $userRepository->findByEmail($user->getEmail());
            if ($existingUser) {
                $form->get('email')->addError(new FormError('Este correo ya está registrado.'));
            } else {
                // Tomar la contraseña desde el formulario
                $plainPassword = $form->get('plainPassword')->getData();

                // Hash de la contraseña
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);

                // Guardar en la base de datos
                //$entityManager = $this->getDoctrine()->getManager();
                $user->setRoles(['ROLE_USER']);
                $em->persist($user);
                $em->flush();

                // Redirigir al home o login
                return $this->redirectToRoute('app_login');
            }
        }

         return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);

    }
}
