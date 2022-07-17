<?php

namespace Ecotone\EventSourcing;

use Ecotone\Modelling\Event;
use Prooph\EventStore\Metadata\MetadataMatcher;

interface EventStore
{
    /**
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function create(string $streamName, array $streamEvents): void;
    /**
     * @param Event[]|object[]|array[] $streamEvents
     */
    public function appendTo(string $streamName, array $streamEvents): void;

    public function delete(string $streamName): void;

    public function hasStream(string $streamName): bool;

    /**
     * @return Event[]
     */
    public function load(
        string $streamName,
        int $fromNumber = 1,
        int $count = null,
        MetadataMatcher $metadataMatcher = null,
        bool $deserialize = true
    ): iterable;

    /**
     * @return Event[]
     */
    public function loadReverse(
        string $streamName,
        int $fromNumber = null,
        int $count = null,
        MetadataMatcher $metadataMatcher = null,
        bool $deserialize = true
    ): iterable;

    /**
     * @return string[]
     */
    public function fetchStreamNames(
        ?string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * @return string[]
     */
    public function fetchStreamNamesRegex(
        string $filter,
        ?MetadataMatcher $metadataMatcher,
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * @return string[]
     */
    public function fetchCategoryNames(?string $filter, int $limit = 20, int $offset = 0): array;

    /**
     * @return string[]
     */
    public function fetchCategoryNamesRegex(string $filter, int $limit = 20, int $offset = 0): array;
}
