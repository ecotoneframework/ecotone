<?php

namespace Ecotone\Api\Gateway;

/**
 * licence Apache-2.0
 */
interface DocumentStore
{
    public const LAST_WRITE_WINS = -1;

    public function dropCollection(string $collectionName): void;

    public function addDocument(string $collectionName, string $documentId, object|array|string $document): void;

    public function updateDocument(string $collectionName, string $documentId, object|array|string $document, int $expectedVersion): void;

    public function upsertDocument(string $collectionName, string $documentId, object|array|string $document, int $expectedVersion): void;

    public function deleteDocument(string $collectionName, string $documentId): void;

    public function getDocument(string $collectionName, string $documentId): array|object|string;

    public function getDocumentVersion(string $collectionName, string $documentId): int;

    public function findDocument(string $collectionName, string $documentId): array|object|string|null;

    /**
     * @return object[]|string[]|array
     */
    public function getAllDocuments(string $collectionName): array;

    public function countDocuments(string $collectionName): int;
}
