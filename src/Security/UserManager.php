<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class UserManager
{

    public function __construct(
        private EmailVerifier $emailVerifier,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * @throws RandomException
     */
    public function updateOrCreateUser(string $email, $name, $picture, $externalId = null, $externalType = null): array
    {
        $user = $this->getUserByEmail($email);
        $isNewUser = false;
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $randomString = bin2hex(random_bytes(16));
            $randomPassword = $this->userPasswordHasher->hashPassword($user, $randomString);
            $user->setPassword($randomPassword);
            $isNewUser = true;

        }
        if ($name) {
            $names = explode(' ', $name);
            $user->setFirstName($names[0]);
            $user->setLastName($names[1] ?? '');
        }
        if ($picture) {
            $user->setAvatarUrl($picture);
        }
        if ($externalId) {
            $user->setExternalId($externalId);
            $user->setExternalType($externalType);
        }
        $this->em->persist($user);
        $this->em->flush();
        return [$user, $isNewUser];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendEmailVerificationNotification(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('pdf-editor@kwadro.com.ua', 'Confirmation Email'))
                ->to((string)$user->getEmail())
                ->subject('Please Confirm your Email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function getUserByExternalData(string $externalId, $externalType): ?User
    {
        return $this->em->getRepository(User::class)
            ->findOneBy(
                [
                    'external_id' => $externalId,
                    'external_type' => $externalType
                ]
            );
    }
}
