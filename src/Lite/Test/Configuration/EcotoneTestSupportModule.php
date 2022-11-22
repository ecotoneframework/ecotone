<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Lite\Test\TestSupportGateway;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Precedence;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

#[ModuleAnnotation]
final class EcotoneTestSupportModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const RECORD_COMMAND = 'recordCommand';
    public const RECORD_EVENT = 'recordEvent';
    public const RECORD_QUERY = 'recordQuery';
    public const GET_PUBLISHED_EVENT_MESSAGES = 'getPublishedEventMessages';
    public const GET_PUBLISHED_EVENTS = 'getPublishedEvents';
    public const GET_SENT_COMMANDS = 'getSentCommands';
    public const GET_SENT_COMMAND_MESSAGES = 'getSentCommandMessages';
    public const GET_SENT_QUERIES = 'getSentQueries';
    public const GET_SENT_QUERY_MESSAGES = 'getSentQueryMessages';
    public const RESET_MESSAGES = 'resetMessages';
    public const RELEASE_DELAYED_MESSAGES = 'releaseMessagesAwaitingFor';

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $testConfiguration = ExtensionObjectResolver::resolveUnique(TestConfiguration::class, $extensionObjects, TestConfiguration::createWithDefaults());

        $this->registerMessageCollector($configuration, $interfaceToCallRegistry);
        $this->registerMessageReleasingHandler($configuration);

        $allowMissingDestination = new AllowMissingDestination();
        if (! $testConfiguration->isFailingOnCommandHandlerNotFound()) {
            $configuration
                ->registerAroundMethodInterceptor(AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                    $interfaceToCallRegistry,
                    $allowMissingDestination,
                    'invoke',
                    Precedence::DEFAULT_PRECEDENCE,
                    CommandBus::class
                ));
        }
        if (! $testConfiguration->isFailingOnQueryHandlerNotFound()) {
            $configuration
                ->registerAroundMethodInterceptor(AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                    $interfaceToCallRegistry,
                    $allowMissingDestination,
                    'invoke',
                    Precedence::DEFAULT_PRECEDENCE,
                    QueryBus::class
                ));
        }

        if ($testConfiguration->getPollableChannelMediaTypeConversion()) {
            $configuration
                ->registerChannelInterceptor(new SerializationChannelAdapterBuilder($testConfiguration->getChannelToConvertOn(), $testConfiguration->getPollableChannelMediaTypeConversion()));
        }
    }

    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof TestConfiguration;
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
        $configuration
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                new DelayedMessageReleaseHandler(),
                self::RELEASE_DELAYED_MESSAGES
            )
                ->withMethodParameterConverters([
                    HeaderBuilder::create("channelName", "ecotone.test_support_gateway.channel_name"),
                    PayloadBuilder::create("timeInMilliseconds"),
                    ReferenceBuilder::create("channelResolver", ChannelResolver::class)
                ])
                ->withInputChannelName(self::inputChannelName(self::RELEASE_DELAYED_MESSAGES)))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::RELEASE_DELAYED_MESSAGES,
                self::inputChannelName(self::RELEASE_DELAYED_MESSAGES)
            )->withParameterConverters([
                GatewayHeaderBuilder::create("channelName", "ecotone.test_support_gateway.channel_name"),
                GatewayPayloadBuilder::create("timeInMilliseconds")
            ]));
    }

    private function registerMessageCollector(Configuration $configuration, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messageCollectorHandler = new MessageCollectorHandler();

        $configuration
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::RECORD_EVENT
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_EVENT)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::RECORD_COMMAND
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_COMMAND)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::RECORD_QUERY
            )
                ->withInputChannelName(self::inputChannelName(self::RECORD_QUERY)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::GET_PUBLISHED_EVENTS
            )
                ->withInputChannelName(self::inputChannelName(self::GET_PUBLISHED_EVENTS)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::GET_PUBLISHED_EVENT_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_PUBLISHED_EVENT_MESSAGES)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::GET_SENT_COMMANDS
            )
                ->withInputChannelName(self::inputChannelName(self::GET_SENT_COMMANDS)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::GET_SENT_COMMAND_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_SENT_COMMAND_MESSAGES)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::GET_SENT_QUERIES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_SENT_QUERIES)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::GET_SENT_QUERY_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::GET_SENT_QUERY_MESSAGES)))
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(
                $messageCollectorHandler,
                self::RESET_MESSAGES
            )
                ->withInputChannelName(self::inputChannelName(self::RESET_MESSAGES)))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::GET_PUBLISHED_EVENTS,
                self::inputChannelName(self::GET_PUBLISHED_EVENTS)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::GET_PUBLISHED_EVENT_MESSAGES,
                self::inputChannelName(self::GET_PUBLISHED_EVENT_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::GET_SENT_COMMANDS,
                self::inputChannelName(self::GET_SENT_COMMANDS)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::GET_SENT_COMMAND_MESSAGES,
                self::inputChannelName(self::GET_SENT_COMMAND_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::GET_SENT_QUERIES,
                self::inputChannelName(self::GET_SENT_QUERIES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::GET_SENT_QUERY_MESSAGES,
                self::inputChannelName(self::GET_SENT_QUERY_MESSAGES)
            ))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(
                TestSupportGateway::class,
                TestSupportGateway::class,
                self::RESET_MESSAGES,
                self::inputChannelName(self::RESET_MESSAGES)
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptor::create(
                MessageCollectorHandler::class . self::RECORD_EVENT,
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_EVENT),
                ServiceActivatorBuilder::createWithDirectReference($messageCollectorHandler, self::RECORD_EVENT),
                Precedence::DEFAULT_PRECEDENCE,
                EventBus::class
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptor::create(
                MessageCollectorHandler::class . self::RECORD_COMMAND,
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_COMMAND),
                ServiceActivatorBuilder::createWithDirectReference($messageCollectorHandler, self::RECORD_COMMAND),
                Precedence::DEFAULT_PRECEDENCE,
                CommandBus::class
            ))
            ->registerBeforeMethodInterceptor(MethodInterceptor::create(
                MessageCollectorHandler::class . self::RECORD_QUERY,
                $interfaceToCallRegistry->getFor(MessageCollectorHandler::class, self::RECORD_QUERY),
                ServiceActivatorBuilder::createWithDirectReference($messageCollectorHandler, self::RECORD_QUERY),
                Precedence::DEFAULT_PRECEDENCE,
                QueryBus::class
            ));
    }
}
