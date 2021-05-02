<?php

namespace Ecotone\Messaging\Store\Document;

class InMemoryDocumentStore implements DocumentStore
{
    public function dropCollection(string $collectionName): void
    {
        // TODO: Implement dropCollection() method.
    }

    public function addDocument(string $collectionName, string $documentId, object|array $document): void
    {
        // TODO: Implement addDocument() method.
    }

    public function replaceDocument(string $collectionName, string $documentId, object|array $fullOrSubsetDocument): void
    {
        // TODO: Implement replaceDocument() method.
    }

    public function upsertDocument(string $collectionName, string $documentId, object|array $fullOrSubsetDocument): void
    {
        // TODO: Implement upsertDocument() method.
    }

    public function deleteDocument(string $collectionName, string $documentId): void
    {
        // TODO: Implement deleteDocument() method.
    }

    public function getDocument(string $collectionName, string $documentId): array|object
    {
        // TODO: Implement getDocument() method.
    }

    public function countDocs(string $collectionName): int
    {
        // TODO: Implement countDocs() method.
    }
}