<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
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
     * @var ReferenceSearchService|null
     */
    private $referenceSearchService;

    /**
     * InMemoryReferenceSearchService constructor.
     * @param array|object[] $objectsToResolve
     * @param ReferenceSearchService|null $referenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function __construct(array $objectsToResolve, ?ReferenceSearchService $referenceSearchService)
    {
        $this->referenceSearchService = $referenceSearchService;

        $this->initialize($objectsToResolve);
    }

    /**
     * @param array|object[] $objects
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith(array $objects) : self
    {
        return new self($objects, null);
    }

    /**
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([], null);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $objects
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWithReferenceService(ReferenceSearchService $referenceSearchService, array $objects) : self
    {
        return new self($objects, $referenceSearchService);
    }

    /**
     * @param string $referenceName
     * @param $object
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function registerReferencedObject(string $referenceName, $object)
    {
        Assert::isObject($object, "Passed reference {$referenceName} must be object");

        $this->objectsToResolve[$referenceName] = $object;
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference)
    {
        foreach ($this->objectsToResolve as $referenceName => $object) {
            if ($referenceName == $reference) {
                return $object;
            }
        }

        if ($this->referenceSearchService) {
            return $this->referenceSearchService->get($reference);
        }

        throw ReferenceNotFoundException::create("Reference {$reference} was not found");
    }

    /**
     * @param array|object[] $objects
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(array $objects) : void
    {
        foreach ($objects as $object) {
            Assert::isObject($object, "Passed reference is not an object");
        }

        $this->objectsToResolve = $objects;

        if (!array_key_exists(InterfaceToCallRegistry::REFERENCE_NAME, $this->objectsToResolve)) {
            $this->objectsToResolve[InterfaceToCallRegistry::REFERENCE_NAME] = InterfaceToCallRegistry::createEmpty();
        }
        if (!array_key_exists(ConversionService::REFERENCE_NAME, $this->objectsToResolve)) {
            $this->objectsToResolve[ConversionService::REFERENCE_NAME] = AutoCollectionConversionService::createEmpty();
        }
    }
}