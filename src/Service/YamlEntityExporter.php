<?php

namespace App\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Yaml\Yaml;

class YamlEntityExporter
{
    const NAME_CLASSES = [
        'recipecategorys',
        'locale',
        'recipe',
        'user',
    ];
    const IGNORE_FIELDS = [
        'created_at',
        'updated_at',
        'image',
        'recipe',
    ];

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
            $rowAssociative = $this->getRowAssociativeByEntity($entity);
            if (!empty($rowAssociative)) {
                $result[$rowAssociative['id']] = $rowAssociative;
            }
            $this->em->clear();

        }
        fwrite($handle, Yaml::dump($result, 8, 4));
    }

    private function getRowSimpleByEntity($entity): array
    {
        $entityMeta = $this->em->getClassMetadata($entity::class);
        $row = [];
        foreach ($entityMeta->getFieldNames() as $field) {
            if (!in_array($field, self::IGNORE_FIELDS)) {
                $value = $entityMeta->getFieldValue($entity, $field);
                $normalizeSimple = $this->normalizeValue($value);
                if (!empty($normalizeSimple)) {
                    $row[$field] = $normalizeSimple;
                }
            }
        }
        return $row;
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

    private function getValueByEntityAndField($entity, $fieldName)
    {
        $entityMeta = $this->em->getClassMetadata($entity::class);
        $value = $entityMeta->getFieldValue($entity, $fieldName);
        if (!($value instanceof Collection)) {
            if (is_object($value)) {
                return method_exists($value, '__toString')
                    ? (string)$value
                    : (string)$value->getId();
            }
        }
        return $value;
    }

    private function getRowAssociativeByEntity($entity, $level = 0, $oldField = null, $data = []): array
    {
        if ($level > 7) {
            echo 'level return: ' . $level . '<br/>';
            return $data;
        }

        foreach ($this->getRowSimpleByEntity($entity) as $k => $v) {
            echo ' key: ' . $k . ' val: ' . $v . '<br/>';
            if($oldField){
                $data[$oldField][$k] = $v;
            } else {
                $data[$k] = $v;
            }
        }

        $entityMeta = $this->em->getClassMetadata($entity::class);
        foreach ($entityMeta->getAssociationNames() as $associationName) {
            $value = $this->getValueByEntityAndField($entity, $associationName);
            if (is_string($value)) {
                if($oldField){
                    $this->mergeIntoByKey($data, $oldField, [$associationName=>$value]);
                }else{
                    $data[$associationName] = $value;
                }
                continue;
            }

            if ( in_array($associationName, self::NAME_CLASSES)) {
                foreach ($value as $item) {
                    $name = method_exists($item, '__toString')
                        ? (string)$item
                        : (string)$item->getId();
                    $res = ['id' => $item->getId(), 'name' => $name];
                    if($oldField){
                        $this->addIntoByKey($data, $oldField, $res);
                    }else{
                        $data[$associationName][] = $res;
                    }
                }

                continue;
            }
            if ($value instanceof Collection) {
                $level++;
                foreach ($value as $valItem) {
                    $temp = $this->getRowAssociativeByEntity($valItem, $level, null , []);;
                    if($oldField){
                        $this->mergeIntoByKey(
                            $data,
                            $oldField,
                            $temp
                        );
                    }else{
                        $data[$associationName][] = $temp;
                    }

                }
            }
        }

        return $data;
    }
    public function mergeIntoByKey(array &$array, string $searchKey, array $toMerge): bool
    {
        foreach ($array as $key => &$value) {
            if ($key === $searchKey && is_array($value)) {
                $value = array_merge($value, $toMerge);
                return true;
            }
            if ($key === $searchKey && $value===null) {
                $value[] = $toMerge;
                return true;
            }
            if (is_array($value)) {
                $found = $this->mergeIntoByKey($value, $searchKey, $toMerge);
                if ($found) {
                    return true;
                }
            }
        }
        return false;
    }
    public function addIntoByKey(array &$array, string $searchKey, array $toAdd): bool
    {
        foreach ($array as $key => &$value) {
            if ($key === $searchKey && is_array($value)) {
                $value[] = $toAdd;
                return true;
            }
            if (is_array($value)) {
                $found = $this->addIntoByKey($value, $searchKey, $toAdd);
                if ($found) {
                    return true;
                }
            }
        }
        $array[$searchKey] = $toAdd;
        return false;
    }
}

