<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

class UploaderHelper
{
    public const DUMP_FILE_DIR = '/uploads/files/';
    const DUMP_AVATAR_DIR = '/uploads/avatars/';
    private string $targetDirectory;
    private string $targetAvatarDirectory;
    private RequestStack $requestStack;

    public function __construct(
        string $targetDirectory,
        string $targetAvatarDirectory,
        RequestStack $requestStack,
    ) {
        $this->targetDirectory = $targetDirectory;
        $this->requestStack = $requestStack;
        $this->targetAvatarDirectory = $targetAvatarDirectory;
    }

    public function uploadServerFile(UploadedFile $uploadedFile,$entityInstance): void
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = Urlizer::urlize($originalFilename) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move(
            $this->targetDirectory,
            $newFilename
        );
        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() . $request->getBasePath() : '';
        $entityInstance->setDumpLink($baseUrl . self::DUMP_FILE_DIR . $newFilename);
    }
    public function uploadAvatar(UploadedFile $uploadedFile, $entityInstance): void
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);

        $newFilename = Urlizer::urlize($originalFilename) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();


        $uploadedFile->move(
            $this->targetAvatarDirectory,
            $newFilename
        );
        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() . $request->getBasePath() : '';
        $entityInstance->setAvatarUrl($baseUrl . self::DUMP_AVATAR_DIR . $newFilename);
    }
}
