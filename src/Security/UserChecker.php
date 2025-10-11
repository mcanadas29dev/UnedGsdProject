<?php
// src/Security/UserChecker.php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof \App\Entity\User) {
            return;
        }

        // Bloquear usuario inactivo
        if (!$user->IsActive()) {
            // Mensaje mostrado en el login form
            throw new CustomUserMessageAccountStatusException(
                'Tu cuenta está desactivada. Contacta con el administrador.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No hace nada
    }
}
