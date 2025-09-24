<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TwoFactorController extends AbstractController
{
    /**
     * Ruta para mostrar el formulario de ingreso del código 2FA
     */
    #[Route('/2fa', name: '2fa_login')]
    public function login(Request $request): Response
    {
        // Symfony Scheb se encargará de validar el código automáticamente
        // Si hay error, Scheb lo pone en la sesión y se puede mostrar
        $error = $request->getSession()->get('_scheb_2fa_error');

        return $this->render('security/2fa_form.html.twig', [
        //return $this->render('security/2fa_login.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * Ruta de check donde Symfony/Scheb valida el código
     * No necesita lógica: el bundle intercepta esta ruta
     */
    #[Route('/2fa_check', name: '2fa_login_check')]
    public function check(): void
    {
        // Este método puede estar vacío: Scheb se encarga del proceso
    }

    /**
     * (Opcional) Ruta de logout si quieres forzar cerrar sesión desde 2FA
     */
    #[Route('/2fa_logout', name: 'app_2fa_logout')]
    public function logout(): void
    {
        // Este método puede estar vacío, Symfony maneja el logout
        throw new \LogicException('Esta ruta solo se usa para Symfony logout.');
    }
}
