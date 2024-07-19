<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Chain;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use Ecotone\Messaging\Handler\Enricher\EnricherBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion\Order;
use Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion\OrderConverter;
use Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion\OrderIdIncreaser;
use Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion\OrderNamePrefixer;
use Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion\OrderReceiver;
use Test\Ecotone\Messaging\Fixture\Handler\Transformer\StdClassTransformer;
use Test\Ecotone\Messaging\Fixture\Handler\Transformer\StringTransformer;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;

/**
 * Class ChainMessageHandlerBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Chain
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ChainMessageHandlerBuilderTest extends TestCase
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws Exception
     */
    public function test_chaining_with_single_message_handler()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 'some';
        $messageHandler = TransformerBuilder::createHeaderEnricher([
            'token' => '123',
        ]);

        $this->createChainHandlerAndHandle([$messageHandler], $requestPayload, $replyChannel);

        $this->assertEquals(
            '123',
            $replyChannel->receive()->getHeaders()->get('token')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_chaining_with_two_message_handlers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 'some';

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createHeaderEnricher([
                    'token' => '123',
                ]),
                TransformerBuilder::createHeaderEnricher([
                    'hax' => 'x',
                ]),
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            '123',
            $message->getHeaders()->get('token')
        );
        $this->assertEquals(
            'x',
            $message->getHeaders()->get('hax')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_with_chain_handler_at_the_end()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 0;

        $this->createChainHandlerAndHandle(
            [
                ChainMessageHandlerBuilder::create()
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    ),
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            4,
            $message->getPayload()
        );
    }

    public function test_chaining_payload_transformers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 'x';

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createWithDirectObject(new StdClassTransformer(), 'transform'),
                TransformerBuilder::createWithDirectObject(new StringTransformer(), 'transform'),
            ],
            $requestPayload,
            $replyChannel
        );

        $this->assertEquals(
            'some',
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_chaining_with_other_chain_inside()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 'x';

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createWithDirectObject(new StdClassTransformer(), 'transform'),
                TransformerBuilder::createWithDirectObject(new StringTransformer(), 'transform'),
                ChainMessageHandlerBuilder::create()
                    ->chain(TransformerBuilder::createWithDirectObject(new StdClassTransformer(), 'transform')),
            ],
            $requestPayload,
            $replyChannel
        );

        $this->assertEquals(
            new stdClass(),
            $replyChannel->receive()->getPayload()
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_chaining_with_other_chain_at_the_beginning_of_flow()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = ['some' => 'bla'];

        $this->createChainHandlerAndHandle(
            [
                EnricherBuilder::create([
                    EnrichHeaderWithValueBuilder::create('awesome', 'yes'),
                ]),
                ChainMessageHandlerBuilder::create()
                    ->chain(TransformerBuilder::createWithDirectObject(new StdClassTransformer(), 'transform'))
                    ->chain(TransformerBuilder::createHeaderEnricher(['superAwesome' => 'no'])),
                TransformerBuilder::createHeaderEnricher(['superAwesome' => 'yes']),
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            new stdClass(),
            $message->getPayload()
        );
        $this->assertEquals(
            'yes',
            $message->getHeaders()->get('awesome')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passing_through_internal_output_channel_at_the_end_of_the_stack()
    {
        $internalOutputChannelName = 'internalOutputChannelName';
        $triggerChannelName = 'externalOutputChannelName';
        $internalOutputChannel = DirectChannel::create();

        $componentTest = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($internalOutputChannelName, $internalOutputChannel))
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum')
                    ->withInputChannelName($internalOutputChannelName)
            )
            ->withMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($triggerChannelName)
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->withOutputMessageChannel($internalOutputChannelName)
                    )
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'))
            );

        $this->assertEquals(
            10,
            $componentTest->build()->sendDirectToChannel($triggerChannelName, 0)
        );
    }

    public function test_having_chain_in_chain_with_around_interceptors()
    {
        $aroundAddOneAfterCall = AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
            InterfaceToCallRegistry::createEmpty(),
            CalculatingServiceInterceptorExample::create(1),
            'sumAfterCalling',
            1,
            ConsumerContinuouslyWorkingService::class
        );

        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->chainInterceptedHandler(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                            ->addAroundInterceptor($aroundAddOneAfterCall)
                    )
                    ->chainInterceptedHandler(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'))
                    ->addAroundInterceptor($aroundAddOneAfterCall)
            )
            ->build();

        $this->assertEquals(
            13,
            $messaging->sendDirectToChannel($inputChannel, 0)
        );
    }

    public function test_having_chain_with_output_channel_and_around_interceptor()
    {
        $aroundAddOneAfterCall = AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
            InterfaceToCallRegistry::createEmpty(),
            CalculatingServiceInterceptorExample::create(10),
            'sumAfterCalling',
            1,
            ConsumerContinuouslyWorkingService::class
        );

        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum')
                    ->withInputChannelName($internalOutputChannelName = 'internalOutputChannelName')
            )
            ->withMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($requestChannel = 'requestChannel')
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'sum'))
                    ->chainInterceptedHandler(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply')
                            ->withOutputMessageChannel($internalOutputChannelName)
                    )
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    ->addAroundInterceptor($aroundAddOneAfterCall)
            )
            ->build();

        $this->assertEquals(
            16,
            $messaging->sendDirectToChannel($requestChannel, 0)
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_chaining_with_three_levels()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 'x';

        $this->createChainHandlerAndHandle(
            [
                ChainMessageHandlerBuilder::create()
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(EnricherBuilder::create([
                                EnrichHeaderWithExpressionBuilder::createWith('some', 'false'),
                            ]))
                            ->chain(EnricherBuilder::create([
                                EnrichHeaderWithExpressionBuilder::createWith('some2', "request['payload']"),
                            ]))
                    ),
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            false,
            $message->getHeaders()->get('some')
        );
        $this->assertEquals(
            'x',
            $message->getHeaders()->get('some2')
        );
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_chaining_multiple_handlers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = 1;

        $this->createChainHandlerAndHandle(
            [
                ChainMessageHandlerBuilder::create()
                    //1
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    //2
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'))
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    )
                    //5
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'))
                    //6
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'))
                    //12
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum')),
                //13
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'),
                //14
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'),
                //28
                ChainMessageHandlerBuilder::create()
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'sum'))
                    //30
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply')),
                //60
                ChainMessageHandlerBuilder::create()
                    ->chain(
                        ChainMessageHandlerBuilder::create()
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply'))
                            //120
                            ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(10), 'sum'))
                        //130
                    )
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'multiply')),
                //260
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum'),
                //261
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            261,
            $message->getPayload()
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws Exception
     */
    public function test_chaining_with_router_at_the_end()
    {
        $outputChannelName = 'outputChannel';
        $outputChannel = QueueChannel::create();
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($outputChannelName, $outputChannel))
            ->withMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($requestChannel = 'requestChannel')
                    ->chain(TransformerBuilder::createWithExpression('1 + 1'))
                    ->chain(TransformerBuilder::createWithExpression('payload + 1'))
                    ->withOutputMessageHandler(RouterBuilder::createRecipientListRouter([$outputChannelName]))
            )
            ->build();

        $messaging->sendDirectToChannel($requestChannel, 'some');

        $this->assertEquals(
            3,
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_chaining_with_conversions_flow_using_x_type_to_keep_class_type_when_final_endpoint_is_not_compatible_with_payload()
    {
        $messaging = ComponentTestBuilder::create([OrderConverter::class])
            ->withReference(OrderConverter::class, new OrderConverter())
            ->withMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->withInputChannelName($requestChannel = 'requestChannel')
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(new OrderNamePrefixer('ecotone.'), 'transform'))
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(new OrderIdIncreaser(), 'increase'))
                    ->chain(ServiceActivatorBuilder::createWithDirectReference(new OrderReceiver(), 'receive'))
            )
            ->build();

        $this->assertEquals(
            new Order('2', 'ecotone.shop'),
            $messaging->sendMessageDirectToChannel(
                $requestChannel,
                MessageBuilder::withPayload(new Order('1', 'shop'))
                    ->setHeader(MessageHeaders::TYPE_ID, Order::class)
                    ->build()
            )
        );
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_configured_output_channel_and_output_handler()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                ChainMessageHandlerBuilder::create()
                    ->chain(TransformerBuilder::createWithExpression('1 + 1'))
                    ->withOutputMessageHandler(RouterBuilder::createRecipientListRouter(['some']))
                    ->withOutputMessageChannel('some')
            )
            ->build();
    }

    /**
     * @param $messageHandlers
     * @param $requestPayload
     * @param $replyChannel
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createChainHandlerAndHandle(array $messageHandlers, $requestPayload, $replyChannel): void
    {
        $chainHandlerBuilder = ChainMessageHandlerBuilder::create()
                                    ->withInputChannelName($inputChannel = 'inputChannel')
                                    ->withOutputMessageChannel($outputChannel = 'outputChannel');
        foreach ($messageHandlers as $messageHandler) {
            $chainHandlerBuilder = $chainHandlerBuilder->chain($messageHandler);
        }

        $messaging = ComponentTestBuilder::create()
                        ->withChannel(SimpleMessageChannelBuilder::create($outputChannel, $replyChannel))
                        ->withMessageHandler($chainHandlerBuilder)
                        ->build();

        $messaging->sendDirectToChannel(
            $inputChannel,
            MessageBuilder::withPayload($requestPayload)
                ->build()
        );
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = 'someName';

        $this->assertEquals(
            ChainMessageHandlerBuilder::create()
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf('Handler of type %s with name `%s` for input channel `%s`', ChainMessageHandlerBuilder::class, $endpointName, $inputChannelName)
        );
    }
}
