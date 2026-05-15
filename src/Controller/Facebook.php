<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class Facebook extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ){}

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
    ): JsonResponse
    {
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
