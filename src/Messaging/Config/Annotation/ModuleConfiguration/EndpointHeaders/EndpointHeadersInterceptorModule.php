<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AddHeader;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\RemoveHeader;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class EndpointHeadersInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
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
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor(EndpointHeadersInterceptor::class, 'addMetadata');
        $messagingConfiguration->registerBeforeSendInterceptor(
            MethodInterceptorBuilder::create(
                new Definition(EndpointHeadersInterceptor::class),
                $interfaceToCall,
                Precedence::ENDPOINT_HEADERS_PRECEDENCE,
                AddHeader::class . '||' . RemoveHeader::class,
                true
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
