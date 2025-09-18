<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class ContactController extends AbstractController
{
    #[Route('/contacto', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(
        Request $request,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            // Componer el email
            $emailMessage = (new Email())
                #->from($email)
                ->from('no-reply@greenharvest.internal') // remitente válido
                ->to('mcanadas29dev@gmail.com') // 📌 cámbialo por tu correo real
                ->replyTo($email) // aquí el correo del usuario
                ->subject('Nuevo mensaje de contacto: ' . $subject)
                ->text("Has recibido un nuevo mensaje de contacto:\n\n".
                       "Nombre: $name\n".
                       "Email: $email\n".
                       "Asunto: $subject\n\n".
                       "Mensaje:\n$message");

            // Enviar el correo
            $mailer->send($emailMessage);

            // Flash message de confirmación
            $this->addFlash('success', 'Tu mensaje se ha enviado correctamente. ¡Gracias por contactarnos!');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/contact.html.twig');
    }
}
