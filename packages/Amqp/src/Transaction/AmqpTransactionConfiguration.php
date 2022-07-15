<?php

namespace Ecotone\Amqp\Transaction;

use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;
use Enqueue\AmqpExt\AmqpConnectionFactory;

#[ModuleAnnotation]
class AmqpTransactionConfiguration implements AnnotationModule
{
    private function __construct()
    {
    }

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
        $connectionFactories = [AmqpConnectionFactory::class];
        $pointcut = AmqpTransaction::class;
        $amqpConfiguration = AmqpConfiguration::createWithDefaults();
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof AmqpConfiguration) {
                $amqpConfiguration = $extensionObject;
            }
        }

        $isTransactionWrapperEnabled = false;
        if ($amqpConfiguration->isTransactionOnAsynchronousEndpoints()) {
            $pointcut .= '||' . AsynchronousRunningEndpoint::class;
            $isTransactionWrapperEnabled = true;
        }
        if ($amqpConfiguration->isTransactionOnCommandBus()) {
            $pointcut .= '||' . CommandBus::class . '';
            $isTransactionWrapperEnabled = true;
        }
        if ($amqpConfiguration->isTransactionOnConsoleCommands()) {
            $pointcut .= '||' . ConsoleCommand::class . '';
            $isTransactionWrapperEnabled = true;
        }
        if ($amqpConfiguration->getDefaultConnectionReferenceNames()) {
            $connectionFactories = $amqpConfiguration->getDefaultConnectionReferenceNames();
        }

        if ($isTransactionWrapperEnabled) {
            $configuration->requireReferences($connectionFactories);
        }

        $configuration
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                    new AmqpTransactionInterceptor($connectionFactories),
                    'transactional',
                    Precedence::DATABASE_TRANSACTION_PRECEDENCE - 1,
                    $pointcut
                )
            );
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof AmqpConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return AmqpModule::NAME;
    }
}
