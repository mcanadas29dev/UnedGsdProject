<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/', name: 'redirect_locale')]
    public function redirectToLocale(Request $request, UrlGeneratorInterface $urlGenerator): RedirectResponse
    {
        // Detecta idioma del navegador
        $locale = $request->getPreferredLanguage(['es','en','fr','it','de']) ?? 'es';

        // Redirige a home con locale
        //$url = $urlGenerator->generate('/', ['_locale' => $locale]);
        $url = $urlGenerator->generate('app_home', ['_locale' => $locale]);
        

        return new RedirectResponse($url);
    }
}
