<?php

namespace Ecotone\Dbal\DocumentStore;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Ecotone\Enqueue\CachedConnectionFactory;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Store\Document\DocumentException;
use Ecotone\Messaging\Store\Document\DocumentNotFound;
use Ecotone\Messaging\Store\Document\DocumentStore;
use Enqueue\Dbal\DbalContext;
use Interop\Queue\Exception\Exception;

final class DbalDocumentStore implements DocumentStore
{
    const ECOTONE_DOCUMENT_STORE = "ecotone_document_store";

    public function __construct(private CachedConnectionFactory $cachedConnectionFactory, private bool $initialize, private ConversionService $conversionService)
    {

    }

    public function dropCollection(string $collectionName): void
    {
        if (!$this->doesTableExists()) {
            return;
        }

        $this->getConnection()->delete(
            $this->getTableName(),
            [
                'collection' => $collectionName
            ]
        );
    }

    public function addDocument(string $collectionName, string $documentId, object|array|string $document): void
    {
        $this->createDataBaseTable();

        try {
            $type = TypeDescriptor::createFromVariable($document);

            $rowsAffected = $this->getConnection()->insert(
                $this->getTableName(),
                [
                    'collection' => $collectionName,
                    'document_id' => $documentId,
                    'document_type' => $type->toString(),
                    'document' => $this->convertToJSONDocument($type, $document)
                ],
                [
                    'collection' => Types::STRING,
                    'document_id' => Types::STRING,
                    'document_type' => Types::STRING,
                    'document' => Types::TEXT
                ]
            );
        }catch (DriverException $driverException) {
            throw DocumentException::createFromPreviousException(sprintf("Document with id %s can not be added to collection %s. The cause: %s", $documentId, $collectionName, $driverException->getMessage()), $driverException);
        }

        if (1 !== $rowsAffected) {
            throw DocumentNotFound::create(sprintf("There was a problem inserting document with id %s to collection %s. Dbal did not confirm that the record was inserted.", $documentId, $collectionName));
        }
    }

    public function updateDocument(string $collectionName, string $documentId, object|array|string $document): void
    {
        $this->createDataBaseTable();

        $rowsAffected = $this->updateDocumentInternally($document, $documentId, $collectionName);

        if (1 !== $rowsAffected) {
            throw DocumentNotFound::create(sprintf("There is no document with id %s in collection %s to update.", $documentId, $collectionName));
        }
    }

    public function upsertDocument(string $collectionName, string $documentId, object|array|string $document): void
    {
        $this->createDataBaseTable();

        $rowsAffected = $this->updateDocumentInternally($document, $documentId, $collectionName);

        if ($rowsAffected === 0) {
            $this->addDocument($collectionName, $documentId, $document);
        }
    }

    public function deleteDocument(string $collectionName, string $documentId): void
    {
        if (!$this->doesTableExists()) {
            return;
        }

        $this->getConnection()->delete(
            $this->getTableName(),
            [
                'document_id' => $documentId
            ]
        );
    }

    public function getAllDocuments(string $collectionName): array
    {
        if (!$this->doesTableExists()) {
            return [];
        }

        $select = $this->getDocumentsFor($collectionName)
            ->fetchAllAssociative();
        
        $documents = [];
        foreach ($select as $documentRecord) {
            $documents[] = $this->convertFromJSONDocument($documentRecord);
        }

        return $documents;
    }

    public function getDocument(string $collectionName, string $documentId): array|object|string
    {
        $document = $this->findDocument($collectionName, $documentId);

        if (is_null($document)) {
            throw DocumentNotFound::create(sprintf("Document with id %s does not exists in Collection %s", $documentId, $collectionName));
        }

        return $document;
    }

