<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Amqp\Configuration\AmqpChannelManagerModule;
use Ecotone\Amqp\Configuration\AmqpMessageConsumerModule;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\Amqp\Configuration\RabbitConsumerModule;
use Ecotone\Amqp\Publisher\AmqpMessagePublisherModule;
use Ecotone\Amqp\Transaction\AmqpTransactionModule;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\DataProtection\Configuration\DataProtectionModule;
use Ecotone\Dbal\BatchForwarding\DbalBatchForwardingModule;
use Ecotone\Dbal\Configuration\DbalConnectionModule;
use Ecotone\Dbal\Configuration\DbalPublisherModule;
use Ecotone\Dbal\Database\DatabaseSetupModule;
use Ecotone\Dbal\DbalBusinessMethod\DbalBusinessMethodModule;
use Ecotone\Dbal\DbalTransaction\DbalTransactionModule;
use Ecotone\Dbal\Deduplication\DeduplicationModule;
use Ecotone\Dbal\DocumentStore\DbalDocumentStoreModule;
use Ecotone\Dbal\MultiTenant\Module\MultiTenantConnectionFactoryModule;
use Ecotone\Dbal\MultiTenant\Pulse\PulseTenancyModule;
use Ecotone\Dbal\ObjectManager\ObjectManagerModule;
use Ecotone\Dbal\Recoverability\DbalDeadLetterModule;
use Ecotone\Dbal\Tracing\DbalTracingModule;
use Ecotone\EventSourcing\Config\EventSourcingModule;
use Ecotone\EventSourcing\Config\EventSourcingProjectingModule;
use Ecotone\EventSourcing\Tagging\Config\EventTaggingModule;
use Ecotone\JMSConverter\Configuration\JMSConverterConfigurationModule;
use Ecotone\Kafka\Configuration\KafkaModule;
use Ecotone\Laravel\Config\LaravelConnectionModule;
use Ecotone\Lite\Test\Configuration\EcotoneTestSupportModule;
use Ecotone\Messaging\Channel\Collector\Config\CollectorModule;
use Ecotone\Messaging\Channel\DeliveryConfirmation\Config\DeliveryConfirmationModule;
use Ecotone\Messaging\Channel\DynamicChannel\Config\DynamicMessageChannelModule;
use Ecotone\Messaging\Channel\Manager\ChannelSetupModule;
use Ecotone\Messaging\Channel\PollableChannel\InMemory\InMemoryQueueAcknowledgeModule;
use Ecotone\Messaging\Channel\PollableChannel\SendRetries\PollableChannelSendRetriesModule;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\PollableChannelSerializationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\BasicMessagingModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ChannelInterceptor\ChannelInterceptorModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConverterModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders\EndpointHeadersInterceptorModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ErrorHandlerModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessageConsumerModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands\MessagingCommandsModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingGatewayModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor\MethodInterceptorModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\Orchestrator\OrchestratorModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\PollerModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\RequiredConsumersModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\RouterModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ScheduledModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\SerializerModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\SplitterModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use Ecotone\Messaging\Config\Flow\MessageFlowModule;
use Ecotone\Messaging\Handler\Logger\Config\LoggingModule;
use Ecotone\Messaging\Handler\Logger\Config\MessageHandlerLogger;
use Ecotone\Modelling\Config\AggregateModule;
use Ecotone\Modelling\Config\EventSourcedRepositoryModule;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryAttributeModule;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryModule;
use Ecotone\Modelling\Config\MessageHandlerRoutingModule;
use Ecotone\Modelling\Config\ServiceHandlerModule;
use Ecotone\Modelling\DecisionModel\Config\DecisionModelModule;
use Ecotone\Modelling\MessageHandling\Distribution\Module\DistributedBusWithServiceMapModule;
use Ecotone\Modelling\MessageHandling\Distribution\Module\DistributedHandlerModule;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseModule;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ecotone\OpenTelemetry\Configuration\OpenTelemetryModule;
use Ecotone\Projecting\Config\PartitionProviderRegistryModule;
use Ecotone\Projecting\Config\ProjectingAttributeModule;
use Ecotone\Projecting\Config\ProjectingConsoleCommands;
use Ecotone\Projecting\Config\ProjectingModule;
use Ecotone\Projecting\Config\ProjectionStateStorageRegistryModule;
use Ecotone\Projecting\Config\StreamFilterRegistryModule;
use Ecotone\Projecting\Config\StreamSourceRegistryModule;
use Ecotone\Projecting\EventStoreAdapter\EventStoreAdapterModule;
use Ecotone\Redis\Configuration\RedisMessageConsumerModule;
use Ecotone\Redis\Configuration\RedisMessagePublisherModule;
use Ecotone\Sqs\Configuration\SqsChannelManagerModule;
use Ecotone\Sqs\Configuration\SqsMessageConsumerModule;
use Ecotone\Sqs\Configuration\SqsMessagePublisherModule;
use Ecotone\Sqs\Configuration\SqsModule;
use Ecotone\SymfonyBundle\Config\SymfonyConnectionModule;
use Ecotone\Tempest\Config\TempestModule;
use Ecotone\Tracing\Config\TracingModule;

