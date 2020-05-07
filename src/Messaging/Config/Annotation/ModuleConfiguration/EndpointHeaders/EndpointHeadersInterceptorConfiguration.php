<?php


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\Messaging\Annotation\Endpoint\WithDelay;
use Ecotone\Messaging\Annotation\Endpoint\WithPriority;
use Ecotone\Messaging\Annotation\Endpoint\WithTimeToLive;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Precedence;

/**
 * @ModuleAnnotation()
 */
class EndpointHeadersInterceptorConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "endpointHeadersModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $interfaceToCall = InterfaceToCall::create(EndpointHeadersInterceptor::class, "addMetadata");
        $configuration->registerBeforeSendInterceptor(
            MethodInterceptor::create(
                EndpointHeadersInterceptor::class,
                $interfaceToCall,
                ServiceActivatorBuilder::createWithDirectReference(new EndpointHeadersInterceptor(), "addMetadata"),
                Precedence::ENDPOINT_HEADERS_PRECEDENCE,
                "@(" . WithTimeToLive::class . ")||(@" . WithPriority::class . ")||@(" . WithDelay::class . ")"
            )
        );
        $configuration->registerRelatedInterfaces([$interfaceToCall]);
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }
}