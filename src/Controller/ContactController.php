<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
    
class ContactController extends AbstractController
{
    #[Route('/contacto', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            try {
                // Componer el email
                $emailMessage = (new Email())
                    ->from('mcanadas29dev@gmail.com')   // remitente real de Gmail
                    ->to('mcanadas29dev@gmail.com')     // destinatario (tú mismo o soporte)
                    //->replyTo($email)                   // para poder responder al usuario
                    ->subject('Nuevo mensaje de contacto: ' . $subject)
                    ->text(
                        "Has recibido un nuevo mensaje de contacto:\n\n".
                        "Nombre: $name\n".
                        "Email: $email\n".
                        "Asunto: $subject\n\n".
                        "Mensaje:\n$message"
                    )
                    ->html("
                        <h2>Nuevo mensaje de contacto</h2>
                        <p><strong>Nombre:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Asunto:</strong> $subject</p>
                        <p><strong>Mensaje:</strong><br>" . nl2br($message) . "</p>
                    ");

                // Enviar el correo
                // $mailer->send($emailMessage);

                // Debug para comprobar el envio de correo 
                try {
                    $mailer->send($emailMessage);
                    $this->addFlash('success', 'Tu mensaje se ha enviado correctamente!. ¡Gracias por contactarnos!');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Error al enviar el mensaje: ' . $e->getMessage());
                    dump($e->getMessage()); // Muestra detalles en la pantalla
                }


                // Flash de confirmación
                //$this->addFlash('success', 'Tu mensaje se ha enviado correctamente. ¡Gracias por contactarnos!');
            } catch (\Exception $e) {
                // Flash de error
                $this->addFlash('danger', 'Hubo un problema al enviar tu mensaje. Intenta de nuevo más tarde.');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/contact.html.twig');
    }
}
