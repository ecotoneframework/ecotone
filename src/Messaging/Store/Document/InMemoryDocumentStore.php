<?php

namespace Ecotone\Messaging\Store\Document;

use function json_decode;

use JsonException;
use Throwable;

/**
 * licence Apache-2.0
 */
final class InMemoryDocumentStore implements DocumentStore
{
    /**
     * @var array<string, array|object|string>
     */
    private array $collection = [];

    private function __construct()
    {
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function dropCollection(string $collectionName): void
    {
        unset($this->collection[$collectionName]);
    }

    public function addDocument(string $collectionName, string $documentId, object|array|string $document): void
    {
        if (isset($this->collection[$collectionName][$documentId])) {
            throw DocumentException::create(sprintf('Collection %s already contains document with id %s', $collectionName, $documentId));
        }
        if (is_string($document)) {
            try {
                json_decode($document, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw DocumentException::create(sprintf('Trying to store document in %s collection with incorrect JSON: %s', $documentId, $collectionName));
            }
        }

        $this->collection[$collectionName][$documentId] = $document;
    }

    public function updateDocument(string $collectionName, string $documentId, object|array|string $document): void
    {
        if (! isset($this->collection[$collectionName][$documentId])) {
            throw DocumentNotFound::create(sprintf('Collection %s does not contains document with id %s', $collectionName, $documentId));
        }

        $this->collection[$collectionName][$documentId] = $document;
    }

    public function upsertDocument(string $collectionName, string|int $documentId, object|array|string $document): void
    {
        $this->collection[$collectionName][$documentId] = $document;
    }

    public function deleteDocument(string $collectionName, string $documentId): void
    {
        unset($this->collection[$collectionName][$documentId]);
    }

    public function getDocument(string $collectionName, string $documentId): array|object|string
    {
        $document = $this->findDocument($collectionName, $documentId);
        if (is_null($document)) {
            throw DocumentNotFound::create(sprintf('Collection %s does not have document with id %s', $collectionName, $documentId));
        }

        return $document;
    }

    public function findDocument(string $collectionName, string $documentId): array|object|string|null
    {
        if (! isset($this->collection[$collectionName][$documentId])) {
            return null;
        }

        $document = $this->collection[$collectionName][$documentId];

        if ($document instanceof Throwable) {
            throw $document;
        }

        return $document;
    }

    public function getAllDocuments(string $collectionName): array
    {
        if (! isset($this->collection[$collectionName])) {
            return [];
        }

        return array_values($this->collection[$collectionName]);
    }

    public function countDocuments(string $collectionName): int
    {
        if (! isset($this->collection[$collectionName])) {
            return 0;
        }

        return count($this->collection[$collectionName]);
    }
}
