<?php


namespace Ecotone\Lite;

use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Class InMemoryPSRContainer
 * @package Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryPSRContainer implements ContainerInterface
{
    private array $objects;

    /**
     * InMemoryPSRContainer constructor.
     * @param array $objects
     */
    private function __construct(array $objects)
    {
        $this->objects = $objects;
    }

    /**
     * @param object[] $objects
     * @return InMemoryPSRContainer
     */
    public static function createFromAssociativeArray(array $objects) : self
    {
        return new self($objects);
    }

    /**
     * @param array $objects
     * @return InMemoryPSRContainer
     */
    public static function createFromObjects(array $objects) : self
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
    public static function createEmpty() : self
    {
        return self::createFromAssociativeArray([]);
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (!isset($this->objects[$id])) {
            throw ReferenceNotFoundException::create("Reference with id {$id} was not found");
        }

        return $this->objects[$id];
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->objects);
    }
}