<?php

namespace App\Service;

use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

class UploaderHelper
{
    public const DUMP_FILE_DIR = '/uploads/files/';
    private string $targetDirectory;
    private RequestStack $requestStack;

    public function __construct(
        string $targetDirectory,
        RequestStack $requestStack,
    ) {
        $this->targetDirectory = $targetDirectory;
        $this->requestStack = $requestStack;
    }

    public function uploadServerFile(UploadedFile $uploadedFile): string
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = Urlizer::urlize($originalFilename) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
        $uploadedFile->move(
            $this->targetDirectory,
            $newFilename
        );
        return $newFilename;
    }

    public function setFileNameToEntity(string $newFilename, $entityInstance): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() . $request->getBasePath() : '';
        $entityInstance->setDumpLink($baseUrl . self::DUMP_FILE_DIR . $newFilename);
    }
}
