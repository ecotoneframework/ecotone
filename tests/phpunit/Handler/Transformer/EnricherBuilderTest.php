<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer;

use Fixture\Handler\ReplyViaHeadersMessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\SymfonyExpressionEvaluationAdapter;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class PayloadEnricherBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnricherBuilderTest extends MessagingTest
{
    public function test_requesting_external_message_endpoint()
    {
        $requestChannelName = "requestChannel";
        $requestChannel = DirectChannel::create();
        $replyData      = "somereplydata";
        $requestHandler = ReplyViaHeadersMessageHandler::create($replyData);
        $requestChannel->subscribe($requestHandler);



        $enricher = EnricherBuilder::create("inputChannel", $requestChannelName)
                        ->build(
                            InMemoryChannelResolver::createFromAssociativeArray([
                                $requestChannelName => $requestChannel
                            ]),
                            InMemoryReferenceSearchService::createWith([
                                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
                            ])
                        );

        $outputChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload("some")->setReplyChannel($outputChannel)->build();
        $enricher->handle($requestMessage);

        $this->assertEquals(
            $requestMessage,
            $requestHandler->getReceivedMessage()
        );
        $this->assertEquals(
            $replyData,
            $outputChannel->receive()->getPayload()
        );
    }


}