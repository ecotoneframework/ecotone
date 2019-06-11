<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;

/**
 * Class BaseAnnotationConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class MessageHandlerRegisterConfiguration extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var array|MessageHandlerBuilder[]
     */
    private $messageHandlerBuilders;

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param MessageHandlerBuilder[] $messageHandlerBuilders
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
        $messageHandlerBuilders = [];
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, static::getMessageHandlerAnnotation()) as $annotationRegistration) {
            $annotation = $annotationRegistration->getAnnotationForMethod();
            $messageHandlerBuilders[] = static::createMessageHandlerFrom($annotationRegistration)
                ->withMethodParameterConverters(
                    $parameterConverterFactory->createParameterConverters(InterfaceToCall::create($annotationRegistration->getClassName(), $annotationRegistration->getMethodName()), $annotation->parameterConverters)
                );
        }

        return new static($messageHandlerBuilders);
    }

    /**
     * @return string
     */
    public static abstract function getMessageHandlerAnnotation(): string;

    /**
     * @param AnnotationRegistration $annotationRegistration
     * @return MessageHandlerBuilderWithParameterConverters
     */
    public static abstract function createMessageHandlerFrom(AnnotationRegistration $annotationRegistration): MessageHandlerBuilderWithParameterConverters;

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->messageHandlerBuilders as $messageHandlerBuilder) {
            $configuration->registerMessageHandler($messageHandlerBuilder);
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