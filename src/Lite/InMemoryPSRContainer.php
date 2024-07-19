<?php

namespace Ecotone\Lite;

use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class InMemoryPSRContainer
 * @package Ecotone\Lite
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InMemoryPSRContainer implements ContainerInterface
{
    private array $objects;

    private function __construct(array $objects)
    {
        $this->objects = $objects;
    }

    /**
     * @param object[] $objects
     * @return InMemoryPSRContainer
     */
    public static function createFromAssociativeArray(array $objects): self
    {
        $objectReferences = [];
        foreach ($objects as $referenceName => $object) {
            $objectReferences[is_int($referenceName) ? get_class($object) : $referenceName] = $object;
        }

        return new self($objectReferences);
    }

    /**
     * @param array $objects
     * @return InMemoryPSRContainer
     */
    public static function createFromObjects(array $objects): self
    {
        $map = [];
        foreach ($objects as $key => $object) {
            $map[is_numeric($key) ? get_class($object) : $key] = $object;
        }

        return new self($map);
    }

    /**
     * @return InMemoryPSRContainer
     */
    public static function createEmpty(): self
    {
        return self::createFromAssociativeArray([]);
    }

    public function addGateway(string $referenceName, object $gateway): void
    {
        $this->objects[$referenceName] = $gateway;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        if (! isset($this->objects[$id])) {
            throw ReferenceNotFoundException::create("Reference with id {$id} was not found");
        }

        return $this->objects[$id];
    }

    public function set(string $id, mixed $object): void
    {
        $this->objects[$id] = $object;
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->objects);
    }
}
