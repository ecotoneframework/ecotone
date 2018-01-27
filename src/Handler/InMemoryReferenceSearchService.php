<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class InMemoryReferenceSearchService
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryReferenceSearchService implements ReferenceSearchService
{
    /**
     * @var object[]
     */
    private $objectsToResolve;

    /**
     * InMemoryReferenceSearchService constructor.
     * @param array|object[] $objectsToResolve
     */
    private function __construct(array $objectsToResolve)
    {
        $this->objectsToResolve = $objectsToResolve;
    }

    /**
     * @param array|object[] $objects
     * @return InMemoryReferenceSearchService
     */
    public static function createWith(array $objects) : self
    {
        return new self($objects);
    }

    /**
     * @param string $referenceName
     * @param $object
     */
    public function registerReferencedObject(string $referenceName, $object)
    {
        Assert::isObject($object, "Passed reference {$referenceName} must be object");

        $this->objectsToResolve[$referenceName] = $object;
    }

    /**
     * @return InMemoryReferenceSearchService
     */
    public static function createEmpty() : self
    {
        return new self([]);
    }

    /**
     * @inheritDoc
     */
    public function findByReference(string $reference)
    {
        foreach ($this->objectsToResolve as $referenceName => $object) {
            if ($referenceName == $reference) {
                return $object;
            }
        }

        throw ReferenceNotFoundException::create("Reference {$reference} was not found");
    }

    /**
     * @param array|object[] $objects
     */
    private function initialize(array $objects) : void
    {
        foreach ($objects as $object) {
            Assert::isObject($object, "Passed reference is not an object");
        }

        $this->objectsToResolve = $objects;
    }
}