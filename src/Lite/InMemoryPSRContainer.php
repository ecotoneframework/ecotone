<?php


namespace Ecotone\Lite;

use Psr\Container\ContainerInterface;

/**
 * Class InMemoryPSRContainer
 * @package Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryPSRContainer implements ContainerInterface
{
    /**
     * @var array
     */
    private $objects;

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
        foreach ($objects as $object) {
            $map[get_class($object)] = $object;
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
        return $this->objects[$id];
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return array_key_exists($id, $this->objects);
    }
}