<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\LicensingException;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class MessagingGatewayModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewayModule';

    /**
     * @var GatewayProxyBuilder[]
     */
    private array $gatewayBuilders = [];

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param GatewayProxyBuilder[] $gatewayBuilders
     */
    private function __construct(array $gatewayBuilders)
    {
        $this->gatewayBuilders = $gatewayBuilders;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $gatewayBuilders = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods(MessageGateway::class) as $annotationRegistration) {
            /** @var MessageGateway $annotation */
            $annotation      = $annotationRegistration->getAnnotationForMethod();
            $referenceName   = AnnotatedDefinitionReference::getReferenceFor($annotationRegistration);
            $interfaceToCall = $interfaceToCallRegistry->getFor($annotationRegistration->getClassName(), $annotationRegistration->getMethodName());

            $parameterConverters = [];
            foreach ($interfaceToCall->getInterfaceParameters() as $interfaceParameter) {
                $converter = self::getConverterForParameter($interfaceParameter, $referenceName);
                if ($converter) {
                    $parameterConverters[] = $converter;
                }
            }

            $gatewayProxyBuilder = GatewayProxyBuilder::create($referenceName, $annotationRegistration->getClassName(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                ->withErrorChannel($annotation->getErrorChannel())
                ->withParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames())
                ->withReplyMillisecondTimeout($annotation->replyTimeoutInMilliseconds);

            if ($annotation->getReplyContentType()) {
                $gatewayProxyBuilder = $gatewayProxyBuilder->withReplyContentType($annotation->getReplyContentType());
            }

            $gatewayBuilders[] = $gatewayProxyBuilder;
        }

        return new self($gatewayBuilders);
    }

    private static function getConverterForParameter(InterfaceParameter $interfaceParameter, string $referenceName): ?GatewayParameterConverterBuilder
    {
        $annotations = $interfaceParameter->getAnnotations();

        foreach ($annotations as $parameterAnnotation) {
            if ($parameterAnnotation instanceof Payload) {
                if ($parameterAnnotation->getExpression()) {
                    return GatewayPayloadExpressionBuilder::create($interfaceParameter->getName(), $parameterAnnotation->getExpression());
                } else {
                    return GatewayPayloadBuilder::create($interfaceParameter->getName());
                }
            } elseif ($parameterAnnotation instanceof Header) {
                if ($parameterAnnotation->getExpression()) {
                    throw ConfigurationException::create("@Header annotation for Gateway ({$referenceName}) cannot be used with expression");
                } else {
                    return GatewayHeaderBuilder::create($interfaceParameter->getName(), $parameterAnnotation->getHeaderName());
                }
            } elseif ($parameterAnnotation instanceof Headers) {
                return GatewayHeadersBuilder::create($interfaceParameter->getName());
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            /** @var Asynchronous[] $asynchronous */
            $asynchronous = $interfaceToCallRegistry->getFor($gatewayBuilder->getInterfaceName(), $gatewayBuilder->getRelatedMethodName())->getAnnotationsByImportanceOrder(Type::attribute(Asynchronous::class));
            if ($asynchronous && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
                throw LicensingException::create("Gateway {$gatewayBuilder->getInterfaceName()}::{$gatewayBuilder->getRelatedMethodName()} is marked as asynchronous. This functionality is available as part of Ecotone Enterprise. See https://docs.ecotone.tech/enterprise for details.");
            }
            $errorChannel = $interfaceToCallRegistry->getFor($gatewayBuilder->getInterfaceName(), $gatewayBuilder->getRelatedMethodName())->getAnnotationsByImportanceOrder(Type::attribute(ErrorChannel::class));
            if ($errorChannel && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
                throw LicensingException::create(ErrorChannelExceptionMessages::gatewayErrorChannelRequiresEnterprise($gatewayBuilder->getInterfaceName(), $gatewayBuilder->getRelatedMethodName()));
            }
            $delayedRetry = $interfaceToCallRegistry->getFor($gatewayBuilder->getInterfaceName(), $gatewayBuilder->getRelatedMethodName())->getAnnotationsByImportanceOrder(Type::attribute(\Ecotone\Api\Attribute\DelayedRetry::class));
            if ($delayedRetry && ! $messagingConfiguration->isRunningForEnterpriseLicence()) {
                throw LicensingException::create(ErrorChannelExceptionMessages::gatewayDelayedRetryRequiresEnterprise($gatewayBuilder->getInterfaceName(), $gatewayBuilder->getRelatedMethodName()));
            }

            $messagingConfiguration->registerGatewayBuilder($gatewayBuilder);
        }
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
