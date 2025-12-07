<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Amqp\Configuration\AmqpMessageConsumerModule;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\Amqp\Configuration\RabbitConsumerModule;
use Ecotone\Amqp\Publisher\AmqpMessagePublisherModule;
use Ecotone\Amqp\Transaction\AmqpTransactionModule;
use Ecotone\Dbal\Configuration\DbalConnectionModule;
use Ecotone\Dbal\Configuration\DbalPublisherModule;
use Ecotone\Dbal\DbaBusinessMethod\DbaBusinessMethodModule;
use Ecotone\Dbal\DbalTransaction\DbalTransactionModule;
use Ecotone\Dbal\Deduplication\DeduplicationModule;
use Ecotone\Dbal\DocumentStore\DbalDocumentStoreModule;
use Ecotone\Dbal\MultiTenant\Module\MultiTenantConnectionFactoryModule;
use Ecotone\Dbal\ObjectManager\ObjectManagerModule;
use Ecotone\Dbal\Recoverability\DbalDeadLetterModule;
use Ecotone\EventSourcing\Config\EventSourcingModule;
use Ecotone\EventSourcing\Config\ProophProjectingModule;
use Ecotone\JMSConverter\Configuration\JMSConverterConfigurationModule;
use Ecotone\JMSConverter\Configuration\JMSDefaultSerialization;
use Ecotone\Kafka\Configuration\KafkaModule;
use Ecotone\Laravel\Config\LaravelConnectionModule;
use Ecotone\Lite\Test\Configuration\EcotoneTestSupportModule;
use Ecotone\Messaging\Channel\Collector\Config\CollectorModule;
use Ecotone\Messaging\Channel\DynamicChannel\Config\DynamicMessageChannelModule;
use Ecotone\Messaging\Channel\PollableChannel\InMemory\InMemoryQueueAcknowledgeModule;
use Ecotone\Messaging\Channel\PollableChannel\SendRetries\PollableChannelSendRetriesModule;
use Ecotone\Messaging\Channel\PollableChannel\Serialization\PollableChannelSerializationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\BasicMessagingModule;
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
use Ecotone\Messaging\Handler\Logger\Config\LoggingModule;
use Ecotone\Messaging\Handler\Logger\Config\MessageHandlerLogger;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\AggregrateModule;
use Ecotone\Modelling\Config\EventSourcedRepositoryModule;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryAttributeModule;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryModule;
use Ecotone\Modelling\Config\MessageHandlerRoutingModule;
use Ecotone\Modelling\Config\ServiceHandlerModule;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\MessageHandling\Distribution\Module\DistributedBusWithServiceMapModule;
use Ecotone\Modelling\MessageHandling\Distribution\Module\DistributedHandlerModule;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ecotone\Modelling\QueryBus;
use Ecotone\OpenTelemetry\Configuration\OpenTelemetryModule;
use Ecotone\Projecting\Config\ProjectingAttributeModule;
use Ecotone\Projecting\Config\ProjectingConsoleCommands;
use Ecotone\Projecting\Config\ProjectingModule;
use Ecotone\Projecting\EventStoreAdapter\EventStoreAdapterModule;
use Ecotone\Redis\Configuration\RedisMessageConsumerModule;
use Ecotone\Redis\Configuration\RedisMessagePublisherModule;
use Ecotone\Sqs\Configuration\SqsMessageConsumerModule;
use Ecotone\Sqs\Configuration\SqsMessagePublisherModule;
use Ecotone\SymfonyBundle\Config\SymfonyConnectionModule;

/**
 * licence Apache-2.0
 */
class ModuleClassList
{
    public const CORE_MODULES = [
        DistributedHandlerModule::class,
        DistributedBusWithServiceMapModule::class,
        AggregrateModule::class,
        ServiceHandlerModule::class,
        MessageHandlerRoutingModule::class,
        MethodInterceptorModule::class,
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
        ProjectingModule::class,
        ProjectingAttributeModule::class,
        EventStoreAdapterModule::class,

        /** Attribute based configurations */
        MessageHeadersPropagatorInterceptor::class,
        MessageHandlerLogger::class,
        CommandBus::class,
        QueryBus::class,
        EventBus::class,
        ProjectingConsoleCommands::class,
    ];

    public const ASYNCHRONOUS_MODULE = [
        AsynchronousModule::class,
        PollableChannelSerializationModule::class,
        PollableChannelSendRetriesModule::class,
        InMemoryQueueAcknowledgeModule::class,
    ];

    public const AMQP_MODULES = [
        AmqpTransactionModule::class,
        AmqpMessagePublisherModule::class,
        AmqpModule::class,
        AmqpMessageConsumerModule::class,
        RabbitConsumerModule::class,
    ];

    public const DBAL_MODULES = [
        DbalConnectionModule::class,
        DbalDeadLetterModule::class,
        ObjectManagerModule::class,
        DbalDocumentStoreModule::class,
        DeduplicationModule::class,
        DbalTransactionModule::class,
        DbalPublisherModule::class,
        DbaBusinessMethodModule::class,
        MultiTenantConnectionFactoryModule::class,
    ];

    public const REDIS_MODULES = [
        RedisMessageConsumerModule::class,
        RedisMessagePublisherModule::class,
    ];

    public const SQS_MODULES = [
        SqsMessageConsumerModule::class,
        SqsMessagePublisherModule::class,
    ];

    public const EVENT_SOURCING_MODULES = [
        EventSourcingModule::class,
        ProophProjectingModule::class,
    ];

    public const JMS_CONVERTER_MODULES = [
        JMSConverterConfigurationModule::class,
        JMSDefaultSerialization::class,
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

    public const KAFKA_MODULES = [
        KafkaModule::class,
    ];
}
