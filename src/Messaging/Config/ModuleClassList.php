<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Amqp\Configuration\AmqpMessageConsumerModule;
use Ecotone\Amqp\Configuration\AmqpModule;
use Ecotone\Amqp\Publisher\AmqpMessagePublisherModule;
use Ecotone\Amqp\Transaction\AmqpTransactionModule;
use Ecotone\Dbal\Configuration\DbalPublisherModule;
use Ecotone\Dbal\DbaBusinessMethod\DbaBusinessMethodModule;
use Ecotone\Dbal\DbalTransaction\DbalTransactionModule;
use Ecotone\Dbal\Deduplication\DeduplicationModule;
use Ecotone\Dbal\DocumentStore\DbalDocumentStoreModule;
use Ecotone\Dbal\MultiTenant\Module\MultiTenantConnectionFactoryModule;
use Ecotone\Dbal\ObjectManager\ObjectManagerModule;
use Ecotone\Dbal\Recoverability\DbalDeadLetterModule;
use Ecotone\EventSourcing\Config\EventSourcingModule;
use Ecotone\JMSConverter\Configuration\JMSConverterConfigurationModule;
use Ecotone\JMSConverter\Configuration\JMSDefaultSerialization;
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
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessageConsumerModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands\MessagingCommandsModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor\MethodInterceptorModule;
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
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Modelling\Config\BusModule;
use Ecotone\Modelling\Config\BusRoutingModule;
use Ecotone\Modelling\Config\DistributedGatewayModule;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryModule;
use Ecotone\Modelling\Config\ModellingHandlerModule;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ecotone\OpenTelemetry\Configuration\OpenTelemetryModule;
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
        DistributedGatewayModule::class,
        ModellingHandlerModule::class,
        BusRoutingModule::class,
        BusModule::class,
        MethodInterceptorModule::class,
        MessagingCommandsModule::class,
        EndpointHeadersInterceptorModule::class,
        BasicMessagingModule::class,
        ConsoleCommandModule::class,
        ConverterModule::class,
        ErrorHandlerModule::class,
        GatewayModule::class,
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
        MessageConsumerModule::class,
        InstantRetryModule::class,
        DynamicMessageChannelModule::class,

        /** Attribute based configurations */
        LoggingGateway::class,
        LoggingService::class,
        MessageHeadersPropagatorInterceptor::class,
        MessageHandlerLogger::class,
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
    ];

    public const DBAL_MODULES = [
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
}
