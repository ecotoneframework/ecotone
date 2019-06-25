<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycleBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class ConsumerRegisterConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var array|ConsumerLifecycleBuilder[]
     */
    private $messageHandlerBuilders;

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param ConsumerLifecycleBuilder[] $messageHandlerBuilders
     */
    private function __construct(array $messageHandlerBuilders)
    {
        $this->messageHandlerBuilders = $messageHandlerBuilders;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        $consumerBuilders = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, static::getConsumerAnnotation()) as $annotationRegistration) {
            $consumerBuilders[] = static::createConsumerFrom($annotationRegistration);
        }

        return new static($consumerBuilders);
    }

    /**
     * @return string
     */
    public static abstract function getConsumerAnnotation(): string;

    /**
     * @param AnnotationRegistration $annotationRegistration
     * @return ConsumerLifecycleBuilder
     */
    public static abstract function createConsumerFrom(AnnotationRegistration $annotationRegistration): ConsumerLifecycleBuilder;

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $configuration->registerConsumer($messageHandlerBuilder);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }
}