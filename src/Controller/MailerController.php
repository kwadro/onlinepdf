<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class MailerController extends AbstractController
{
    #[Route('/{_locale}/mailer', name: 'smtp-mailer')]
    public function sendEmail(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from('pdf-editor@kwadro.com.ua')
                ->to('kwadro2010@gmail.com')
                //->cc('cc@example.com')
                //->bcc('bcc@example.com')
                //->replyTo('fabien@example.com')
                //->priority(Email::PRIORITY_HIGH)
                ->subject('Time for Symfony Mailer!')
                ->text('Sending emails is fun again!')
                ->html('<p>See Twig integration for better HTML integration!</p>');
            $mailer->send($email);
            $message =  'sending emails is fun again!';
        }catch (TransportExceptionInterface $exception) {
            $message =  $exception->getMessage();
        }

        return $this->render('email/sendemail.html.twig',['message' => $message]);
    }
}
