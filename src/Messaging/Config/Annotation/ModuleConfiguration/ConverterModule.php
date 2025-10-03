<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Converter;
use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverter;
use Ecotone\Messaging\Conversion\StaticCallConverter;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Support\InvalidArgumentException;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class ConverterModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * ConverterModule constructor.
     *
     * @param CompilableBuilder[] $converterBuilders
     */
    private function __construct(private array $converterBuilders)
    {
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

            if (! $interfaceToCall->hasSingleParameter()) {
                throw InvalidArgumentException::create("Converter should have only single parameter: {$interfaceToCall}");
            }
            if ($interfaceToCall->getReturnType()->isVoid()) {
                throw InvalidArgumentException::create("Converter cannot have void return type: {$interfaceToCall}");
            }
            if ($interfaceToCall->getReturnType()->isUnionType()) {
                throw InvalidArgumentException::create("Converter cannot have union type as parameter: {$interfaceToCall}");
            }
            if ($interfaceToCall->isStaticallyCalled()) {
                $converterBuilders[] = new Definition(StaticCallConverter::class, [
                    $interfaceToCall->getInterfaceName(),
                    $interfaceToCall->getMethodName(),
                    $interfaceToCall->getFirstParameter()->getTypeDescriptor(),
                    $interfaceToCall->getReturnType(),
                ]);
            } else {
                $converterBuilders[] = new Definition(ReferenceServiceConverter::class, [
                    new Reference(AnnotatedDefinitionReference::getReferenceFor($registration)),
                    $registration->getMethodName(),
                    $interfaceToCall->getFirstParameter()->getTypeDescriptor(),
                    $interfaceToCall->getReturnType(),
                ]);
            }
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
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->converterBuilders as $converterBuilder) {
            $messagingConfiguration->registerConverter($converterBuilder);
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
