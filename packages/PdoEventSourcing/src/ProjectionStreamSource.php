<?php

namespace Ecotone\EventSourcing;

final class ProjectionStreamSource
{
    /**
     * @param string[] $streams
     * @param string[] $categories
     */
    private function __construct(private bool $forAllStreams, private array $streams, private array $categories)
    {
    }

    /**
     * @param string[] $streamNames
     */
    public static function fromStreams(array $streamNames): self
    {
        return new self(false, $streamNames, []);
    }

    /**
     * @param string[] $categories
     */
    public static function fromCategories(array $categories): self
    {
        return new self(false, [], $categories);
    }

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
