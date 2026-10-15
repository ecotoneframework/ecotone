<?php

namespace Ecotone\Messaging\Store\Document;

use function array_map;

use Ecotone\Api\Gateway\DocumentStore;
use Ecotone\Api\Messaging\ConcurrencyException;

use function json_decode;

use JsonException;

use function serialize;

use Throwable;

use function unserialize;

/**
 * licence Apache-2.0
 */
final class InMemoryDocumentStore implements DocumentStore
{
    /**
     * @var array<string, array|object|string>
     */
    private array $collection = [];

    /**
     * @var array<string, array<string, int>>
     */
    private array $versions = [];

    private function __construct()
    {
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function dropCollection(string $collectionName): void
    {
        unset($this->collection[$collectionName], $this->versions[$collectionName]);
    }

    public function addDocument(string $collectionName, string $documentId, object|array|string $document): void
    {
        if (isset($this->collection[$collectionName][$documentId])) {
            throw DocumentException::create(sprintf('Collection %s already contains document with id %s', $collectionName, $documentId));
        }

        $this->collection[$collectionName][$documentId] = $this->storedFormOf($collectionName, $documentId, $document);
        $this->versions[$collectionName][$documentId] = 1;
    }

    public function updateDocument(string $collectionName, string $documentId, object|array|string $document, int $expectedVersion): void
    {
        if (! isset($this->collection[$collectionName][$documentId])) {
            throw DocumentNotFound::create(sprintf('Collection %s does not contains document with id %s', $collectionName, $documentId));
        }

        $this->storeUnderVersion($collectionName, $documentId, $document, $expectedVersion);
    }

    public function upsertDocument(string $collectionName, string $documentId, object|array|string $document, int $expectedVersion): void
    {
        $this->storeUnderVersion($collectionName, $documentId, $document, $expectedVersion);
    }

    public function deleteDocument(string $collectionName, string $documentId): void
    {
        unset($this->collection[$collectionName][$documentId], $this->versions[$collectionName][$documentId]);
    }

    public function getDocumentVersion(string $collectionName, string $documentId): int
    {
        return $this->versions[$collectionName][$documentId] ?? 0;
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

        return $this->copied($document);
    }

    public function getAllDocuments(string $collectionName): array
    {
        if (! isset($this->collection[$collectionName])) {
            return [];
        }

        return array_map($this->copied(...), array_values($this->collection[$collectionName]));
    }

    public function countDocuments(string $collectionName): int
    {
        if (! isset($this->collection[$collectionName])) {
            return 0;
        }

        return count($this->collection[$collectionName]);
    }

    private function storeUnderVersion(string $collectionName, string $documentId, object|array|string $document, int $expectedVersion): void
    {
        $currentVersion = $this->getDocumentVersion($collectionName, $documentId);
        if ($expectedVersion !== self::LAST_WRITE_WINS && $expectedVersion !== $currentVersion) {
            throw ConcurrencyException::forStaleDocument($collectionName, $documentId, $expectedVersion, $currentVersion);
        }

        $this->collection[$collectionName][$documentId] = $this->storedFormOf($collectionName, $documentId, $document);
        $this->versions[$collectionName][$documentId] = $currentVersion + 1;
    }

    private function storedFormOf(string $collectionName, string $documentId, object|array|string $document): object|array|string
    {
        if (is_string($document)) {
            try {
                json_decode($document, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $notJson) {
                throw DocumentException::forStringThatIsNotJson($collectionName, $documentId, $notJson);
            }
        }

        return $this->copied($document);
    }

    private function copied(object|array|string $document): object|array|string
    {
        if (is_string($document) || $document instanceof Throwable) {
            return $document;
        }

        return unserialize(serialize($document));
    }
}
