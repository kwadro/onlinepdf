<?php

namespace App\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class CustomLocaleType extends EntityType
{
    public function getBlockPrefix(): string
    {
        return 'locale_with_add';
    }
}
