<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp\Configuration;

use SimplyCodedSoftware\Amqp\AmqpAdmin;
use SimplyCodedSoftware\Amqp\AmqpBackedMessageChannelBuilder;
use SimplyCodedSoftware\Amqp\AmqpBackendMessageChannelConsumer;
use SimplyCodedSoftware\Amqp\AmqpBinding;
use SimplyCodedSoftware\Amqp\AmqpExchange;
use SimplyCodedSoftware\Amqp\AmqpQueue;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class AmqpModule
 * @package SimplyCodedSoftware\Amqp\Configuration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class AmqpModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "amqpModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $amqpExchanges = [];
        $amqpQueues = [];
        $amqpBindings = [];

        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof AmqpBackedMessageChannelBuilder) {
                $amqpQueues[] = AmqpQueue::createWith($extensionObject->getMessageChannelName());
            }else if ($extensionObject instanceof AmqpExchange) {
                $amqpExchanges[] = $extensionObject;
            }else if ($extensionObject instanceof AmqpQueue) {
                $amqpQueues[] = $extensionObject;
            }else if ($extensionObject instanceof AmqpBinding) {
                $amqpBindings[] = $extensionObject;
            }
        }

        $configuration->registerConsumerFactory(new AmqpBackendMessageChannelConsumer());
        $moduleReferenceSearchService->store(AmqpAdmin::REFERENCE_NAME, AmqpAdmin::createWith(
            $amqpExchanges, $amqpQueues, $amqpBindings
        ));
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof AmqpBackedMessageChannelBuilder
            || $extensionObject instanceof AmqpExchange
            || $extensionObject instanceof AmqpQueue
            || $extensionObject instanceof AmqpBinding;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}