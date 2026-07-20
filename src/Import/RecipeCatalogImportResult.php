<?php

declare(strict_types=1);

namespace App\Import;

final class RecipeCatalogImportResult
{
    public int $recipesCreated = 0;

    public int $recipesUpdated = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var list<int> */
    public array $recipeIds = [];

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function isSuccessful(): bool
    {
        return $this->errors === [];
    }
}
