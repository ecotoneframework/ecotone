<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[ModuleAnnotation]
class AsynchronousModule extends NoExternalConfigurationModule implements AnnotationModule
{
    private function __construct(private array $asyncEndpoints)
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $asynchronousClasses = $annotationRegistrationService->findAnnotatedClasses(Asynchronous::class);

        $asynchronousMethods = $annotationRegistrationService->findAnnotatedMethods(Asynchronous::class);
        $endpoints           = array_merge(
            $annotationRegistrationService->findAnnotatedMethods(EndpointAnnotation::class),
            $annotationRegistrationService->findAnnotatedMethods(EventHandler::class)
        );

        $registeredAsyncEndpoints = [];
        foreach ($asynchronousClasses as $asynchronousClass) {
            /** @var Asynchronous $asyncClass */
            $asyncClass = AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationRegistrationService, $asynchronousClass, Asynchronous::class);
            foreach ($endpoints as $endpoint) {
                if ($asynchronousClass === $endpoint->getClassName()) {
                    /** @var EndpointAnnotation $annotationForMethod */
                    $annotationForMethod = $endpoint->getAnnotationForMethod();
                    if ($annotationForMethod instanceof QueryHandler) {
                        continue;
                    }
                    if (in_array(get_class($annotationForMethod), [CommandHandler::class, EventHandler::class])) {
                        if ($annotationForMethod->isEndpointIdGenerated()) {
                            throw ConfigurationException::create("{$endpoint} should have endpointId defined for handling asynchronously");
                        }
                    }

                    $registeredAsyncEndpoints[$annotationForMethod->getEndpointId()] = $asyncClass->getChannelName();
                }
            }
        }

        foreach ($asynchronousMethods as $asynchronousMethod) {
            /** @var Asynchronous $asyncAnnotation */
            $asyncAnnotation = $asynchronousMethod->getAnnotationForMethod();
            foreach ($endpoints as $key => $endpoint) {
                if (($endpoint->getClassName() === $asynchronousMethod->getClassName()) && ($endpoint->getMethodName() === $asynchronousMethod->getMethodName())) {
                    /** @var EndpointAnnotation $annotationForMethod */
                    $annotationForMethod = $endpoint->getAnnotationForMethod();
                    if ($annotationForMethod instanceof QueryHandler) {
                        continue;
                    }
                    if (in_array(get_class($annotationForMethod), [CommandHandler::class, EventHandler::class])) {
                        if ($annotationForMethod->isEndpointIdGenerated()) {
                            throw ConfigurationException::create("{$endpoint} should have endpointId defined for handling asynchronously");
                        }
                    }

                    $registeredAsyncEndpoints[$annotationForMethod->getEndpointId()] = $asyncAnnotation->getChannelName();
                }
            }
        }

        return new self($registeredAsyncEndpoints);
    }

    public function getSynchronousChannelFor(string $handlerChannelName, string $endpointIdToLookFor): ?string
    {
        if (array_key_exists($endpointIdToLookFor, $this->asyncEndpoints)) {
            return self::getHandlerExecutionChannel($handlerChannelName);
        }

        return $handlerChannelName;
    }

    public static function getHandlerExecutionChannel(string $originalInputChannelName): string
    {
        return $originalInputChannelName . '.execute';
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->asyncEndpoints as $endpointId => $asyncChannels) {
            $messagingConfiguration->registerAsynchronousEndpoint($asyncChannels, $endpointId);
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::ASYNCHRONOUS_PACKAGE;
    }
}
