<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Lite\Test\MessagingTestSupport;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class EcotoneTestSupportModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const RECORD_COMMAND = 'recordCommand';
    public const RECORD_EVENT = 'recordEvent';
    public const RECORD_QUERY = 'recordQuery';
    public const GET_RECORDED_EVENT_MESSAGES = 'getRecordedEventMessages';
    public const GET_RECORDED_EVENTS = 'getRecordedEvents';
    public const GET_RECORDED_COMMANDS = 'getRecordedCommands';
    public const GET_RECORDED_COMMAND_MESSAGES = 'getRecordedCommandMessages';
    public const GET_RECORDED_QUERIES = 'getRecordedQueries';
    public const GET_RECORDED_QUERY_MESSAGES = 'getRecordedQueryMessages';
    public const DISCARD_MESSAGES = 'discardRecordedMessages';
    public const RELEASE_DELAYED_MESSAGES = 'releaseMessagesAwaitingFor';
    public const GET_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS = 'getRecordedMessagePayloadsFrom';
    public const GET_SPIED_CHANNEL_RECORDED_MESSAGES = 'getRecordedEcotoneMessagesFrom';

    private function __construct(private array $spiedChannels)
    {

    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $spiedChannels = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(CommandHandler::class) as $messageHandler) {
            /** @var CommandHandler $attribute */
            $attribute = $messageHandler->getAnnotationForMethod();

            if ($attribute->getOutputChannelName()) {
                $spiedChannels[] = $attribute->getOutputChannelName();
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(EventHandler::class) as $messageHandler) {
            /** @var EventHandler $attribute */
            $attribute = $messageHandler->getAnnotationForMethod();

            if ($attribute->getOutputChannelName()) {
                $spiedChannels[] = $attribute->getOutputChannelName();
            }
        }
        foreach ($annotationRegistrationService->findAnnotatedMethods(InternalHandler::class) as $messageHandler) {
            /** @var InternalHandler $attribute */
            $attribute = $messageHandler->getAnnotationForMethod();

            if ($attribute->getOutputChannelName()) {
                $spiedChannels[] = $attribute->getOutputChannelName();
            }
        }

        return new self($spiedChannels);
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $testConfiguration = ExtensionObjectResolver::resolveUnique(TestConfiguration::class, $extensionObjects, TestConfiguration::createWithDefaults());

        $messagingConfiguration->registerServiceDefinition(MessageCollectorHandler::class, new Definition(MessageCollectorHandler::class));
        $this->registerMessageCollector($messagingConfiguration, $interfaceToCallRegistry);
        $this->registerMessageReleasingHandler($messagingConfiguration);

        $messagingConfiguration->registerServiceDefinition(AllowMissingDestination::class);
        $allowMissingDestinationInterfaceToCall = $interfaceToCallRegistry->getFor(AllowMissingDestination::class, 'invoke');
        if (! $testConfiguration->isFailingOnCommandHandlerNotFound()) {
            $messagingConfiguration
                ->registerAroundMethodInterceptor(AroundInterceptorBuilder::create(
                    AllowMissingDestination::class,
                    $allowMissingDestinationInterfaceToCall,
                    Precedence::DEFAULT_PRECEDENCE,
                    CommandBus::class
                ));
        }
        if (! $testConfiguration->isFailingOnQueryHandlerNotFound()) {
            $messagingConfiguration
                ->registerAroundMethodInterceptor(AroundInterceptorBuilder::create(
                    AllowMissingDestination::class,
                    $allowMissingDestinationInterfaceToCall,
                    Precedence::DEFAULT_PRECEDENCE,
                    QueryBus::class
                ));
        }

        if ($testConfiguration->getPollableChannelMediaTypeConversion()) {
            $messagingConfiguration
                ->registerChannelInterceptor(new SerializationChannelAdapterBuilder($testConfiguration->getChannelToConvertOn(), $testConfiguration->getPollableChannelMediaTypeConversion()));
        }

        foreach ($this->spiedChannels as $spiedChannel) {
            $messagingConfiguration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($spiedChannel));
        }
        $spiedChannels = array_unique(array_merge($testConfiguration->getSpiedChannels(), $this->spiedChannels));
        foreach ($spiedChannels as $spiedChannel) {
            $messagingConfiguration
                ->registerChannelInterceptor(new SpiedChannelAdapterBuilder($spiedChannel));
        }

        if ($spiedChannels) {
            $messagingConfiguration
                ->registerMessageHandler(ServiceActivatorBuilder::create(
                    MessageCollectorHandler::class,
                    self::GET_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS
                )
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                    ])
                    ->withInputChannelName(self::inputChannelName(self::GET_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS)))
                ->registerMessageHandler(ServiceActivatorBuilder::create(
                    MessageCollectorHandler::class,
                    self::GET_SPIED_CHANNEL_RECORDED_MESSAGES
                )
                    ->withMethodParameterConverters([
                        HeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                    ])
                    ->withInputChannelName(self::inputChannelName(self::GET_SPIED_CHANNEL_RECORDED_MESSAGES)))
                ->registerGatewayBuilder(GatewayProxyBuilder::create(
                    MessagingTestSupport::class,
                    MessagingTestSupport::class,
                    self::GET_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS,
                    self::inputChannelName(self::GET_SPIED_CHANNEL_RECORDED_MESSAGE_PAYLOADS)
                )->withParameterConverters([
                    GatewayHeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                ]))
                ->registerGatewayBuilder(GatewayProxyBuilder::create(
                    MessagingTestSupport::class,
                    MessagingTestSupport::class,
                    self::GET_SPIED_CHANNEL_RECORDED_MESSAGES,
                    self::inputChannelName(self::GET_SPIED_CHANNEL_RECORDED_MESSAGES)
                )->withParameterConverters([
                    GatewayHeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                ]));
        }
    }

    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof TestConfiguration
            || ($extensionObject instanceof MessageChannelBuilder && $extensionObject->isPollable())
            || $extensionObject instanceof ServiceConfiguration;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::TEST_PACKAGE;
    }

    private static function inputChannelName(string $methodName): string
    {
        return 'test_support.message_collector.' .$methodName;
    }

    private function registerMessageReleasingHandler(Configuration $configuration): void
    {
        $configuration->registerServiceDefinition(DelayedMessageReleaseHandler::class, new Definition(DelayedMessageReleaseHandler::class));
        $configuration
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                DelayedMessageReleaseHandler::class,
                self::RELEASE_DELAYED_MESSAGES
            )
                ->withMethodParameterConverters([
                    HeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                    PayloadBuilder::create('timeInMillisecondsOrDateTime'),
                    ReferenceBuilder::create('channelResolver', ChannelResolver::class),
                ])
                ->withInputChannelName(self::inputChannelName(self::RELEASE_DELAYED_MESSAGES)))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::RELEASE_DELAYED_MESSAGES,
                self::inputChannelName(self::RELEASE_DELAYED_MESSAGES)
            )->withParameterConverters([
                GatewayHeaderBuilder::create('channelName', 'ecotone.test_support_gateway.channel_name'),
                GatewayPayloadBuilder::create('timeInMillisecondsOrDateTime'),
            ]));
    }

    private function registerMessageCollector(Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $configuration
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::RECORD_EVENT
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_EVENT)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::RECORD_COMMAND
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_COMMAND)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::RECORD_QUERY
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_QUERY)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::GET_RECORDED_EVENTS
            )
                ->withInputChannelName(self::inputChannelName(self::GET_RECORDED_EVENTS)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::GET_RECORDED_EVENT_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_RECORDED_EVENT_MESSAGES)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::GET_RECORDED_COMMANDS
            )
                ->withInputChannelName(self::inputChannelName(self::GET_RECORDED_COMMANDS)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::GET_RECORDED_COMMAND_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_RECORDED_COMMAND_MESSAGES)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::GET_RECORDED_QUERIES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_RECORDED_QUERIES)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::GET_RECORDED_QUERY_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_RECORDED_QUERY_MESSAGES)))
            ->registerMessageHandler(ServiceActivatorBuilder::create(
                MessageCollectorHandler::class,
                self::DISCARD_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::DISCARD_MESSAGES)))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::GET_RECORDED_EVENTS,
                self::inputChannelName(self::GET_RECORDED_EVENTS)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::GET_RECORDED_EVENT_MESSAGES,
                self::inputChannelName(self::GET_RECORDED_EVENT_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::GET_RECORDED_COMMANDS,
                self::inputChannelName(self::GET_RECORDED_COMMANDS)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::GET_RECORDED_COMMAND_MESSAGES,
                self::inputChannelName(self::GET_RECORDED_COMMAND_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::GET_RECORDED_QUERIES,
                self::inputChannelName(self::GET_RECORDED_QUERIES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::GET_RECORDED_QUERY_MESSAGES,
                self::inputChannelName(self::GET_RECORDED_QUERY_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                MessagingTestSupport::class,
                MessagingTestSupport::class,
                self::DISCARD_MESSAGES,
                self::inputChannelName(self::DISCARD_MESSAGES)
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to(MessageCollectorHandler::class),
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_EVENT),
                Precedence::DEFAULT_PRECEDENCE,
                EventBus::class,
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to(MessageCollectorHandler::class),
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_COMMAND),
                Precedence::DEFAULT_PRECEDENCE,
                CommandBus::class
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to(MessageCollectorHandler::class),
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_QUERY),
                Precedence::DEFAULT_PRECEDENCE,
                QueryBus::class
            ));
    }
}
