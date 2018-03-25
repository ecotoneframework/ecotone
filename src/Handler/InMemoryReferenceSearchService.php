<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class InMemoryReferenceSearchService
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function __construct(array $objectsToResolve, ?ReferenceSearchService $referenceSearchService)
    {
        $this->referenceSearchService = $referenceSearchService;

        $this->initialize($objectsToResolve);
    }

    /**
     * @param array|object[] $objects
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWith(array $objects) : self
    {
        return new self($objects, null);
    }

    /**
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([], null);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $objects
     * @return InMemoryReferenceSearchService
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public static function createWithReferenceService(ReferenceSearchService $referenceSearchService, array $objects) : self
    {
        return new self($objects, $referenceSearchService);
    }

    /**
     * @param string $referenceName
     * @param $object
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function registerReferencedObject(string $referenceName, $object)
    {
        Assert::isObject($object, "Passed reference {$referenceName} must be object");

        $this->objectsToResolve[$referenceName] = $object;
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

        if ($this->referenceSearchService) {
            return $this->referenceSearchService->findByReference($reference);
        }

        throw ReferenceNotFoundException::create("Reference {$reference} was not found");
    }

    /**
     * @param array|object[] $objects
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(array $objects) : void
    {
        foreach ($objects as $object) {
            Assert::isObject($object, "Passed reference is not an object");
        }

        $this->objectsToResolve = $objects;
    }
}