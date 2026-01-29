<?php

namespace App\Import;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface EntityImportHandlerInterface
{
    const ASSOCIATION_FIELD_MAP = [
        'products' => 'name',
        'category' => 'name',
        'orderitems' => 'sku',
        'tags' => 'name',
        'sale' => 'incrementid',
        'source' => 'name',
        'client' => 'phone',
        'recipecategorys'=>'name',
        'recipe' => 'name|id',
    ];

    public function supports(string $entityClass): bool;

    public function import(string $entityClass, UploadedFile $file): ImportResult;
}
