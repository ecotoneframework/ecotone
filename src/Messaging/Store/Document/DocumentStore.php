<?php

namespace Ecotone\Messaging\Store\Document;

interface DocumentStore
{
    public function dropCollection(string $collectionName): void;

    /**
     * @throws DocumentException
     */
    public function addDocument(string $collectionName, string $documentId, object|array|string $document): void;

    /**
     * @throws DocumentException
     */
    public function updateDocument(string $collectionName, string $documentId, object|array|string $document): void;

    /**
     * Same as replaceDoc except that doc is added to collection if it does not exist.
     *
     * @throws DocumentException
     */
    public function upsertDocument(string $collectionName, string $documentId, object|array|string $document): void;

    /**
     * @throws DocumentException
     */
    public function deleteDocument(string $collectionName, string $documentId): void;

    /**
     * @throws DocumentException
     */
    public function getDocument(string $collectionName, string $documentId): array|object|string;

    /**
     * @return object[]|string[]|array
     */
    public function getAllDocuments(string $collectionName): array;

    /**
     * @throws DocumentException
     */
    public function countDocuments(string $collectionName): int;
}