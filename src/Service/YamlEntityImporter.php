<?php

namespace App\Service;

use App\Import\ImportResult;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Yaml;

class YamlEntityImporter
{

    private EntityManagerInterface $entityManager;
    private array $associationMapping;
    private array $subAssociationMapping;

    public function loadEntityObject($entityClass, $dataItem)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $fieldName = array_key_first($dataItem);
        $fieldValue = $dataItem[$fieldName];
        $entity = $this->entityManager
            ->getRepository($entityClass)
            ->findOneBy([$fieldName => $fieldValue]);

        if (!$entity) {
            // create new value and set id null
            $entity = new $entityClass();
            $this->setItemValue($accessor, $entity, 'id', null);
            if ($fieldName !== 'id') {
                $this->setItemValue($accessor, $entity, $fieldName, $fieldValue);
            }
        }
        return $entity;
    }
    public function loadSimpleItemData($dataItem)
    {
        $result = [];
        foreach ($dataItem as $key => $item) {
            if (is_string($item)) {
                $result [$key] = $item;
                continue;
            }
            if (is_array($item)) {
                $result[$key] = [];
                foreach ($item as $subItem) {
                    $result[$key][] = $this->loadSimpleItemData($subItem);
                }
            }
        }
        return $result;
    }
    public function loadItemData($entity, $fileItem, $prevEntity = null , $property=null)
    {
        $result = new ImportResult();
        $accessor = PropertyAccess::createPropertyAccessor();
        // load mapping for entity
        $metadata = $this->entityManager->getClassMetadata($entity::class);
        $mapping = $metadata->getAssociationMappings();

        $entity = $this->loadEntityObject($entity::class, $fileItem);
        foreach ($fileItem as $key => $item) {
            if (is_string($item)) {
                $this->setItemValue($accessor, $entity, $key, $item);
                continue;
            }
            if (is_array($item)) {
                //$subEntity = $accessor->getValue($entity, $key);
                $subEntityClass = $mapping[$key]['targetEntity'];
                $fieldName = array_key_first($item);
                $itemValue = $item[$fieldName];
                $subEntity = $this->loadEntityObject($subEntityClass, $itemValue);
                foreach ($item as $itemValue) {
                    $subEntity = $this->loadEntityObject($subEntityClass, $itemValue);
                    $this->loadSimpleItemData($subEntity, $itemValue,$entity);
                    $addFunction = substr('add' . ucfirst($key), 0, -1);
                    $entity->$addFunction($subEntity);
                    $this->entityManager->persist($entity);
                    $this->entityManager->flush();
                }
                $this->entityManager->persist($entity);
                $this->entityManager->flush();
            }
        }
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $result->created++;

        return $result;
    }
    public function setSimpleDataToEntity($entityFullClass, $fileData, $oldEntity = null, $level = 0)
    {
        $entityObject = $this->loadEntityObject($entityFullClass, $fileData);
        $accessor = PropertyAccess::createPropertyAccessor();
        array_shift($fileData);

        $metadata = $this->entityManager->getClassMetadata($entityFullClass);
        $associationMapping = $metadata->getAssociationMappings();

        foreach ($fileData as $field => $fileItem) {
            if (is_string($fileItem)) {
                $this->setItemValue($accessor, $entityObject, $field, $fileItem);
            }
            if (is_array($fileItem)) {
                $subFullClass = $associationMapping[$field]['targetEntity'];

                if(!str_contains($subFullClass, '\\')){
                    $subFullClass = "App\\Entity\\" . $subFullClass;
                }
                echo 'level : '.$level.PHP_EOL.'<br/>';
                echo 'origin class : '.$entityFullClass.' sub class: '.$subFullClass.PHP_EOL.'<br/>';
                echo 'count item : '.count($fileItem).PHP_EOL.'<br/>';
                $addFunction = substr('add' . ucfirst($field), 0, -1);
                $setFunction = substr('set' . ucfirst($field), 0, -1);

                foreach ($fileItem as $subItem) {
                    $subentity = $this->setSimpleDataToEntity($subFullClass, $subItem,$entityObject,$level+1);

                    if($subentity){
                        $this->entityManager->persist($subentity);
                        $this->entityManager->flush();
                        echo ' add function : '.$addFunction.' class: '. (get_class($entityObject)).' add : '.$subentity::class.PHP_EOL.'<br/>';
                        $entityObject->$addFunction($subentity);
                    }

                }
            }
        }
        return $entityObject;
    }

    public function import($entityManager, $entityClass, $file): ImportResult
    {
        $this->entityManager = $entityManager;
        $entityFullClass = "App\\Entity\\" . $entityClass;
        if (!class_exists($entityFullClass)) {
            throw new \InvalidArgumentException("Entity $entityFullClass does not exist");
        }
        $data = Yaml::parseFile($file->getPathname());
        $result = new ImportResult();
        foreach ($data as $dataItem) {
            $fileData = $this->loadSimpleItemData($dataItem);
            $entity = $this->setSimpleDataToEntity($entityFullClass, $fileData);
            $result->created++;
            echo 'cat :' . count($entity->getRecipecategorys()).PHP_EOL.'<br/>';
            echo 'translations :' . count($entity->getRecipetranslations()).PHP_EOL.'<br/>';
            echo 'group :' . count($entity->getRecipetranslations()[0]->getGroupcomponents()).PHP_EOL.'<br/>';
            echo 'group :' . count($entity->getRecipetranslations()[1]->getGroupcomponents()).PHP_EOL.'<br/>';
            echo 'steps :' . count($entity->getRecipetranslations()[0]->getRecipesteps()).PHP_EOL.'<br/>';
            echo 'steps :' . count($entity->getRecipetranslations()[1]->getRecipesteps()).PHP_EOL.'<br/>';
            foreach ($entity->getRecipetranslations()[1]->getGroupcomponents() as $group ) {
                echo 'group :' . $group->getName().PHP_EOL.'<br/>';
            }
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
        }

        exit;

        return $result;
    }

    private function setItemValue($accessor, $entity, $field, $value): void
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        if ($metadata->hasAssociation($field)) {
            $targetEntity = $metadata->getAssociationTargetClass($field);

            $stringField = $this->loadStringField($targetEntity);
            if ($stringField) {
                $relatedEntity = $this->entityManager
                    ->getRepository($targetEntity)
                    ->findOneBy([$stringField => $value]);

                $accessor->setValue($entity, $field, $relatedEntity);
            }
        } else {
            $accessor->setValue($entity, $field, $value);
        }
    }

    private function loadStringField(string $targetEntity): ?string
    {
        $map = [
            'App\Entity\Site' => 'domain',
            'App\Entity\Locale' => 'name',
            'App\Entity\User' => 'email',
            'App\Entity\RecipeCategory' => 'name'
        ];
        return $map[$targetEntity] ?? null;
    }
}
