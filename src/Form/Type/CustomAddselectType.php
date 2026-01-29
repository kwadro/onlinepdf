<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CustomAddselectType extends EntityType
{
    public function getBlockPrefix(): string
    {
        return 'select_with_add';
    }
}
