<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;
use Ecotone\Messaging\Annotation\Converter;
use Ecotone\Messaging\Annotation\ConverterClass;
use Ecotone\Messaging\Annotation\MediaTypeConverter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Class ConverterModule
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration
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
            ConverterClass::class,
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

        $registrations = $annotationRegistrationService->getAllClassesWithAnnotation(MediaTypeConverter::class);

        foreach ($registrations as $registration) {
            /** @var MediaTypeConverter $mediaTypeConverter */
            $mediaTypeConverter = $annotationRegistrationService->getAnnotationForClass($registration, MediaTypeConverter::class);

            $converterBuilders[] = ConverterReferenceBuilder::create($mediaTypeConverter->referenceName ? $mediaTypeConverter->referenceName : $registration);
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
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
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