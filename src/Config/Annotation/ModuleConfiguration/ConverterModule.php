<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Converter;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ReferenceServiceConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceToCall;

/**
 * Class ConverterModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ConverterModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var ConverterBuilder[]
     */
    private $converterBuilders = [];

    /**
     * ConverterModule constructor.
     * @param array $converterBuilders
     */
    private function __construct(array $converterBuilders)
    {
        $this->converterBuilders = $converterBuilders;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService) : self
    {
        $registrations = $annotationRegistrationService->findRegistrationsFor(
            MessageEndpoint::class,
            Converter::class
        );

        $converterBuilders = [];

        foreach ($registrations as $registration) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $converterBuilders[] = ReferenceServiceConverterBuilder::create(
                  $registration->getReferenceName(),
                  $registration->getMethodName(),
                  $interfaceToCall->getFirstParameter()->getTypeDescriptor(),
                  $interfaceToCall->getReturnType()
            );
        }

        return new self($converterBuilders);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions): void
    {
        foreach ($this->converterBuilders as $converterBuilder) {
            $configuration->registerConverter($converterBuilder);
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "converterModule";
    }
}