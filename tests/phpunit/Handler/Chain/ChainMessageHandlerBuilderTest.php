<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Chain;

use Fixture\Handler\Transformer\PassThroughTransformer;
use Fixture\Handler\Transformer\StdClassTransformer;
use Fixture\Handler\Transformer\StringTransformer;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\ExpressionHeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\StaticHeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter\StaticPayloadSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\SymfonyExpressionEvaluationAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class ChainMessageHandlerBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Chain
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainMessageHandlerBuilderTest extends TestCase
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \Exception
     */
    public function test_chaining_with_single_message_handler()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "some";
        $messageHandler = TransformerBuilder::createHeaderEnricher("", [
            "token" => "123"
        ]);

        $this->createChainHandlerAndHandle([$messageHandler], $requestPayload, $replyChannel);

        $this->assertEquals(
            "123",
            $replyChannel->receive()->getHeaders()->get("token")
        );
    }

    public function test_returning_input_channel()
    {
        $chainBuilder = ChainMessageHandlerBuilder::createWith("some");

        $this->assertEquals("some", $chainBuilder->getInputMessageChannelName());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_chaining_with_two_message_handlers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "some";

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createHeaderEnricher("", [
                    "token" => "123"
                ]),
                TransformerBuilder::createHeaderEnricher("", [
                    "hax" => "x"
                ])
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            "123",
            $message->getHeaders()->get("token")
        );
        $this->assertEquals(
            "x",
            $message->getHeaders()->get("hax")
        );
    }

    public function test_chaining_payload_transformers()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "x";

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createWithReferenceObject("", new StdClassTransformer(), "transform"),
                TransformerBuilder::createWithReferenceObject("", new StringTransformer(), "transform"),
            ],
            $requestPayload,
            $replyChannel
        );

        $this->assertEquals(
            "some",
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_chaining_with_other_chain_inside()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "x";

        $this->createChainHandlerAndHandle(
            [
                TransformerBuilder::createWithReferenceObject("", new StdClassTransformer(), "transform"),
                TransformerBuilder::createWithReferenceObject("", new StringTransformer(), "transform"),
                ChainMessageHandlerBuilder::createWith("")
                    ->chain(TransformerBuilder::createWithReferenceObject("", new StdClassTransformer(), "transform"))
            ],
            $requestPayload,
            $replyChannel
        );

        $this->assertEquals(
            new \stdClass(),
            $replyChannel->receive()->getPayload()
        );
    }

    public function test_chaining_with_other_chain_at_the_beginning_of_flow()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = ["some" => "bla"];

        $this->createChainHandlerAndHandle(
            [
                EnricherBuilder::create("", [
                   StaticHeaderSetterBuilder::create("awesome", "yes")
                ]),
                ChainMessageHandlerBuilder::createWith("")
                    ->chain(TransformerBuilder::createWithReferenceObject("", new StdClassTransformer(), "transform")),
                TransformerBuilder::createWithReferenceObject("", new PassThroughTransformer(), "transform"),
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            new \stdClass(),
            $message->getPayload()
        );
        $this->assertEquals(
            "yes",
            $message->getHeaders()->get("awesome")
        );
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_setting_output_channel()
    {
        $outputChannelName = "outputChannelName";
        $outputChannel = QueueChannel::create();
        $requestPayload = "some";

        $chainHandler = ChainMessageHandlerBuilder::createWith("some")
            ->chain(TransformerBuilder::createHeaderEnricher("", [
                "token" => "123"
            ]))
            ->withOutputMessageChannel($outputChannelName)
            ->build(InMemoryChannelResolver::createFromAssociativeArray([
                $outputChannelName => $outputChannel
            ]), InMemoryReferenceSearchService::createEmpty());

        $chainHandler->handle(
            MessageBuilder::withPayload($requestPayload)
                ->build()
        );

        $this->assertEquals(
            "123",
            $outputChannel->receive()->getHeaders()->get("token")
        );
    }

    public function test_chaining_with_three_levels()
    {
        $replyChannel = QueueChannel::create();
        $requestPayload = "x";

        $this->createChainHandlerAndHandle(
            [
                ChainMessageHandlerBuilder::createWith("")
                    ->chain(
                        ChainMessageHandlerBuilder::createWith("")
                            ->chain(EnricherBuilder::create("", [
                                ExpressionHeaderSetterBuilder::createWith("some", "false")
                            ]))
                            ->chain(EnricherBuilder::create("", [
                                ExpressionHeaderSetterBuilder::createWith("some2", "payload")
                            ]))
                    )
            ],
            $requestPayload,
            $replyChannel
        );

        $message = $replyChannel->receive();
        $this->assertEquals(
            false,
            $message->getHeaders()->get("some")
        );
        $this->assertEquals(
            "x",
            $message->getHeaders()->get("some2")
        );
    }

    public function test_passing_references_objects_to_top_handler()
    {
        $chainBuilder = ChainMessageHandlerBuilder::createWith("some")
                        ->chain(TransformerBuilder::create("", "some", "method"));

        $this->assertEquals(["some"], $chainBuilder->getRequiredReferenceNames());
    }

    /**
     * @param $messageHandlers
     * @param $requestPayload
     * @param $replyChannel
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createChainHandlerAndHandle(array $messageHandlers, $requestPayload, $replyChannel): void
    {
        $chainHandler = ChainMessageHandlerBuilder::createWith("some");
        foreach ($messageHandlers as $messageHandler) {
            $chainHandler = $chainHandler->chain($messageHandler);
        }

        $chainHandler = $chainHandler
            ->build(InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ]));

        $chainHandler->handle(
            MessageBuilder::withPayload($requestPayload)
                ->setReplyChannel($replyChannel)
                ->build()
        );
    }

}