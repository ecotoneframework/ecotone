<?php


namespace Ecotone\EventSourcing\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Projection
{
    private string $name;
    private array $fromStreams;

    public function __construct(string $name, string|array $fromStreams)
    {
        $this->name = $name;
        $this->fromStreams = is_string($fromStreams) ? [$fromStreams] : $fromStreams;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFromStreams(): array
    {
        return $this->fromStreams;
    }
}