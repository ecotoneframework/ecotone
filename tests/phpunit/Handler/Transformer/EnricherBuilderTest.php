<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use Fixture\Dto\OrderExample;
use Fixture\Handler\ReplyViaHeadersMessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnrichException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter\StaticHeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter\ExpressionSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter\StaticSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SymfonyExpressionEvaluationAdapter;
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
                StaticSetterBuilder::createWith("token", "123"),
                StaticSetterBuilder::createWith("password", "secret")
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
                StaticSetterBuilder::createWith("token", "123")
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
            ExpressionSetterBuilder::createWith("name", "payload")
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
                StaticSetterBuilder::createWith("buyerName", $buyerNameToSet)
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
                StaticSetterBuilder::createWith("orderId", $newOrderId)
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
                StaticSetterBuilder::createWith("notExisting", "some")
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
                StaticSetterBuilder::createWith("[workerId]", $newWorkerId)
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
                StaticSetterBuilder::createWith("[0][workerId]", $newWorkerId)
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

//    public function test_enriching_headers_when_header_is_object()
//    {
//        $outputChannel = QueueChannel::create();
//
//        $newOrderId = 999;
//        $this->createEnricherAndHandle(
//            MessageBuilder::withPayload("some")
//                ->setHeader("order", OrderExample::createFromId(100)),
//            $outputChannel,
//            [
//                StaticHeaderSetterBuilder::create("orderId", $newOrderId)
//            ]
//        );
//
//        /** @var OrderExample $payload */
//        $payload = $outputChannel->receive()->getPayload();
//        $this->assertEquals(
//            $newOrderId,
//            $payload->getOrderId()
//        );
//    }

    /**
     * @param $inputMessage
     * @param $outputChannel
     * @param $replyPayload
     * @param $setterBuilders
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
     * @param $inputMessage
     * @param $outputChannel
     * @param $setterBuilders
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