<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;
use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\Converter;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\MediaTypeConverter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Class ConverterModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AsyncModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var array
     */
    private $asyncEndpoints = [];

    /**
     * ConverterModule constructor.
     * @param array $asyncEndpoints
     */
    private function __construct(array $asyncEndpoints)
    {
        $this->asyncEndpoints = $asyncEndpoints;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService) : self
    {
        $asynchronousClass = $annotationRegistrationService->getAllClassesWithAnnotation(Async::class);

        $asynchronousMethods = $annotationRegistrationService->findRegistrationsFor(
            MessageEndpoint::class,
            Async::class
        );
        $endpoints = $annotationRegistrationService->findRegistrationsFor(
            MessageEndpoint::class,
            EndpointAnnotation::class
        );

        $registeredAsyncEndpoints = [];
        foreach ($asynchronousMethods as $asynchronousMethod) {
            $asyncAnnotation = $asynchronousMethod->getAnnotationForMethod();
            $inputChannel = $asyncAnnotation->channelName;
            foreach ($endpoints as $key => $endpoint) {
                if ($endpoint->getClassName() === $asynchronousMethod->getClassName() && $endpoint->getMethodName() === $asynchronousMethod->getMethodName()) {
                    /** @var EndpointAnnotation $annotationForMethod */
                    $annotationForMethod = $endpoint->getAnnotationForMethod();
                    $registeredAsyncEndpoints[$inputChannel][] = $annotationForMethod->endpointId;
                    unset($endpoints[$key]);
                }
            }
        }
        $endpoints = array_values($endpoints);

        foreach ($asynchronousClass as $asynchronousClass) {
            /** @var Async $asyncClass */
            $asyncClass = $annotationRegistrationService->getAnnotationForClass($asynchronousClass, Async::class);
            foreach ($endpoints as $endpoint) {
                if ($asynchronousClass === $endpoint->getClassName()) {
                    /** @var EndpointAnnotation $annotationForMethod */
                    $annotationForMethod = $endpoint->getAnnotationForMethod();
                    $registeredAsyncEndpoints[$asyncClass->channelName][] = $annotationForMethod->endpointId;
                }
            }
        }

        return new self($registeredAsyncEndpoints);
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
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->asyncEndpoints as $channelName => $asyncEndpointsForChannel) {
            foreach ($asyncEndpointsForChannel as $asyncEndpointForChannel) {
                $configuration->registerAsynchronousEndpoint($channelName, $asyncEndpointForChannel);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "converterModule";
    }
}