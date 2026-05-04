<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContactForm;
use App\Form\ContactFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContactController extends AbstractController
{

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    #[Route('/{_locale}/contact', name: 'contact')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        HttpClientInterface $client
    ): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $token = $request->request->get('recaptcha_token');
            $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $_ENV['RECAPTCHA_SECRET_KEY'],
                    'response' => $token,
                    'remoteip' => $request->getClientIp(),
                ]
            ]);
            $result = $response->toArray();
            if (
                !$result['success'] ||
                $result['score'] < 0.5 ||
                $result['action'] !== 'contact'
            ) {
                throw new \Exception('Captcha validation failed');
            }
            $formData = $form->getData();
            $contact = new ContactForm();
            $contact->setEmail($formData['email']);
            $contact->setName($formData['full_name']);
            $contact->setMessage($formData['message']);
            $contact->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Your message has been sent!');
            return $this->redirectToRoute('contact');
        }

        return $this->render('contact/index.html.twig', [
            'ContactFormType' => $form,
            'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'],
        ]);
    }
}
