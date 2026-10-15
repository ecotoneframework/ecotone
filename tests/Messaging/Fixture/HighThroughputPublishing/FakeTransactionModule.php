<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\HighThroughputPublishing;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class FakeTransactionModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messagingConfiguration->registerServiceDefinition(
            FakeTransactionInterceptor::class,
            new Definition(FakeTransactionInterceptor::class, [new Reference(OperationsLog::class)])
        );
        $messagingConfiguration->registerAroundMethodInterceptor(
            AroundInterceptorBuilder::create(
                FakeTransactionInterceptor::class,
                $interfaceToCallRegistry->getFor(FakeTransactionInterceptor::class, 'transactional'),
                Precedence::DATABASE_TRANSACTION_PRECEDENCE,
                CommandBus::class . '||' . AsynchronousRunningEndpoint::class,
            )
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