/**
 * licence Apache-2.0
 */
class ModuleClassList
{
    public const CORE_MODULES = [
        DistributedHandlerModule::class,
        DistributedBusWithServiceMapModule::class,
        PulseModule::class,
        AggregateModule::class,
        ServiceHandlerModule::class,
        EventTaggingModule::class,
        DecisionModelModule::class,
        MessageHandlerRoutingModule::class,
        MethodInterceptorModule::class,
        ChannelInterceptorModule::class,
        MessagingCommandsModule::class,
        EndpointHeadersInterceptorModule::class,
        BasicMessagingModule::class,
        ConsoleCommandModule::class,
        ConverterModule::class,
        ErrorHandlerModule::class,
        MessagingGatewayModule::class,
        LoggingModule::class,
        PollerModule::class,
        RequiredConsumersModule::class,
        RouterModule::class,
        ScheduledModule::class,
        CollectorModule::class,
        TracingModule::class,
        DeliveryConfirmationModule::class,
        ChannelSetupModule::class,
        SerializerModule::class,
        ServiceActivatorModule::class,
        SplitterModule::class,
        TransformerModule::class,
        OrchestratorModule::class,
        MessageConsumerModule::class,
        InstantRetryModule::class,
        InstantRetryAttributeModule::class,
        DynamicMessageChannelModule::class,
        EventSourcedRepositoryModule::class,
        PartitionProviderRegistryModule::class,
        ProjectingModule::class,
        ProjectingAttributeModule::class,
        ProjectionStateStorageRegistryModule::class,
        StreamFilterRegistryModule::class,
        StreamSourceRegistryModule::class,
        EventStoreAdapterModule::class,
        AsynchronousModule::class,
        PollableChannelSerializationModule::class,
        PollableChannelSendRetriesModule::class,
        InMemoryQueueAcknowledgeModule::class,
        MessageFlowModule::class,

        /** Attribute based configurations */
        MessageHeadersPropagatorInterceptor::class,
        MessageHandlerLogger::class,
        CommandBus::class,
        QueryBus::class,
        EventBus::class,
        ProjectingConsoleCommands::class,
    ];

    public const AMQP_MODULES = [
        AmqpTransactionModule::class,
        AmqpMessagePublisherModule::class,
        AmqpModule::class,
        AmqpChannelManagerModule::class,
        AmqpMessageConsumerModule::class,
        RabbitConsumerModule::class,
    ];

    public const DBAL_MODULES = [
        DbalBatchForwardingModule::class,
        DbalConnectionModule::class,
        DbalDeadLetterModule::class,
        ObjectManagerModule::class,
        DbalDocumentStoreModule::class,
        DeduplicationModule::class,
        DbalTransactionModule::class,
        DbalPublisherModule::class,
        DbalBusinessMethodModule::class,
        MultiTenantConnectionFactoryModule::class,
        PulseTenancyModule::class,
        DatabaseSetupModule::class,
        DbalTracingModule::class,
    ];

