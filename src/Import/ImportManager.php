<?php

namespace App\Import;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportManager
{
    /** @var EntityImportHandlerInterface[] */
    private iterable $handlers;

    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function import(string $entityClass, UploadedFile $file): ImportResult
    {
        if (!str_contains($entityClass, '\\')) {
            $entityClass = 'App\\Entity\\' . ucfirst($entityClass);
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($entityClass)) {
                return $handler->import($entityClass,$file);
            }
        }
        throw new \RuntimeException("No import handler for $entityClass");
    }
}
