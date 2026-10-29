<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\Presend;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\Type;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class MethodInterceptorModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'methodInterceptorModule';

    /**
     * @param MethodInterceptorBuilder[]          $beforeSendInterceptors
     * @param MethodInterceptorBuilder[]          $preCallInterceptors
     * @param AroundInterceptorBuilder[] $aroundInterceptors
     * @param MethodInterceptorBuilder[]          $postCallInterceptors
     */
    private function __construct(
        private array $beforeSendInterceptors,
        private array $preCallInterceptors,
        private array $aroundInterceptors,
        private array $postCallInterceptors
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $parameterConverterFactory = ParameterConverterAnnotationFactory::create();
        /** @var AnnotatedFinding[] $methodsInterceptors */
        $methodsInterceptors = array_merge(
            $annotationRegistrationService->findAnnotatedMethods(Presend::class),
            $annotationRegistrationService->findAnnotatedMethods(Before::class),
            $annotationRegistrationService->findAnnotatedMethods(Around::class),
            $annotationRegistrationService->findAnnotatedMethods(After::class)
        );

        $beforeSendAnnotation = Type::attribute(Presend::class);
        $beforeAnnotation     = Type::attribute(Before::class);
        $aroundAnnotation     = Type::attribute(Around::class);
        $afterAnnotation      = Type::attribute(After::class);

        $beforeSendInterceptors = [];
        $preCallInterceptors    = [];
        $aroundInterceptors     = [];
        $postCallInterceptors   = [];
        foreach ($methodsInterceptors as $methodInterceptor) {
            $interceptorInterface = $interfaceToCallRegistry->getFor($methodInterceptor->getClassName(), $methodInterceptor->getMethodName());

            if ($interceptorInterface->hasMethodAnnotation($aroundAnnotation)) {
                /** @var Around $aroundInterceptor */
                $aroundInterceptor    = $interceptorInterface->getSingleMethodAnnotationOf($aroundAnnotation);
                $aroundInterceptors[] = AroundInterceptorBuilder::create(
                    AnnotatedDefinitionReference::getReferenceFor($methodInterceptor),
                    $interceptorInterface,
                    $aroundInterceptor->precedence,
                    $aroundInterceptor->pointcut,
                    $parameterConverterFactory->createParameterConverters($interceptorInterface)
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($beforeSendAnnotation)) {
                /** @var Presend $beforeInterceptor */
                $beforeSendInterceptor    = $interceptorInterface->getSingleMethodAnnotationOf($beforeSendAnnotation);
                $beforeSendInterceptors[] = MethodInterceptorBuilder::create(
                    new Reference(AnnotatedDefinitionReference::getReferenceFor($methodInterceptor)),
                    $interceptorInterface,
                    $beforeSendInterceptor->precedence,
                    $beforeSendInterceptor->pointcut,
                    $beforeSendInterceptor->changeHeaders,
                    $parameterConverterFactory->createParameterConverters($interceptorInterface),
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($beforeAnnotation)) {
                /** @var Before $beforeInterceptor */
                $beforeInterceptor     = $interceptorInterface->getSingleMethodAnnotationOf($beforeAnnotation);
                $preCallInterceptors[] = MethodInterceptorBuilder::create(
                    new Reference(AnnotatedDefinitionReference::getReferenceFor($methodInterceptor)),
                    $interceptorInterface,
                    $beforeInterceptor->precedence,
                    $beforeInterceptor->pointcut,
                    $beforeInterceptor->changeHeaders,
                    $parameterConverterFactory->createParameterConverters($interceptorInterface),
                );
            }

            if ($interceptorInterface->hasMethodAnnotation($afterAnnotation)) {
                /** @var After $afterInterceptor */
                $afterInterceptor       = $interceptorInterface->getSingleMethodAnnotationOf($afterAnnotation);
                $postCallInterceptors[] = MethodInterceptorBuilder::create(
                    new Reference(AnnotatedDefinitionReference::getReferenceFor($methodInterceptor)),
                    $interceptorInterface,
                    $afterInterceptor->precedence,
                    $afterInterceptor->pointcut,
                    $afterInterceptor->changeHeaders,
                    $parameterConverterFactory->createParameterConverters($interceptorInterface),
                );
            }
        }

        return new self($beforeSendInterceptors, $preCallInterceptors, $aroundInterceptors, $postCallInterceptors);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->beforeSendInterceptors as $interceptor) {
            $messagingConfiguration->registerBeforeSendInterceptor($interceptor);
        }
        foreach ($this->preCallInterceptors as $preCallInterceptor) {
            $messagingConfiguration->registerBeforeMethodInterceptor($preCallInterceptor);
        }
        foreach ($this->aroundInterceptors as $aroundInterceptorReference) {
            $messagingConfiguration->registerAroundMethodInterceptor($aroundInterceptorReference);
        }
        foreach ($this->postCallInterceptors as $postCallInterceptor) {
            $messagingConfiguration->registerAfterMethodInterceptor($postCallInterceptor);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
