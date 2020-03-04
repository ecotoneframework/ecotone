<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyConfiguration;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\Support\Assert;
use ProxyManager\Configuration;
use Psr\Log\NullLogger;

/**
 * Class InMemoryReferenceSearchService
 * @package Ecotone\Messaging\Handler
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
     * @param bool $withDefaults
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function __construct(array $objectsToResolve, ?ReferenceSearchService $referenceSearchService, bool $withDefaults)
    {
        if ($withDefaults) {
            if (!array_key_exists(ExpressionEvaluationService::REFERENCE, $objectsToResolve)) {
                $objectsToResolve[ExpressionEvaluationService::REFERENCE] = SymfonyExpressionEvaluationAdapter::create();
            }
            if (!array_key_exists(InterfaceToCallRegistry::REFERENCE_NAME, $objectsToResolve)) {
                $objectsToResolve[InterfaceToCallRegistry::REFERENCE_NAME] = InterfaceToCallRegistry::createEmpty();
            }
            if (!array_key_exists(ConversionService::REFERENCE_NAME, $objectsToResolve)) {
                $objectsToResolve[ConversionService::REFERENCE_NAME] = AutoCollectionConversionService::createEmpty();
            }
            if (!array_key_exists(ProxyFactory::REFERENCE_NAME, $objectsToResolve)) {
                $objectsToResolve[ProxyFactory::REFERENCE_NAME] = ProxyFactory::createNoCache();
            }
            if (!array_key_exists(LoggingHandlerBuilder::LOGGER_REFERENCE, $objectsToResolve)) {
                $objectsToResolve[LoggingHandlerBuilder::LOGGER_REFERENCE] = new NullLogger();
            }
        }
        $this->referenceSearchService = $referenceSearchService;

        $this->initialize($objectsToResolve);
    }

    /**
     * @param array|object[] $objects
     * @return InMemoryReferenceSearchService
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWith(array $objects) : self
    {
        return new self($objects, null, true);
    }

    /**
     * @return InMemoryReferenceSearchService
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createEmpty() : self
    {
        return new self([], null, true);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $objects
     * @return InMemoryReferenceSearchService
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWithReferenceService(ReferenceSearchService $referenceSearchService, array $objects) : self
    {
        return new self($objects, $referenceSearchService, true);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array $objects
     * @return InMemoryReferenceSearchService
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWithNoDefaultsReferenceService(ReferenceSearchService $referenceSearchService, array $objects) : self
    {
        return new self($objects, $referenceSearchService, false);
    }

    /**
     * @param string $referenceName
     * @param $object
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function registerReferencedObject(string $referenceName, $object)
    {
        Assert::isObject($object, "Passed reference {$referenceName} must be object");

        $this->objectsToResolve[$referenceName] = $object;
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference) : object
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

    public function has(string $referenceName): bool
    {
        foreach ($this->objectsToResolve as $referenceName => $object) {
            if ($referenceName == $referenceName) {
                return true;
            }
        }

        if ($this->referenceSearchService) {
            return $this->referenceSearchService->has($referenceName);
        }

        return false;
    }

    /**
     * @param array|object[] $objects
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function initialize(array $objects) : void
    {
        foreach ($objects as $object) {
            Assert::isObject($object, "Passed reference is not an object");
        }

        $this->objectsToResolve = $objects;
    }
}