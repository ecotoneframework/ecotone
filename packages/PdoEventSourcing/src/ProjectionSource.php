<?php

namespace Ecotone\EventSourcing;

final class ProjectionSource
{
    /**
     * @param string[] $streams
     * @param string[] $categories
     */
    private function __construct(private bool $forAllStreams, private array $streams, private array $categories) {}

    public static function forAllStreams(): self
    {
        return new self(true, [], []);
    }

    public function isForAllStreams(): bool
    {
        return $this->forAllStreams;
    }

    /**
     * @return string[]
     */
    public function getStreams(): array
    {
        return $this->streams;
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }
}