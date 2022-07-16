<?php

namespace Ecotone\Dbal\Recoverability;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\Configuration\DbalModule;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Enqueue\Dbal\DbalConnectionFactory;

#[ModuleAnnotation]
class DbalDeadLetterModule implements AnnotationModule
{
    public const HELP_COMMAND_NAME = 'ecotone:deadletter:help';
    public const LIST_COMMAND_NAME            = 'ecotone:deadletter:list';
    public const SHOW_COMMAND_NAME       = 'ecotone:deadletter:show';
    public const REPLAY_COMMAND_NAME     = 'ecotone:deadletter:replay';
    public const REPLAY_ALL_COMMAND_NAME = 'ecotone:deadletter:replayAll';
    public const DELETE_COMMAND_NAME     = 'ecotone:deadletter:delete';

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
        $isDeadLetterEnabled = false;
        $connectionFactory     = DbalConnectionFactory::class;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                if (! $extensionObject->isDeadLetterEnabled()) {
                    return;
                }

                $connectionFactory     = $extensionObject->getDeadLetterConnectionReference();
                $isDeadLetterEnabled = true;
            }
        }

        if (! $isDeadLetterEnabled) {
            return;
        }

        $this->registerOneTimeCommand('list', self::LIST_COMMAND_NAME, $configuration, $interfaceToCallRegistry);
        $this->registerOneTimeCommand('show', self::SHOW_COMMAND_NAME, $configuration, $interfaceToCallRegistry);
        $this->registerOneTimeCommand('reply', self::REPLAY_COMMAND_NAME, $configuration, $interfaceToCallRegistry);
        $this->registerOneTimeCommand('replyAll', self::REPLAY_ALL_COMMAND_NAME, $configuration, $interfaceToCallRegistry);
        $this->registerOneTimeCommand('delete', self::DELETE_COMMAND_NAME, $configuration, $interfaceToCallRegistry);
        $this->registerOneTimeCommand('help', self::HELP_COMMAND_NAME, $configuration, $interfaceToCallRegistry);

        $configuration
            ->registerMessageHandler(DbalDeadLetterBuilder::createStore($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createDelete($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createShow($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createList($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createReply($connectionFactory))
            ->registerMessageHandler(DbalDeadLetterBuilder::createReplyAll($connectionFactory))
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    'list',
                    DbalDeadLetterBuilder::LIST_CHANNEL
                )
                    ->withParameterConverters([
                        GatewayHeaderBuilder::create('limit', DbalDeadLetterBuilder::LIMIT_HEADER),
                        GatewayHeaderBuilder::create('offset', DbalDeadLetterBuilder::OFFSET_HEADER),
                    ])
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    'show',
                    DbalDeadLetterBuilder::SHOW_CHANNEL
                )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    'reply',
                    DbalDeadLetterBuilder::REPLAY_CHANNEL
                )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    'replyAll',
                    DbalDeadLetterBuilder::REPLAY_ALL_CHANNEL
                )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    DeadLetterGateway::class,
                    DeadLetterGateway::class,
                    'delete',
                    DbalDeadLetterBuilder::DELETE_CHANNEL
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof DbalConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    private function registerOneTimeCommand(string $methodName, string $commandName, Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        [$messageHandlerBuilder, $oneTimeCommandConfiguration] = ConsoleCommandModule::prepareConsoleCommandForDirectObject(
            new DbalDeadLetterConsoleCommand(),
            $methodName,
            $commandName,
            true,
            $interfaceToCallRegistry
        );
        $configuration
            ->registerMessageHandler($messageHandlerBuilder)
            ->registerConsoleCommand($oneTimeCommandConfiguration);
    }

    public function getModulePackageName(): string
    {
        return DbalModule::NAME;
    }
}
