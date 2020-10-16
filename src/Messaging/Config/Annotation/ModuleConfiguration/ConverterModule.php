<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\Converter;
use Ecotone\Messaging\Annotation\ConverterClass;
use Ecotone\Messaging\Annotation\MediaTypeConverter;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
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
    private array $converterBuilders = [];

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
    public static function create(AnnotationFinder $annotationRegistrationService) : self
    {
        $registrations = $annotationRegistrationService->findAnnotatedMethods(Converter::class);

        $converterBuilders = [];

        foreach ($registrations as $registration) {
            $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $converterBuilders[] = ReferenceServiceConverterBuilder::create(
                  AnnotatedDefinitionReference::getReferenceFor($registration),
                  $registration->getMethodName(),
                  $interfaceToCall->getFirstParameter()->getTypeDescriptor(),
                  $interfaceToCall->getReturnType()
            );
        }

        $registrations = $annotationRegistrationService->findAnnotatedClasses(MediaTypeConverter::class);

        foreach ($registrations as $registration) {
            /** @var MediaTypeConverter $mediaTypeConverter */
            $mediaTypeConverter = AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationRegistrationService, $registration, MediaTypeConverter::class);

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