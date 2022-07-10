<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Transformer;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\Converter;
use Ecotone\Messaging\Conversion\JsonToArray\JsonToArrayConverter;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichHeaderWithExpressionBuilder;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichHeaderWithValueBuilder;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichPayloadWithExpressionBuilder;
use Ecotone\Messaging\Handler\Enricher\Converter\EnrichPayloadWithValueBuilder;
use Ecotone\Messaging\Handler\Enricher\EnricherBuilder;
use Ecotone\Messaging\Handler\Enricher\EnrichException;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Fixture\Conversion\FakeConverterService;
use Test\Ecotone\Messaging\Fixture\Dto\OrderExample;
use Test\Ecotone\Messaging\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class PayloadEnricherBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherBuilderTest extends MessagingTest
{
    /**
     * @throws ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_no_property_or_header_setters_configured()
    {
        $this->expectException(ConfigurationException::class);

        $enricher = EnricherBuilder::create([]);

        $enricher->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_enriching_array_with_multiple_static_properties()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("token", "123"),
                EnrichPayloadWithValueBuilder::createWith("password", "secret")
            ]
        );

        $this->assertEquals(
            [
                "token" => 123,
                "password" => "secret"
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @param MessageBuilder $inputMessage
     * @param QueueChannel $outputChannel
     * @param array $setterBuilders
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createEnricherAndHandle(MessageBuilder $inputMessage, QueueChannel $outputChannel, array $setterBuilders): void
    {
        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withInputChannelName("some")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                    ]
                )
            );

        $enricher->handle($inputMessage);
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_enriching_message_with_content_type_conversion()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherWithConvertersAndHandle(
            MessageBuilder::withPayload('{"name": "Franco"}')
                ->setContentType(MediaType::createApplicationJson()),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("token", "123")
            ],
            [
                new JsonToArrayConverter()
            ]
        );

        $transformedMessage = $outputChannel->receive();

        $this->assertEquals(
            [
                "name" => "Franco",
                "token" => "123"
            ],
            $transformedMessage->getPayload()
        );
        $this->assertEquals(
            MediaType::parseMediaType(MediaType::APPLICATION_X_PHP)
                ->addParameter("type", TypeDescriptor::ARRAY)->toString(),
            $transformedMessage->getHeaders()->get(MessageHeaders::CONTENT_TYPE)
        );
    }

    /**
     * @param MessageBuilder $inputMessage
     * @param QueueChannel $outputChannel
     * @param array $setterBuilders
     * @param Converter[] $converters
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    private function createEnricherWithConvertersAndHandle(MessageBuilder $inputMessage, QueueChannel $outputChannel, array $setterBuilders, array $converters): void
    {
        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withInputChannelName("some")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create(),
                        ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith($converters)
                    ]
                )
            );

        $enricher->handle($inputMessage);
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_not_converting_request_message_if_php_related_payload()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherWithConvertersAndHandle(
            MessageBuilder::withPayload(["name" => "Franco"])
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::OBJECT)),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("token", "123")
            ],
            [FakeConverterService::create(["wrong" => 12], TypeDescriptor::OBJECT, MediaType::APPLICATION_X_PHP)]
        );

        $this->assertEquals(
            [
                "name" => "Franco",
                "token" => "123"
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_enriching_property_with_quotes_in_names()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("'token'", "123"),
                EnrichPayloadWithValueBuilder::createWith('"password"', "secret")
            ]
        );

        $this->assertEquals(
            [
                "token" => 123,
                "password" => "secret"
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_copying_headers_from_input_message()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([])
                ->setHeader("user", 1),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("token", "123")
            ]
        );


        $this->assertEquals(1, $outputChannel->receive()->getHeaders()->get("user"));
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_enriching_with_static_headers()
    {
        $outputChannel = QueueChannel::create();
        $this->createEnricherAndHandle(MessageBuilder::withPayload("some"), $outputChannel, [
            EnrichHeaderWithValueBuilder::create("token", "123")
        ]);

        $this->assertEquals(
            "123",
            $outputChannel->receive()->getHeaders()->get("token")
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_enriching_with_expression_setter_from_external_message()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(["surname" => "levis"]);
        $replyPayload = "johny";
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWith("name", "payload")
        ];
        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, $replyPayload, $setterBuilders);

        $this->assertEquals(
            [
                "surname" => "levis",
                "name" => "johny"
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @param MessageBuilder $inputMessage
     * @param QueueChannel $outputChannel
     * @param mixed $replyPayload
     * @param array $setterBuilders
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createEnricherWithRequestChannelAndHandle(MessageBuilder $inputMessage, QueueChannel $outputChannel, $replyPayload, array $setterBuilders): void
    {
        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel = DirectChannel::create();
        $requestChannel->subscribe(ReplyViaHeadersMessageHandler::create($replyPayload));

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withInputChannelName("some")
            ->withRequestMessageChannel($requestChannelName)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray(
                    [
                        $requestChannelName => $requestChannel
                    ]
                ),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                    ]
                )
            );

        $enricher->handle($inputMessage);
    }

    public function test_enriching_when_payload_is_object_with_public_setter_method()
    {
        $outputChannel = QueueChannel::create();

        $buyerNameToSet = "johny levis";
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(OrderExample::createFromId(100)),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("buyerName", $buyerNameToSet)
            ]
        );

        /** @var OrderExample $payload */
        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(
            $buyerNameToSet,
            $payload->getBuyerName()
        );
    }

    public function test_enriching_when_payload_is_object_with_no_public_setter_method()
    {
        $outputChannel = QueueChannel::create();

        $newOrderId = 999;
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(OrderExample::createFromId(100)),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("orderId", $newOrderId)
            ]
        );

        /** @var OrderExample $payload */
        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(
            $newOrderId,
            $payload->getId()
        );
    }

    public function test_throwing_exception_if_cannot_enrich_property()
    {
        $outputChannel = QueueChannel::create();

        $this->expectException(EnrichException::class);

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(OrderExample::createFromId(100)),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("notExisting", "some")
            ]
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_enriching_array_by_array_setter_definition()
    {
        $outputChannel = QueueChannel::create();

        $newWorkerId = 1000;
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["workerId" => 123]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("[workerId]", $newWorkerId)
            ]
        );

        $this->assertEquals(
            [
                "workerId" => 1000
            ]
            ,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_enriching_array_by_array_of_array_by_setter_definition()
    {
        $outputChannel = QueueChannel::create();

        $newWorkerId = 1000;
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([["workerId" => 123]]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("[0][workerId]", $newWorkerId)
            ]
        );

        $this->assertEquals(
            [
                [
                    "workerId" => 1000
                ]
            ]
            ,
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_enriching_object_inside_array()
    {
        $outputChannel = QueueChannel::create();

        $buyerName = "Johny";
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["order" => OrderExample::createFromId(1)]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("[order][buyerName]", $buyerName)
            ]
        );

        $this->assertEquals(
            [
                "order" => OrderExample::createWith(1, 1, $buyerName)
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_enriching_multidimensional_array()
    {
        $outputChannel = QueueChannel::create();

        $workerData = [
            "name" => "johny",
            "surname" => "franco"
        ];
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(
                [
                    [
                        "data" => [
                            "workerIdentifier" => ["workerId" => 123]
                        ]
                    ]
                ]
            ),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("[0][data][worker]", $workerData)
            ]
        );

        $this->assertEquals(
            [
                [
                    "data" => [
                        "workerIdentifier" => ["workerId" => 123],
                        "worker" => $workerData
                    ]
                ]
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_enriching_array_by_array_path_beginning_with_property_name()
    {
        $outputChannel = QueueChannel::create();

        $workerName = "johny";
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["worker" => []]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("worker[name]", $workerName)
            ]
        );

        $this->assertEquals(
            ["worker" => ["name" => $workerName]],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_enriching_array_with_new_element()
    {
        $outputChannel = QueueChannel::create();

        $workerName = "johny";
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["worker" => ["names" => ["Edward"]]]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("worker[names][]", $workerName)
            ]
        );

        $this->assertEquals(
            ["worker" => ["names" => ["Edward", $workerName]]],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_throwing_exception_if_enriching_array_element_is_not_array()
    {
        $outputChannel = QueueChannel::create();

        $workerName = "johny";
        $this->expectException(EnrichException::class);

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["worker" => ["names" => "Edward"]]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("worker[names][]", $workerName)
            ]
        );
    }

    public function test_setting_property_path_containing_dots()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([]),
            $outputChannel,
            [
                EnrichPayloadWithValueBuilder::createWith("order.orderId", "some")
            ]
        );

        $this->assertEquals(
            ["order.orderId" => "some"],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_enriching_array_with_multiple_values_at_once_by_mapping()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => [
                    ["orderId" => 1, "personId" => 1],
                    ["orderId" => 2, "personId" => 4]
                ]
            ]
        );
        $replyPayload = [
            [
                "personId" => 1,
                "name" => "johny"
            ],
            [
                "personId" => 4,
                "name" => "franco"
            ]
        ];
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWithMapping("[orders][*][person]", "payload", "requestContext['personId'] == replyContext['personId']")
        ];

        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, $replyPayload, $setterBuilders);

        $this->assertEquals(
            [
                "orders" => [
                    [
                        "orderId" => 1,
                        "personId" => 1,
                        "person" =>
                            [
                                "personId" => 1,
                                "name" => "johny"
                            ]
                    ],
                    [
                        "orderId" => 2,
                        "personId" => 4,
                        "person" =>
                            [
                                "personId" => 4,
                                "name" => "franco"
                            ]
                    ]
                ]
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_if_no_request_channel_passed_then_evaluate_for_request_message()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(["surname" => "levis"]);
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWith("name", "payload"),
            EnrichHeaderWithExpressionBuilder::createWith("toUpload", "request['payload']['surname']")
        ];
        $this->createEnricherAndHandle($inputMessage, $outputChannel, $setterBuilders);

        $message = $outputChannel->receive();
        $this->assertEquals(
            [
                "surname" => "levis",
                "name" => null
            ],
            $message->getPayload()
        );
        $this->assertEquals(
            'levis',
            $message->getHeaders()->get("toUpload")
        );
    }

    public function test_throwing_exception_if_enriching_multiple_values_but_there_was_no_enough_data_to_be_mapped()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => [
                    ["orderId" => 1, "personId" => 1]
                ]
            ]
        );
        $replyPayload = [];
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWithMapping("[orders][*][person]", "payload", "requestContext['personId'] == replyContext['personId']")
        ];

        $this->expectException(MessagingException::class);

        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, $replyPayload, $setterBuilders);
    }

    public function test_not_doing_mapping_when_context_is_empty()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => []
            ]
        );
        $replyPayload = [];
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWithMapping("[orders][*][person]", "payload", "requestContext['personId'] == replyContext['personId']")
        ];

        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, $replyPayload, $setterBuilders);

        $this->assertEquals(
            [
                "orders" => []
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_using_null_expression_when_no_reply_available_on_payload_setter()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(["personId" => 1]);
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWith("name", "payload")
                ->withNullResultExpression("'unknown'")
        ];

        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, null, $setterBuilders);

        $this->assertEquals(
            ["personId" => 1, "name" => "unknown"],
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_using_null_expression_when_no_reply_available_on_header_setter()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(["personId" => 1]);
        $setterBuilders = [
            EnrichHeaderWithExpressionBuilder::createWith("name", "payload")
                ->withNullResultExpression("'unknown'")
        ];

        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, null, $setterBuilders);

        $this->assertEquals(
            "unknown",
            $outputChannel->receive()->getHeaders()->get("name")
        );
    }

    public function test_sending_request_message_evaluated_with_expression()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => []
            ]
        );
        $replyPayload = [];
        $setterBuilders = [
            EnrichPayloadWithExpressionBuilder::createWithMapping("[orders][*][person]", "payload", "requestContext['personId'] == replyContext['personId']")
        ];

        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel = DirectChannel::create();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withInputChannelName("some")
            ->withRequestMessageChannel($requestChannelName)
            ->withRequestPayloadExpression("payload['orders']")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray(
                    [
                        $requestChannelName => $requestChannel
                    ]
                ),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                    ]
                )
            );

        $enricher->handle($inputMessage);

        $this->assertEquals([], $messageHandler->getReceivedMessage()->getPayload());
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function TODO_test_converting_reply_message_for_evaluation()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "name" => "Johny"
            ]
        );
        $replyMessage = MessageBuilder::withPayload('{"surname": "Franco"}')
            ->setContentType(MediaType::createApplicationJson())
            ->build();
        $setterBuilders = [EnrichPayloadWithExpressionBuilder::createWith("surname", "payload['surname']")];

        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel = DirectChannel::create();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyMessage);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withRequestMessageChannel($requestChannelName)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray(
                    [
                        $requestChannelName => $requestChannel
                    ]
                ),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create(),
                        ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([
                            new JsonToArrayConverter()
                        ])
                    ]
                )
            );

        $enricher->handle($inputMessage);

        $transformedMessage = $outputChannel->receive();
        $this->assertEquals([
            "name" => "Johny",
            "surname" => "Franco"
        ], $transformedMessage->getPayload());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_extracting_unique_values_from_arrays_for_request_message()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => [
                    [
                        "orderId" => 1
                    ],
                    [
                        "orderId" => 2
                    ],
                    [
                        "orderId" => 2
                    ]
                ]
            ]
        );
        $replyPayload = [];
        $setterBuilders = [EnrichPayloadWithExpressionBuilder::createWith("test", "1")];

        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel = DirectChannel::create();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withInputChannelName("some")
            ->withRequestMessageChannel($requestChannelName)
            ->withRequestPayloadExpression("extract(payload['orders'], 'orderId')")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray(
                    [
                        $requestChannelName => $requestChannel
                    ]
                ),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                    ]
                )
            );

        $enricher->handle($inputMessage);

        $this->assertEquals([1, 2], $messageHandler->getReceivedMessage()->getPayload());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_extracting_not_unique_values_from_arrays_for_request_message()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => [
                    [
                        "orderId" => 1
                    ],
                    [
                        "orderId" => 2
                    ],
                    [
                        "orderId" => 2
                    ]
                ]
            ]
        );
        $replyPayload = [];
        $setterBuilders = [EnrichPayloadWithExpressionBuilder::createWith("test", "1")];

        $inputMessage = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel = DirectChannel::create();
        $messageHandler = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create($setterBuilders)
            ->withInputChannelName("some")
            ->withRequestMessageChannel($requestChannelName)
            ->withRequestPayloadExpression("createArray('ids', extract(payload['orders'], 'orderId', false))")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray(
                    [
                        $requestChannelName => $requestChannel
                    ]
                ),
                InMemoryReferenceSearchService::createWith(
                    [
                        ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                    ]
                )
            );

        $enricher->handle($inputMessage);

        $this->assertEquals(['ids' => [1, 2, 2]], $messageHandler->getReceivedMessage()->getPayload());
    }
}