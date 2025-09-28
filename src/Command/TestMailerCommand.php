<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestMailerCommand extends Command
{
    protected static $defaultName = 'app:test-mailer';
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Envía un correo de prueba usando Symfony Mailer y Gmail SMTP');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('mcanadas29dev@gmail.com')   // tu cuenta Gmail
            ->to('mcanadas29@alumno.uned.es')     // mismo correo o cualquier destinatario
            ->subject('Prueba de envío de correo GreenHarvest')
            ->text('Este es un correo de prueba enviado desde Symfony usando Gmail SMTP.');

        try {
            $this->mailer->send($email);
            $output->writeln('<info>Correo enviado correctamente ✅</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>Error al enviar el correo:</error> ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
