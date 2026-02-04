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

class ContactController extends AbstractController
{

    #[Route('/{_locale}/contact', name: 'contact')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
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
        ]);
    }
}
