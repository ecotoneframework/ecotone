<?php

namespace SimplyCodedSoftware\DomainModel\Config;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\OrderedMethodInterceptor;
use SimplyCodedSoftware\Messaging\Config\RequiredReference;
use SimplyCodedSoftware\DomainModel\AggregateMessage;
use SimplyCodedSoftware\DomainModel\AggregateMessageConversionServiceBuilder;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;

/**
 * Class AggregateMessageRouterModule
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageRouterModule implements AnnotationModule
{
    const AGGREGATE_ROUTER_MODULE = "aggregateRouterModule";

    /**
     * @var RouterBuilder
     */
    private $router;

    /**
     * AggregateMessageRouterModule constructor.
     * @param RouterBuilder $router
     */
    private function __construct(RouterBuilder $router)
    {
        $this->router = $router;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        $commandHandlers = $annotationRegistrationService->findRegistrationsFor(Aggregate::class, CommandHandler::class);
        $queryHandlers = $annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class);

        $messageClassNameToChannelNameMapping = [];
        foreach ($commandHandlers as $registration) {
            $messageClassName = AggregateMessagingModule::getMessageClassFor($registration);
            $inputChannel = AggregateMessagingModule::getMessageChannelFor($registration);

            $messageClassNameToChannelNameMapping[$messageClassName] = $inputChannel;
            $messageClassNameToChannelNameMapping[$inputChannel] = $inputChannel;
        }

        foreach ($queryHandlers as $registration) {
            $messageClassName = AggregateMessagingModule::getMessageClassFor($registration);
            $inputChannel = AggregateMessagingModule::getMessageChannelFor($registration);

            $messageClassNameToChannelNameMapping[$messageClassName] = $inputChannel;
            $messageClassNameToChannelNameMapping[$inputChannel] = $inputChannel;
        }

        return new self(
            RouterBuilder::createRouterFromObject(new AggregateMessageToChannelRouter($messageClassNameToChannelNameMapping), "route")
                ->withInputChannelName(AggregateMessage::AGGREGATE_SEND_MESSAGE_CHANNEL)
        );
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::AGGREGATE_ROUTER_MODULE;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects): void
    {
        $configuration
            ->registerMessageHandler($this->router);
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function afterConfigure(ReferenceSearchService $referenceSearchService): void
    {
        return;
    }
}