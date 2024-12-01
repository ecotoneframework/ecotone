<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Precedence;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class EndpointHeadersInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
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
                new Definition(EndpointHeadersInterceptor::class, [Reference::to(ExpressionEvaluationService::REFERENCE)]),
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
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
