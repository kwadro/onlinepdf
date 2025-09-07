<?php
// src/EasyAdmin/Field/HtmlField.php
namespace App\EasyAdmin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use App\Form\Type\LinkAreaType;

final class HtmlField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(LinkAreaType::class)
            ->addCssClass('field-html-content'); // Add a class for EasyAdmin styling
    }
}
