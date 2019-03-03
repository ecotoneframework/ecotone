<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Annotation\Converter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Conversion\ReferenceServiceConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;

/**
 * Class ConverterModule
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration
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
            $interfaceToCall = InterfaceToCall::createWithoutCaching($registration->getClassName(), $registration->getMethodName());
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
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects): void
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