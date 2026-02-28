<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Exception;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Behat\Booking\Booking;
use Test\Ecotone\Messaging\Fixture\Behat\Booking\BookingService;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\Calculator;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\InboundCalculation;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\ResultService;
use Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\ErrorReceiver;
use Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\OrderGateway;
use Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\OrderService;
use Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\CalculateGatewayExample;
use Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\InterceptorExample;
use Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\SomeQueryHandler;
use Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\CalculateGatewayExampleWithMessages;
use Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled\InterceptedScheduledExample;
use Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled\InterceptedScheduledGateway;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\Order;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderingService;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderProcessor;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\CoinGateway;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\MultiplyCoins;
use Test\Ecotone\Messaging\Fixture\Behat\Presend\Shop;
use Test\Ecotone\Messaging\Fixture\Behat\Shopping\BookWasReserved;
use Test\Ecotone\Messaging\Fixture\Behat\Shopping\ShoppingService;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class MessagingBasicsTest extends TestCase
{
    public function test_booking_service_activator_and_gateway(): void
    {
        $messagingSystem = EcotoneLite::bootstrap(
            [],
            InMemoryPSRContainer::createFromObjects([
                Booking::class => new Booking(),
            ]),
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withEnvironment('prod')
                ->withFailFast(false)
                ->withNamespaces(['Test\Ecotone\Messaging\Fixture\Behat\Booking']),
            pathToRootCatalog: __DIR__ . '/../../..'
        );

        $gateway = $messagingSystem->getGatewayByName(BookingService::class);
        $gateway->bookFlat('3');
        $this->assertTrue($gateway->checkIfIsBooked('3'));
    }

    public function test_shopping_gateway_with_transformer(): void
    {
        $messagingSystem = EcotoneLite::bootstrap(
            [],
            InMemoryPSRContainer::createFromObjects([
                \Test\Ecotone\Messaging\Fixture\Behat\Shopping\Bookshop::class => new \Test\Ecotone\Messaging\Fixture\Behat\Shopping\Bookshop(),
                \Test\Ecotone\Messaging\Fixture\Behat\Shopping\ToReservationRequestTransformer::class => new \Test\Ecotone\Messaging\Fixture\Behat\Shopping\ToReservationRequestTransformer(),
            ]),
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withEnvironment('prod')
                ->withFailFast(false)
                ->withNamespaces(['Test\Ecotone\Messaging\Fixture\Behat\Shopping']),
            pathToRootCatalog: __DIR__ . '/../../..'
        );

        $gateway = $messagingSystem->getGatewayByName(ShoppingService::class);
        $bookWasReserved = $gateway->reserve('Harry Potter');
        $this->assertInstanceOf(BookWasReserved::class, $bookWasReserved);
    }

    public function test_ordering_synchronous(): void
    {
        $messagingSystem = $this->buildOrderingSystem(isAsync: false, listenChannel: 'syncChannel');

        $gateway = $messagingSystem->getGatewayByName(OrderingService::class);
        $future = $gateway->processOrder(Order::create('3', 'correct'));
        $this->assertInstanceOf(OrderConfirmation::class, $future->resolve());
    }

    public function test_ordering_asynchronous(): void
    {
        $messagingSystem = $this->buildOrderingSystem(isAsync: true, listenChannel: 'asyncChannel');

        $gateway = $messagingSystem->getGatewayByName(OrderingService::class);
        $future = $gateway->processOrder(Order::create('3', 'correct'));
        $messagingSystem->run('orderProcessor');
        $this->assertInstanceOf(OrderConfirmation::class, $future->resolve());
    }

    public function test_ordering_synchronous_exception(): void
    {
        $messagingSystem = $this->buildOrderingSystem(isAsync: false, listenChannel: 'syncChannel');

        $gateway = $messagingSystem->getGatewayByName(OrderingService::class);

        $exceptionThrown = false;
        try {
            $gateway->processOrder(Order::create('3', 'INCORRECT'));
        } catch (Exception $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Expected exception was not thrown');
    }

    public function test_ordering_asynchronous_exception(): void
    {
        $messagingSystem = $this->buildOrderingSystem(isAsync: true, listenChannel: 'asyncChannel');

        $gateway = $messagingSystem->getGatewayByName(OrderingService::class);
        $future = $gateway->processOrder(Order::create('3', 'INCORRECT'));

        $exceptionThrown = false;
        try {
            $messagingSystem->run('orderProcessor');
        } catch (Exception $e) {
            $exceptionThrown = true;
        }
        $this->assertTrue($exceptionThrown, 'Expected exception was not thrown');
    }

    public function test_calculator_synchronous_with_interceptors(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\Calculating',
            [
                InboundCalculation::class => new InboundCalculation(),
                ResultService::class => new ResultService(),
                CalculatorInterceptor::class => new CalculatorInterceptor(),
            ]
        );

        $gateway = $messagingSystem->getGatewayByName(Calculator::class);
        $this->assertEquals(18, $gateway->calculate(3));
    }

    public function test_calculator_asynchronous_with_inbound_channel_adapter(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\Calculating',
            [
                InboundCalculation::class => new InboundCalculation(),
                ResultService::class => new ResultService(),
                CalculatorInterceptor::class => new CalculatorInterceptor(),
            ]
        );

        $messagingSystem->run('inboundCalculator');

        /** @var PollableChannel $resultChannel */
        $resultChannel = $messagingSystem->getMessageChannelByName('resultChannel');
        $message = $resultChannel->receive();
        $this->assertNotNull($message);
        $this->assertEquals(15, $message->getPayload());
    }

    public function test_error_handling_with_retry_and_dead_letter(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling',
            [
                ErrorReceiver::class => new ErrorReceiver(),
                OrderService::class => new OrderService(),
            ]
        );

        $orderGateway = $messagingSystem->getGatewayByName(OrderGateway::class);
        $orderGateway->order('coffee');

        $messagingSystem->run('orderService');
        $this->assertNull($orderGateway->getIncorrectOrder());

        $messagingSystem->run('orderService');
        $this->assertNull($orderGateway->getIncorrectOrder());

        $messagingSystem->run('orderService');
        $this->assertEquals('coffee', $orderGateway->getIncorrectOrder());
    }

    public function test_gateways_inside_gateways(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway',
            [
                InterceptorExample::class => new InterceptorExample(),
                SomeQueryHandler::class => new SomeQueryHandler(),
            ]
        );

        $gateway = $messagingSystem->getGatewayByName(CalculateGatewayExample::class);
        $this->assertEquals(76, $gateway->calculate(2));
    }

    public function test_interceptors_for_gateway(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway',
            [
                \Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\InterceptorExample::class => new \Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\InterceptorExample(),
                \Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\SomeQueryHandler::class => new \Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\SomeQueryHandler(),
            ]
        );

        $gateway = $messagingSystem->getGatewayByName(\Test\Ecotone\Messaging\Fixture\Behat\InterceptedGateway\CalculateGatewayExample::class);
        $this->assertEquals(10, $gateway->calculate(2));
    }

    public function test_gateways_inside_gateways_with_messages(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages',
            [
                \Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\InterceptorExample::class => new \Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\InterceptorExample(),
                \Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler::class => new \Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\SomeQueryHandler(),
            ]
        );

        $requestMessage = MessageBuilder::withPayload(2)->build();
        $replyMessage = $messagingSystem->getGatewayByName(CalculateGatewayExampleWithMessages::class)->calculate($requestMessage);
        $this->assertEquals(76, $replyMessage->getPayload());
    }

    public function test_presend_interceptor(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\Presend',
            [
                MultiplyCoins::class => new MultiplyCoins(),
                Shop::class => new Shop(),
            ]
        );

        $coinGateway = $messagingSystem->getGatewayByName(CoinGateway::class);
        $coinGateway->store(100);

        /** @var PollableChannel $shopChannel */
        $shopChannel = $messagingSystem->getMessageChannelByName('shop');
        $this->assertEquals(200, $shopChannel->receive()->getPayload());
    }

    public function test_intercepted_scheduled_endpoint_in_recursion(): void
    {
        $messagingSystem = $this->bootstrapForNamespace(
            'Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled',
            [
                InterceptedScheduledExample::class => new InterceptedScheduledExample(),
            ]
        );

        $messagingSystem->run('scheduled.handler');

        $gateway = $messagingSystem->getGatewayByName(InterceptedScheduledGateway::class);
        $this->assertEquals(160, $gateway->getInterceptedData());
    }

    private function bootstrapForNamespace(string $namespace, array $objects): \Ecotone\Messaging\Config\ConfiguredMessagingSystem
    {
        return EcotoneLite::bootstrap(
            [],
            InMemoryPSRContainer::createFromObjects($objects),
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withEnvironment('prod')
                ->withFailFast(false)
                ->withNamespaces([$namespace]),
            pathToRootCatalog: __DIR__ . '/../../..'
        );
    }

    private function buildOrderingSystem(bool $isAsync, string $listenChannel): \Ecotone\Messaging\Config\ConfiguredMessagingSystem
    {
        $container = InMemoryPSRContainer::createEmpty();
        $container->set(ServiceCacheConfiguration::REFERENCE_NAME, ServiceCacheConfiguration::noCache());
        $container->set(OrderProcessor::class, new OrderProcessor());

        $config = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('requestChannel', DirectChannel::create()))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('routeChannel', DirectChannel::create()))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('syncChannel', DirectChannel::create()))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('asyncChannel', QueueChannel::create()))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('responseChannel', QueueChannel::create()))
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(OrderingService::class, OrderingService::class, 'processOrder', 'requestChannel')
                    ->withReplyChannel('responseChannel')
                    ->withReplyMillisecondTimeout(1)
            )
            ->registerMessageHandler(
                TransformerBuilder::createHeaderEnricher(['isAsync' => $isAsync ? '1' : '0'])
                    ->withEndpointId('transformer')
                    ->withInputChannelName('requestChannel')
                    ->withOutputMessageChannel('routeChannel')
            )
            ->registerMessageHandler(
                RouterBuilder::createHeaderMappingRouter('isAsync', ['1' => 'asyncChannel', '0' => 'syncChannel'])
                    ->withEndpointId('routing')
                    ->withInputChannelName('routeChannel')
            )
            ->registerMessageHandler(
                ServiceActivatorBuilder::create(OrderProcessor::class, InterfaceToCall::create(OrderProcessor::class, 'processOrder'))
                    ->withInputChannelName($listenChannel)
                    ->withOutputMessageChannel('responseChannel')
                    ->withEndpointId('orderProcessor')
            );

        return $config
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->buildMessagingSystemFromConfiguration($container);
    }
}