    public const REDIS_MODULES = [
        RedisMessageConsumerModule::class,
        RedisMessagePublisherModule::class,
    ];

    public const SQS_MODULES = [
        SqsModule::class,
        SqsChannelManagerModule::class,
        SqsMessageConsumerModule::class,
        SqsMessagePublisherModule::class,
    ];

    public const EVENT_SOURCING_MODULES = [
        EventSourcingModule::class,
        EventSourcingProjectingModule::class,
    ];

    public const JMS_CONVERTER_MODULES = [
        JMSConverterConfigurationModule::class,
    ];

    public const TRACING_MODULES = [
        OpenTelemetryModule::class,
    ];

    public const TEST_MODULES = [
        EcotoneTestSupportModule::class,
    ];

    public const LARAVEL_MODULES = [
        LaravelConnectionModule::class,
    ];

    public const SYMFONY_MODULES = [
        SymfonyConnectionModule::class,
    ];

    public const TEMPEST_MODULES = [
        TempestModule::class,
    ];

    public const KAFKA_MODULES = [
        KafkaModule::class,
    ];

    public const DATA_PROTECTION_MODULES = [
        DataProtectionModule::class,
    ];

    /**
     * @return string[]
     */
    public static function forPackage(string $packageName): array
    {
        return match ($packageName) {
            ModulePackageList::CORE_PACKAGE => self::CORE_MODULES,
            ModulePackageList::AMQP_PACKAGE => self::AMQP_MODULES,
            ModulePackageList::DBAL_PACKAGE => self::DBAL_MODULES,
            ModulePackageList::REDIS_PACKAGE => self::REDIS_MODULES,
            ModulePackageList::SQS_PACKAGE => self::SQS_MODULES,
            ModulePackageList::KAFKA_PACKAGE => self::KAFKA_MODULES,
            ModulePackageList::EVENT_SOURCING_PACKAGE => self::EVENT_SOURCING_MODULES,
            ModulePackageList::JMS_CONVERTER_PACKAGE => self::JMS_CONVERTER_MODULES,
            ModulePackageList::TRACING_PACKAGE => self::TRACING_MODULES,
            ModulePackageList::TEST_PACKAGE => self::TEST_MODULES,
            ModulePackageList::LARAVEL_PACKAGE => self::LARAVEL_MODULES,
            ModulePackageList::SYMFONY_PACKAGE => self::SYMFONY_MODULES,
            ModulePackageList::TEMPEST_PACKAGE => self::TEMPEST_MODULES,
            ModulePackageList::DATA_PROTECTION_PACKAGE => self::DATA_PROTECTION_MODULES,
            default => throw ConfigurationException::create(sprintf('Given unknown package name %s. Available packages name are: %s', $packageName, implode(',', self::packageNames())))
        };
    }

    /**
     * @return string[]
     */
    public static function packageNames(): array
    {
        return [
            ModulePackageList::CORE_PACKAGE,
            ModulePackageList::AMQP_PACKAGE,
            ModulePackageList::REDIS_PACKAGE,
            ModulePackageList::SQS_PACKAGE,
            ModulePackageList::KAFKA_PACKAGE,
            ModulePackageList::DBAL_PACKAGE,
            ModulePackageList::EVENT_SOURCING_PACKAGE,
            ModulePackageList::JMS_CONVERTER_PACKAGE,
            ModulePackageList::TRACING_PACKAGE,
            ModulePackageList::LARAVEL_PACKAGE,
            ModulePackageList::SYMFONY_PACKAGE,
            ModulePackageList::TEMPEST_PACKAGE,
            ModulePackageList::DATA_PROTECTION_PACKAGE,
            ModulePackageList::TEST_PACKAGE,
        ];
    }
}
