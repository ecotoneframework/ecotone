<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogBefore;
use Ecotone\Messaging\Handler\Logger\Annotation\LogError;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Precedence;

#[ModuleAnnotation]
class LoggingModule extends NoExternalConfigurationModule implements AnnotationModule
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
        $messagingConfiguration->registerBeforeMethodInterceptor(
            MethodInterceptor::create(
                'beforeLog',
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logBefore'),
                LoggingHandlerBuilder::createForBefore(),
                Precedence::ERROR_CHANNEL_PRECEDENCE - 1,
                LogBefore::class . '||' . AsynchronousRunningEndpoint::class
            )
        );
        $messagingConfiguration->registerAfterMethodInterceptor(
            MethodInterceptor::create(
                'afterLog',
                $interfaceToCallRegistry->getFor(LoggingInterceptor::class, 'logAfter'),
                LoggingHandlerBuilder::createForAfter(),
                Precedence::ERROR_CHANNEL_PRECEDENCE - 1,
                LogAfter::class
            )
        );
        $messagingConfiguration->registerAroundMethodInterceptor(
            AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                $interfaceToCallRegistry,
                new LoggingInterceptor(null),
                'logException',
                Precedence::ERROR_CHANNEL_PRECEDENCE - 1,
                LogError::class . '||' . AsynchronousRunningEndpoint::class
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
