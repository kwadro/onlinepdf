<?php

namespace App\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;

readonly class YamlEntityExporter
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function export(string $entityClass, string $filePath): void
    {
        if (!class_exists($entityClass)) {
            throw new \InvalidArgumentException("Entity $entityClass does not exist");
        }
        $meta = $this->em->getClassMetadata($entityClass);
        $repo = $this->em->getRepository($entityClass);
        $handle = fopen($filePath, 'w');
        $query = $repo->createQueryBuilder('e')->getQuery();
        $result = [];
        foreach ($query->toIterable() as $entity) {
            $row = $this->getRowByEntity($entity, $meta);
            $result[$row['id']] = $row;
            $this->em->clear();
        }
        fwrite($handle, Yaml::dump($result, 6, 2));
    }

    private function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (method_exists($value, '__toString')) {
            return (string)$value;
        }

        return '';
    }

    private function getRowByEntity($entity, $entityMeta): array
    {
        $row = [];
        foreach ($entityMeta->getFieldNames() as $field) {
            $value = $entityMeta->getFieldValue($entity, $field);
            $row[$field] = $this->normalizeValue($value);
        }
        foreach ($entityMeta->getAssociationMappings() as $assoc) {
            $value = $entityMeta->getFieldValue($entity, $assoc['fieldName']);
            $row[$assoc['fieldName']] = $this->normalizeAssociation($value);
        }
        return $row;
    }

    private function normalizeAssociation(mixed $value): array|string
    {
        $extendsClasses = [
            'App\Entity\RecipeTranslation',
            'App\Entity\GroupComponent',
            'App\Entity\Component',
            'App\Entity\RecipeStep'
        ];
        // toMany
        if ($value instanceof Collection) {
            $items = [];
            foreach ($value as $item) {
                $itemClass = $item::class;
                if (in_array($itemClass, $extendsClasses)) {
                    $itemMeta = $this->em->getClassMetadata($itemClass);
                    $itemRepo = $this->em->getRepository($itemClass);
                    $query = $itemRepo->createQueryBuilder('e')
                        ->getQuery();
                    foreach ($query->toIterable() as $entity) {
                        $val = $this->getRowByEntity($entity, $itemMeta);
                        if ($val) {
                            $items[] = $val;
                        }
                    }
                } else {
                    $items[] = method_exists($item, '__toString')
                        ? (string)$item
                        : (string)$item->getId();
                }
            }
            return $items;
        }
        // toOne
        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string)$value
                : (string)$value->getId();
        }
        return '';
    }
}

