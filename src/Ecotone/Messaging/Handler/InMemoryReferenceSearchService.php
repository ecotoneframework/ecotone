<?php

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyConfiguration;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Psr\Log\NullLogger;

/**
 * Class InMemoryReferenceSearchService
 * @package Ecotone\Messaging\Handler
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InMemoryReferenceSearchService implements ReferenceSearchService
{
    /**
     * @var object[]
     */
    private ?array $objectsToResolve = null;
    private ?ReferenceSearchService $referenceSearchService = null;

    /**
     * @param array|object[]              $objectsToResolve
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
            if (!array_key_exists(LoggingHandlerBuilder::LOGGER_REFERENCE, $objectsToResolve) && (!$referenceSearchService || !$referenceSearchService->has(LoggingHandlerBuilder::LOGGER_REFERENCE))) {
                $objectsToResolve[LoggingHandlerBuilder::LOGGER_REFERENCE] = new NullLogger();
            }
        }
        $this->referenceSearchService = $referenceSearchService;

        $this->initialize($objectsToResolve);
    }

    /**
     * @param array|object[] $objects
     *
     * @return InMemoryReferenceSearchService
     * @throws MessagingException
     */
    public static function createWith(array $objects): self
    {
        return new self($objects, null, true);
    }

    /**
     * @return InMemoryReferenceSearchService
     * @throws MessagingException
     */
    public static function createEmpty(): self
    {
        return new self([], null, true);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param array                  $objects
     *
     * @return InMemoryReferenceSearchService
     * @throws MessagingException
     */
    public static function createWithReferenceService(ReferenceSearchService $referenceSearchService, array $objects): self
    {
        return new self($objects, $referenceSearchService, true);
    }

    /**
     * @param string $referenceName
     * @param        $object
     *
     * @throws MessagingException
     */
    public function registerReferencedObject(string $referenceName, $object): void
    {
        Assert::isObject($object, "Passed reference {$referenceName} must be object");

        $this->objectsToResolve[$referenceName] = $object;
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference): object
    {
        if (array_key_exists($reference, $this->objectsToResolve)) {
            return $this->objectsToResolve[$reference];
        }

        if ($this->referenceSearchService) {
            return $this->referenceSearchService->get($reference);
        }

        throw ReferenceNotFoundException::create("Reference {$reference} was not found");
    }

    public function has(string $referenceName): bool
    {
        if (array_key_exists($referenceName, $this->objectsToResolve)) {
            return true;
        }

        if ($this->referenceSearchService) {
            return $this->referenceSearchService->has($referenceName);
        }

        return false;
    }

    /**
     * @param array|object[] $objects
     *
     * @throws MessagingException
     */
    private function initialize(array $objects): void
    {
        foreach ($objects as $object) {
            Assert::isObject($object, "Passed reference is not an object");
        }

        $this->objectsToResolve = $objects;
    }
}