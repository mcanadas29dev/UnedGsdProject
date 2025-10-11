<?php

/* ORIGINAL
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

*/

/* MEJORADO PARA SEGUIDAD */

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ContactController extends AbstractController
{
    private string $mailerFrom;
    private string $mailerTo;
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private CsrfTokenManagerInterface $csrf;

    private const DISPOSABLE_EMAIL_DOMAINS = [
        'tempmail.com', 'guerrillamail.com', '10minutemail.com',
        'mailinator.com', 'throwaway.email', 'temp-mail.org'
    ];

    private const SUSPICIOUS_PATTERNS = [
        '/content-type:/i', '/bcc:/i', '/cc:/i',
        '/to:/i', '/mime-version:/i', '/<script/i',
        '/javascript:/i', '/<iframe/i', '/eval\(/i'
    ];

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        CsrfTokenManagerInterface $csrf,
        string $mailerFrom = '',
        ?string $mailerTo = null
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->csrf = $csrf;
        $this->mailerFrom = $mailerFrom ?: 'no-reply@greenharvest.com';
        $this->mailerTo = $mailerTo ?? $this->mailerFrom;
    }

    #[Route('/contacto', name: 'app_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request): Response
    {
        $session = $request->getSession();
        $formData = [];

        if ($request->isMethod('POST')) {
            $ip = $request->getClientIp() ?? 'unknown';

            // CSRF
            $token = $request->request->get('_token', '');
            if (!$this->csrf->isTokenValid(new CsrfToken('contact_form', $token))) {
                $this->addFlash('danger', 'Token CSRF inválido. Recarga la página.');
                $this->logger->warning('CSRF inválido', ['ip' => $ip]);
                return $this->redirectToRoute('app_contact');
            }

            // Honeypot
            if ($request->request->get('website') !== '') {
                $this->logger->info('Honeypot activado', ['ip' => $ip]);
                $this->addFlash('succes', 'Tu mensaje se ha enviado correctamente.');
                return $this->redirectToRoute('app_contact');
            }

            // Datos saneados
            $name = $this->sanitizeInput($request->request->get('name', ''));
            $email = $this->sanitizeInput($request->request->get('email', ''));
            $subject = $this->sanitizeInput($request->request->get('subject', ''));
            $message = $this->sanitizeInput($request->request->get('message', ''));

            $formData = compact('name', 'email', 'subject', 'message');
            $errors = $this->validateContactForm($name, $email, $subject, $message);

            if ($errors) {
                foreach ($errors as $err) {
                    $this->addFlash('danger', $err);
                }
                $session->set('contact_form_data', $formData);
                return $this->redirectToRoute('app_contact');
            }

            if ($this->isSpam($name, $email, $subject, $message)) {
                $this->logger->warning('Posible spam', ['email' => $email, 'ip' => $ip]);
                $this->addFlash('danger', 'Tu mensaje se ha enviado correctamente.');
                return $this->redirectToRoute('app_contact');
            }

            try {
                $htmlBody = $this->renderView('emails/contact_email.html.twig', [
                    'name' => htmlspecialchars($name),
                    'email' => htmlspecialchars($email),
                    'subject' => htmlspecialchars($subject),
                    'message' => htmlspecialchars($message),
                    'ip' => $ip,
                    'timestamp' => new \DateTime()
                ]);

                $textBody = $this->generateTextBody($name, $email, $subject, $message, $ip);

                $emailMessage = (new Email())
                    ->from($this->mailerFrom)
                    ->to($this->mailerTo)
                    ->subject('[GreenHarvest] ' . mb_substr($subject, 0, 120))
                    ->text($textBody)
                    ->html($htmlBody)
                    ->priority(Email::PRIORITY_NORMAL);

                if (filter_var($email, FILTER_VALIDATE_EMAIL) && !$this->isDisposableEmail($email)) {
                    $emailMessage->replyTo($email);
                }

                $this->mailer->send($emailMessage);

                $session->remove('contact_form_data');
                $this->addFlash('success', 'Tu mensaje se ha enviado correctamente. ¡Gracias por contactarnos!');
                $this->logger->info('Email enviado correctamente', ['email' => $email, 'ip' => $ip]);
            } catch (\Throwable $e) {
                $this->logger->error('Error enviando correo', [
                    'message' => $e->getMessage(),
                    'ip' => $ip,
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('danger', 'No se pudo enviar tu mensaje. Inténtalo más tarde.');
            }

            return $this->redirectToRoute('app_contact');
        }

        $formData = $session->get('contact_form_data', []);

        return $this->render('contact/contact.html.twig', [
            'form_data' => $formData
        ]);
    }

    private function sanitizeInput(string $input): string
    {
        $input = trim($input);
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $input);
    }

    private function validateContactForm(string $name, string $email, string $subject, string $message): array
    {
        $errors = [];

        if ($name === '' || mb_strlen($name) < 2) {
            $errors[] = 'El nombre es obligatorio y debe tener al menos 2 caracteres.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo electrónico no es válido.';
        } elseif ($this->isDisposableEmail($email)) {
            $errors[] = 'No se permiten correos temporales.';
        }

        if ($subject === '' || mb_strlen($subject) < 3) {
            $errors[] = 'El asunto es obligatorio y debe tener al menos 3 caracteres.';
        }

        if ($message === '' || mb_strlen($message) < 10) {
            $errors[] = 'El mensaje debe tener al menos 10 caracteres.';
        }

        return $errors;
    }

    private function isDisposableEmail(string $email): bool
    {
        $domain = mb_strtolower(substr(strrchr($email, "@"), 1));
        return in_array($domain, self::DISPOSABLE_EMAIL_DOMAINS, true);
    }

    private function isSpam(string $name, string $email, string $subject, string $message): bool
    {
        $text = "$name $subject $message";

        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return substr_count($message, 'http') > 3;
    }

    private function generateTextBody(string $name, string $email, string $subject, string $message, string $ip): string
    {
        return <<<EOT
        NUEVO MENSAJE DE CONTACTO - GreenHarvest

        Fecha: {$this->formatDateTime()}
        IP: {$ip}

        Nombre: {$name}
        Email: {$email}
        Asunto: {$subject}

        MENSAJE:
        {$message}

        Enviado desde el formulario de contacto de GreenHarvest.
        EOT;
    }

    private function formatDateTime(): string
    {
        return (new \DateTime())->format('d/m/Y H:i:s');
    }
}
