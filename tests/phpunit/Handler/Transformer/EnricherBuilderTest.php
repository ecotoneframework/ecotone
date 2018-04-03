<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use Fixture\Dto\OrderExample;
use Fixture\Handler\ReplyViaHeadersMessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\ExpressionHeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\ExpressionPayloadSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\MultipleExpressionPayloadSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\StaticHeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\StaticPayloadSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class PayloadEnricherBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherBuilderTest extends MessagingTest
{
    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_no_property_or_header_setters_configured()
    {
        $this->expectException(ConfigurationException::class);

        $enricher = EnricherBuilder::create("some", []);

        $enricher->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createEmpty());
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_enriching_array_with_multiple_static_properties()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([]),
            $outputChannel,
            [
                StaticPayloadSetterBuilder::createWith("token", "123"),
                StaticPayloadSetterBuilder::createWith("password", "secret")
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_copying_headers_from_input_message()
    {
        $outputChannel = QueueChannel::create();

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([])
                ->setHeader("user", 1),
            $outputChannel,
            [
                StaticPayloadSetterBuilder::createWith("token", "123")
            ]
        );


        $this->assertEquals(1, $outputChannel->receive()->getHeaders()->get("user"));
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_enriching_with_static_headers()
    {
        $outputChannel = QueueChannel::create();
        $this->createEnricherAndHandle(MessageBuilder::withPayload("some"), $outputChannel, [
            StaticHeaderSetterBuilder::create("token", "123")
        ]);

        $this->assertEquals(
            "123",
            $outputChannel->receive()->getHeaders()->get("token")
        );
    }

    public function test_enriching_with_expression_setter_from_external_message()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(["surname" => "levis"]);
        $replyPayload = "johny";
        $setterBuilders = [
            ExpressionPayloadSetterBuilder::createWith("name", "payload")
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

    public function test_enriching_when_payload_is_object_with_public_setter_method()
    {
        $outputChannel = QueueChannel::create();

        $buyerNameToSet = "johny levis";
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(OrderExample::createFromId(100)),
            $outputChannel,
            [
                StaticPayloadSetterBuilder::createWith("buyerName", $buyerNameToSet)
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
                StaticPayloadSetterBuilder::createWith("orderId", $newOrderId)
            ]
        );

        /** @var OrderExample $payload */
        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(
            $newOrderId,
            $payload->getOrderId()
        );
    }

    public function test_throwing_exception_if_cannot_enrich_property()
    {
        $outputChannel = QueueChannel::create();

        $this->expectException(MessageHandlingException::class);

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(OrderExample::createFromId(100)),
            $outputChannel,
            [
                StaticPayloadSetterBuilder::createWith("notExisting", "some")
            ]
        );
    }

    public function test_enriching_array_by_array_setter_definition()
    {
        $outputChannel = QueueChannel::create();

        $newWorkerId = 1000;
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["workerId" => 123]),
            $outputChannel,
            [
                StaticPayloadSetterBuilder::createWith("[workerId]", $newWorkerId)
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

    public function test_enriching_array_by_array_of_array_by_setter_definition()
    {
        $outputChannel = QueueChannel::create();

        $newWorkerId = 1000;
        $this->createEnricherAndHandle(
            MessageBuilder::withPayload([["workerId" => 123]]),
            $outputChannel,
            [
                StaticPayloadSetterBuilder::createWith("[0][workerId]", $newWorkerId)
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
                StaticPayloadSetterBuilder::createWith("[order][buyerName]", $buyerName)
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
                StaticPayloadSetterBuilder::createWith("[0][data][worker]", $workerData)
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
                StaticPayloadSetterBuilder::createWith("worker[name]", $workerName)
            ]
        );

        $this->assertEquals(
            ["worker" => ["name" => $workerName]],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_throwing_exception_if_property_path_contains_dots()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createEnricherAndHandle(
            MessageBuilder::withPayload(["order" => OrderExample::createFromId(100)]),
            QueueChannel::create(),
            [
                StaticPayloadSetterBuilder::createWith("order.orderId", "some")
            ]
        );
    }

    public function test_enriching_array_with_multiple_values_at_once_by_mapping()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => [
                    ["orderId"=>1, "personId"=>1],
                    ["orderId"=>2, "personId"=>4]
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
            MultipleExpressionPayloadSetterBuilder::createWithMapping("person", "payload", "orders", "context['personId'] == reply['personId']")
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

    public function test_enriching_root_array_with_multiple_values_at_once_by_mapping()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                ["orderId"=>1, "personId"=>1],
                ["orderId"=>2, "personId"=>4]
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
            MultipleExpressionPayloadSetterBuilder::createWithMapping("person", "payload", "", "context['personId'] == reply['personId']")
        ];
        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, $replyPayload, $setterBuilders);

        $this->assertEquals(
            [
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
            ],
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_throwing_exception_if_enriching_multiple_values_but_there_was_no_enough_data_to_be_mapped()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(
            [
                "orders" => [
                    ["orderId"=>1, "personId"=>1]
                ]
            ]
        );
        $replyPayload = [];
        $setterBuilders = [
            MultipleExpressionPayloadSetterBuilder::createWithMapping("person", "payload", "orders", "context['personId'] == reply['personId']")
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
            MultipleExpressionPayloadSetterBuilder::createWithMapping("person", "payload", "orders", "context['personId'] == reply['personId']")
        ];

        $this->createEnricherWithRequestChannelAndHandle($inputMessage, $outputChannel, $replyPayload, $setterBuilders);

        $this->assertEquals(
            [
                "orders" => []
            ],
            $outputChannel->receive()->getPayload()
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
            MultipleExpressionPayloadSetterBuilder::createWithMapping("person", "payload", "orders", "context['personId'] == reply['personId']")
        ];

        $inputMessage       = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel     = DirectChannel::create();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
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

    public function test_sending_request_messages_with_appended_new_headers()
    {
        $outputChannel = QueueChannel::create();
        $inputMessage = MessageBuilder::withPayload(["orders" => []]);
        $replyPayload = [];
        $setterBuilders = [StaticPayloadSetterBuilder::createWith("some", "test")];

        $inputMessage       = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel     = DirectChannel::create();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
            ->withRequestMessageChannel($requestChannelName)
            ->withRequestHeader("token", "123")
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

        $this->assertEquals(123, $messageHandler->getReceivedMessage()->getHeaders()->get("token"));
    }

    public function test_sending_request_message_and_enriched_with_new_header()
    {
        $outputChannel = QueueChannel::create();
        $replyPayload = MessageBuilder::withPayload("some")
                            ->setHeader("userId", 123)
                            ->build();
        $setterBuilders = [
            ExpressionHeaderSetterBuilder::createWith("user", "headers['userId']")
        ];

        $inputMessage       = MessageBuilder::withPayload("some")
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel     = DirectChannel::create();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
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

        $this->assertEquals(123, $outputChannel->receive()->getHeaders()->get("user"));
    }

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
        $setterBuilders = [ExpressionPayloadSetterBuilder::createWith("test", "1")];

        $inputMessage       = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel     = DirectChannel::create();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
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
        $setterBuilders = [ExpressionPayloadSetterBuilder::createWith("test", "1")];

        $inputMessage       = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel     = DirectChannel::create();
        $messageHandler     = ReplyViaHeadersMessageHandler::create($replyPayload);
        $requestChannel->subscribe($messageHandler);

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
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

    /**
     * @param MessageBuilder $inputMessage
     * @param QueueChannel $outputChannel
     * @param mixed $replyPayload
     * @param array $setterBuilders
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createEnricherWithRequestChannelAndHandle(MessageBuilder $inputMessage, QueueChannel $outputChannel, $replyPayload, array $setterBuilders): void
    {
        $inputMessage       = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();
        $requestChannelName = "requestChannel";
        $requestChannel     = DirectChannel::create();
        $requestChannel->subscribe(ReplyViaHeadersMessageHandler::create($replyPayload));

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
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

    /**
     * @param MessageBuilder $inputMessage
     * @param QueueChannel $outputChannel
     * @param array $setterBuilders
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createEnricherAndHandle(MessageBuilder $inputMessage, QueueChannel $outputChannel, array $setterBuilders): void
    {
        $inputMessage       = $inputMessage
            ->setReplyChannel($outputChannel)
            ->build();

        $enricher = EnricherBuilder::create(
            "some",
            $setterBuilders
        )
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
}