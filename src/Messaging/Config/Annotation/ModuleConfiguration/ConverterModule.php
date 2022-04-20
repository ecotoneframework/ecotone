<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterBuilder;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
class ConverterModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var ConverterBuilder[]
     */
    private array $converterBuilders = [];

    /**
     * ConverterModule constructor.
     *
     * @param array $converterBuilders
     */
    private function __construct(array $converterBuilders)
    {
        $this->converterBuilders = $converterBuilders;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $registrations = $annotationRegistrationService->findAnnotatedMethods(Converter::class);

        $converterBuilders = [];

        foreach ($registrations as $registration) {
            $interfaceToCall     = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());
            $converterBuilders[] = ReferenceServiceConverterBuilder::create(
                AnnotatedDefinitionReference::getReferenceFor($registration),
                $registration->getMethodName(),
                $interfaceToCall->getFirstParameter()->getTypeDescriptor(),
                $interfaceToCall->getReturnType()
            );
        }

        $registrations = $annotationRegistrationService->findAnnotatedClasses(MediaTypeConverter::class);

        foreach ($registrations as $registration) {
            $converterBuilders[] = ConverterReferenceBuilder::create(AnnotatedDefinitionReference::getReferenceForClassName($annotationRegistrationService, $registration));
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
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->converterBuilders as $converterBuilder) {
            $configuration->registerConverter($converterBuilder);
        }
    }
}