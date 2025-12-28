<?php

namespace App\Import\Handler;

use App\Entity\HeaderTranslation;
use App\Import\EntityImportHandlerInterface;
use App\Import\ImportResult;
use App\Service\MapIdImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class HeaderTranslationImportHandler implements EntityImportHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MapIdImporter $mapIdImporter
    ) {
    }

    public function supports(string $entityClass): bool
    {
        return $entityClass === HeaderTranslation::class;
    }

    public function import(string $entityClass, UploadedFile $file): ImportResult
    {
        $meta = $this->em->getClassMetadata($entityClass);
        $repo = $this->em->getRepository($entityClass);
        $result = new ImportResult();

        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle,null,';');

        $idIndex = array_search('id', $header);

        while (($row = fgetcsv($handle,null,';')) !== false) {
            try {
                $oldId = $row[$idIndex] ?? null;
                if (!$oldId) {
                    continue;
                }
                $id = $this->mapIdImporter->loadNewId($oldId, $entityClass);

                $isNew = false;
                if (!$id) {
                    // create new entity
                    $entity = new $entityClass();
                    $entity->setId(null);
                    $isNew = true;
                } else {
                    $entity = $repo->find($id);
                    if(!$entity){
                        continue;
                    }
                }

                foreach ($header as $i => $column) {
                    if ($column === 'id') {
                        continue;
                    }
                    $value = $row[$i] ?? null;
                    if ($meta->hasField($column)) {
                        $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $column)));
                        if (method_exists($entity, $setter)) {
                            $entity->$setter($value);
                        }
                    }
                }
                if ($isNew) {
                    $this->em->persist($entity);
                    $this->em->flush();
                    //save map data
                    $newId = $entity->getId();
                    $this->mapIdImporter->addItem($oldId, $newId, $entityClass);
                    $result->created++;
                } else {
                    $this->em->flush();
                    $newId = $entity->getId();
                    $result->updated++;
                }
                $this->em->clear();

                foreach ($header as $i => $column) {
                    if ($column === 'id') {
                        continue;
                    }
                    $entity = $repo->find($newId);
                    $value = $row[$i] ?? null;
                    if ($meta->hasAssociation($column)) {
                        $assocClass = $meta->getAssociationTargetClass($column);
                        $assocRepo = $this->em->getRepository($assocClass);
                        if ($meta->isSingleValuedAssociation($column)) {
                            $fieldToSearch = self::ASSOCIATION_FIELD_MAP[$column] ?? 'id';

                            $assocEntity = $assocRepo->findOneBy([$fieldToSearch => $value]);
                            if (!$assocEntity) {
                                $assocEntity = new $assocClass();
                                $assocEntity->setId(null);
                                $setter = 'set' . ucfirst($fieldToSearch);
                                $assocEntity->$setter($value);
                                $this->em->persist($assocEntity);
                                $this->em->flush();
                            }
                            $setter = 'set' . ucfirst($column);
                            $entity->$setter($assocEntity);
                        } elseif ($meta->isCollectionValuedAssociation($column)) {
                            $ids = explode(', ', $value);
                            $assocEntities = [];
                            foreach ($ids as $assocId) {
                                $fieldToSearch = self::ASSOCIATION_FIELD_MAP[$column] ?? 'id';
                                $assocEntity = $assocRepo->findOneBy([$fieldToSearch => $assocId]);
                                if (!$assocEntity) {
                                    $assocEntity = new $assocClass();
                                    $assocEntity->setId(null);
                                    $setter = 'set' . ucfirst($fieldToSearch);
                                    $assocEntity->$setter($assocId);
                                    $this->em->persist($assocEntity);
                                    $this->em->flush();
                                }
                                $assocEntities[] = $assocEntity;
                            }
                            $adder = 'add' . ucfirst(rtrim($column, 's'));
                            foreach ($assocEntities as $assocEntity) {
                                $entity->$adder($assocEntity);
                            }
                        }
                    }
                    $this->em->flush();
                    $this->em->clear();
                }
                $result->imported++;
            } catch (\Throwable $e) {
                $result->failed++;
                $result->errors[] = $e->getMessage();
            }
        }
        fclose($handle);
        return $result;
    }
}