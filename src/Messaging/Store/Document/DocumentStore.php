<?php

namespace Ecotone\Messaging\Store\Document;

interface DocumentStore
{
    /**
     * @param string $collectionName
     */
    public function dropCollection(string $collectionName): void;

    /**
     * @throws UnknownCollectionException
     */
    public function addDocument(string $collectionName, string $documentId, object|array $document): void;

    /**
     * @throws UnknownCollectionException
     */
    public function replaceDocument(string $collectionName, string $documentId, object|array $fullOrSubsetDocument): void;

    /**
     * Same as replaceDoc except that doc is added to collection if it does not exist.
     *
     * @throws UnknownCollectionException
     */
    public function upsertDocument(string $collectionName, string $documentId, object|array $fullOrSubsetDocument): void;

    /**
     * @throws UnknownCollectionException
     */
    public function deleteDocument(string $collectionName, string $documentId): void;

    /**
     * @throws UnknownCollectionException
     */
    public function getDocument(string $collectionName, string $documentId): array|object;

    /**
     * @throws UnknownCollectionException
     */
    public function countDocs(string $collectionName): int;
}