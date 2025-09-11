<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class MailerController extends AbstractController
{
    #[Route('/{_locale}/mailer', name: 'smtp-mailer')]
    public function sendEmail(TransportInterface $mailer): Response
    {
        try {
            $email = (new Email())
                //->from('pdf-editor@kwadro.com.ua')
                ->to('kwadro2010@gmail.com')
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                ->replyTo('pdf-editor@kwadro.com.ua')
                ->priority(Email::PRIORITY_HIGH)
                ->subject('Time for Symfony Mailer!')
                ->text('Sending emails is fun again!')
                ->html('<p>See Twig integration for better HTML integration test !</p>');
            $mailer->send($email);

//            $dsn = 'smtp://pdf-editor%40kwadro.com.ua:437jPp7zCK@mail.adm.tools:2525';
//            $transport = Transport::fromDsn($dsn);
//            $customMailer = new Mailer($transport);
//            $customMailer->send($email);

            $message = 'sending emails is fun again test !' . time();
        } catch (TransportExceptionInterface $exception) {
            $message = $exception->getMessage();
        }

        return $this->render('email/sendemail.html.twig', ['message' => $message]);
    }
}
