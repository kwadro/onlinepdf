<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/{_locale}/contact', name: 'contact' )]
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Process the submitted data, e.g., send an email
            $formData = $form->getData();
            // ... (e.g., use Symfony Mailer to send an email)

            $this->addFlash('success', 'Your message has been sent!');
            return $this->redirectToRoute('contact'); // Redirect to prevent resubmission
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form,
        ]);

    }
}
