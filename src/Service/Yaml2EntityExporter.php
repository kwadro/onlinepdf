<?php

namespace App\Service;


use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

class Yaml2EntityExporter
{
    const IGNORE_FIELDS = [
        'created_at',
        'updated_at',
        'image',
    ];
    const IGNORE_ASSOC_FIELDS = [
        'site',
        'recipecategorys'
    ];
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function export(string $entityClass, string $filePath): void
    {
        if (!class_exists($entityClass)) {
            throw new InvalidArgumentException("Entity $entityClass does not exist");
        }
        $entities = $this->entityManager
            ->getRepository($entityClass)
            ->findAll();
        $data = [
            'entities' => [],
        ];
        foreach ($entities as $entity) {
            $processing = [];
            $data['entities'][] = $this->normalizeEntity($entity,$processing);
        }
        $yaml = Yaml::dump(
            $data,
            20,
            2,
            Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
        );
        $handle = fopen($filePath, 'w');
        fwrite($handle, $yaml);
        exit;
    }

    private function normalizeEntity(
        object $entity,
        array &$processing = [],
        int $depth = 0
    ): array {
        if ($depth > 10) {
            return [
                '_max_depth_reached' => true,
            ];
        }
        $className = get_class($entity);
        $metadata = $this->entityManager->getClassMetadata($className);
        $identifierValues = $metadata->getIdentifierValues($entity);

        $entityHash = $className . ':' . implode('-', $identifierValues);
        if (isset($processing[$entityHash])) {
            return [
                '_reference' => $entityHash,
            ];
        }
        $processing[$entityHash] = true;
        $result = [];
        foreach ($metadata->getFieldNames() as $fieldName) {
            if(in_array($fieldName,self::IGNORE_FIELDS)) {
                continue;
            }
            $value = $metadata->getFieldValue(
                $entity,
                $fieldName
            );
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $result[$fieldName] = $value;
        }


        foreach ($metadata->getAssociationNames() as $associationName) {
            if(in_array($associationName,self::IGNORE_ASSOC_FIELDS)) {
                continue;
            }
            $value = $metadata->getFieldValue(
                $entity,
                $associationName
            );
            if ($value === null) {
                $result[$associationName] = null;
                continue;
            }
            if ($value instanceof Collection) {
                $result[$associationName] = [];
                foreach ($value as $relatedEntity) {
                    $result[$associationName][] = $this->normalizeEntity(
                        $relatedEntity,
                        $processing,
                        $depth + 1
                    );
                }
                continue;
            }
            if (is_object($value)) {
                $result[$associationName] = $this->normalizeEntity(
                    $value,
                    $processing,
                    $depth + 1
                );
                continue;
            }
            $result[$associationName] = $value;
        }
        return $result;
    }
}