    public function findDocument(string $collectionName, string $documentId): array|object|string|null
    {
        if (!$this->doesTableExists()) {
            return null;
        }

        $select = $this->getDocumentsFor($collectionName)
            ->andWhere('document_id = :documentId')
            ->setParameter('documentId', $documentId, Types::TEXT)
            ->setMaxResults(1)
            ->fetchAllAssociative();

        if (!$select) {
            return null;
        }
        $select = $select[0];

        return $this->convertFromJSONDocument($select);
    }

    public function countDocuments(string $collectionName): int
    {
        if (!$this->doesTableExists()) {
            return 0;
        }

        $select = $this->getConnection()->createQueryBuilder()
            ->select('COUNT(document_id)')
            ->from($this->getTableName())
            ->andWhere('collection = :collection')
            ->setParameter('collection', $collectionName, Types::TEXT)
            ->setMaxResults(1)
            ->fetchFirstColumn();

        if ($select) {
            return $select[0];
        }

        return 0;
    }

    private function getTableName(): string
    {
        return self::ECOTONE_DOCUMENT_STORE;
    }

    private function createDataBaseTable(): void
    {
        if (!$this->initialize) {
            return;
        }

        $sm = $this->getConnection()->getSchemaManager();

        if ($this->doesTableExists()) {
            return;
        }

        $table = new Table($this->getTableName());

        $table->addColumn('collection', Types::STRING);
        $table->addColumn('document_id', Types::STRING);
        $table->addColumn('document_type', Types::TEXT);
        $table->addColumn('document', Types::JSON);

        $table->setPrimaryKey(['collection', 'document_id']);

        $sm->createTable($table);
        $this->initialize = false;
    }

    private function getConnection(): Connection
    {
        /** @var DbalContext $context */
        $context = $this->cachedConnectionFactory->createContext();

        return $context->getDbalConnection();
    }

    private function doesTableExists(): bool
    {
        if (!$this->initialize) {
            return true;
        }

        $this->initialize = !$this->getConnection()->getSchemaManager()->tablesExist([$this->getTableName()]);

        return !$this->initialize;
    }

    private function convertToJSONDocument(TypeDescriptor $type, object|array|string $document): mixed
    {
        if (!$type->isString()) {
            $document = $this->conversionService->convert(
                $document,
                $type,
                MediaType::createApplicationXPHP(),
                TypeDescriptor::createStringType(),
                MediaType::createApplicationJson()
            );
        }
        return $document;
    }

    private function updateDocumentInternally(object|array|string $document, string $documentId, string $collectionName): int
    {
        try {
            $type = TypeDescriptor::createFromVariable($document);

            $rowsAffected = $this->getConnection()->update(
                $this->getTableName(),
                [
                    'document_type' => $type->toString(),
                    'document' => $this->convertToJSONDocument($type, $document)
                ],
                [
                    'document_id' => $documentId,
                    'collection' => $collectionName
                ],
                [
                    'collection' => Types::STRING,
                    'document_id' => Types::STRING,
                    'document_type' => Types::STRING,
                    'document' => Types::STRING
                ]
            );
        } catch (DriverException $driverException) {
            throw DocumentException::createFromPreviousException(sprintf("Document with id %s can not be updated in collection %s", $documentId, $collectionName), $driverException);
        }

        return $rowsAffected;
    }

    private function getDocumentsFor(string $collectionName): \Doctrine\DBAL\Query\QueryBuilder
    {
        return $this->getConnection()->createQueryBuilder()
            ->select('document', 'document_type')
            ->from($this->getTableName())
            ->andWhere('collection = :collection')
            ->setParameter('collection', $collectionName, Types::TEXT);
    }

    private function convertFromJSONDocument(mixed $select): mixed
    {
        $documentType = TypeDescriptor::create($select['document_type']);
        if ($documentType->isString()) {
            return $select['document'];
        }

        return $this->conversionService->convert(
            $select['document'],
            TypeDescriptor::createStringType(),
            MediaType::createApplicationJson(),
            $documentType,
            MediaType::createApplicationXPHP()
        );
    }
}