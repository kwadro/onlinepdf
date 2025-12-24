<?php

namespace App\Import;

class ImportResult
{
    public function __construct(
        public int $imported = 0,
        public int $created = 0,
        public int $updated = 0,
        public int $failed = 0,
        public array $errors = []
    ) {}
}
