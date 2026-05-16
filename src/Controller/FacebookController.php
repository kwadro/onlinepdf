<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\FacebookEmailType;
use App\Security\CustomAuthenticator;
use App\Security\UserManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FacebookController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserManager $userManager,
        private readonly LoggerInterface $logger,
        private UserAuthenticatorInterface $userAuthenticator,
    ) {
    }

    /**
     * @throws RandomException
     * @throws TransportExceptionInterface
     */
    #[Route('/facebook/email', name: 'facebook_email')]
    public function addEmail(
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        CustomAuthenticator $authenticator,
        TranslatorInterface $translator
    ): Response {
        $form = $this->createForm(
            FacebookEmailType::class,
            [],
            [
                'csrf_protection' => true,
                'csrf_field_name' => '_token',
                'csrf_token_id' => 'facebook_email_form'
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $email = $formData['email'];
            $user = $this->userManager->getUserByEmail($email);
            if ($user) {
                $this->addFlash('error', $translator->trans('email.already_exists', ['%email%' => $email]));
                return $this->redirectToRoute('facebook_email');
            }
            $userData = $request->getSession()->get('facebook_user_data');
            $this->logger->info('Facebook user data 2 : ' . print_r($userData, true));
            list($user, $isNewUser) = $this->userManager->updateOrCreateUser(
                $email,
                $userData['name'],
                $userData['avatarUrl'],
                $userData['id'],
                CustomAuthenticator::EXTERNAL_TYPE_FACEBOOK
            );
            if ($isNewUser) {
                $this->userManager->sendEmailVerificationNotification($user);
            }
            $this->addFlash('success', 'Account created success!');
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }
        return $this->render('facebook/add-email.html.twig', [
            'formEmail' => $form
        ]);
    }

    #[Route('/facebook/data-deletion/status/{id}', name: 'facebook_data_deletion_status')]
    public function status(string $id): JsonResponse
    {
        return new JsonResponse([
            'status' => 'deleted'
        ]);
    }

    #[Route('/{_locale}/facebook/data-deletion', name: 'facebook_data_deletion', methods: ['POST'])]
    public function deleteFacebookData(
        Request $request
    ): JsonResponse {
        $signedRequest = $request->request->get('signed_request');
        if (!$signedRequest) {
            return new JsonResponse(['error' => 'Missing signed_request'], 400);
        }
        $data = $this->parseSignedRequest($signedRequest);
        if (!$data || empty($data['user_id'])) {
            return new JsonResponse(['error' => 'Invalid signed_request'], 400);
        }
        $facebookUserId = $data['user_id'];
        $user = $this->em
            ->getRepository(User::class)
            ->findOneBy(['facebook_id' => $facebookUserId]);
        if ($user) {
            $this->em->remove($user);
            $this->em->flush();
        }
        $confirmation_code = 'abc123test!_&low';
        return new JsonResponse([
            'url' => $this->generateUrl(
                'facebook_data_deletion_status',
                ['id' => base64_encode($facebookUserId)],
                true
            ),
            'confirmation_code' => $confirmation_code
        ]);
    }

    private function parseSignedRequest(string $signedRequest): ?array
    {
        [$encodedSig, $payload] = explode('.', $signedRequest, 2);
        $secret = $_ENV['FACEBOOK_APP_SECRET'];
        $sig = $this->base64UrlDecode($encodedSig);
        $data = json_decode($this->base64UrlDecode($payload), true);
        $expectedSig = hash_hmac('sha256', $payload, $secret, true);
        if (!hash_equals($sig, $expectedSig)) {
            return null;
        }
        return $data;
    }

    private function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
