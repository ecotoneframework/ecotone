<?php


namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\Endpoint\ExpireAfter;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Precedence;

#[ModuleAnnotation]
class EndpointHeadersInterceptorConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor(EndpointHeadersInterceptor::class, "addMetadata");
        $configuration->registerBeforeSendInterceptor(
            MethodInterceptor::create(
                EndpointHeadersInterceptor::class,
                $interfaceToCall,
                TransformerBuilder::createWithDirectObject(new EndpointHeadersInterceptor(), "addMetadata"),
                Precedence::ENDPOINT_HEADERS_PRECEDENCE,
                ExpireAfter::class . "||" . Priority::class . "||" . Delayed::class . "||" . AddHeader::class . "||" . RemoveHeader::class
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