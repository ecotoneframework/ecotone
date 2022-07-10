<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
class GatewayModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewayModule';

    /**
     * @var GatewayBuilder[]
     */
    private array $gatewayBuilders = [];

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param GatewayBuilder[] $gatewayBuilders
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

            $gatewayProxyBuilder = GatewayProxyBuilder::create($referenceName, $annotationRegistration->getClassName(), $annotationRegistration->getMethodName(), $annotation->getRequestChannel())
                ->withErrorChannel($annotation->getErrorChannel())
                ->withParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->getRequiredInterceptorNames())
                ->withReplyMillisecondTimeout($annotation->getReplyTimeoutInMilliseconds());

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
            } else if ($parameterAnnotation instanceof Header) {
                if ($parameterAnnotation->getExpression()) {
                    throw ConfigurationException::create("@Header annotation for Gateway ({$referenceName}) cannot be used with expression");
                } else {
                    return GatewayHeaderBuilder::create($interfaceParameter->getName(), $parameterAnnotation->getHeaderName());
                }
            } else if ($parameterAnnotation instanceof Headers) {
                return GatewayHeadersBuilder::create($interfaceParameter->getName());
            }
        }

        return null;
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
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $configuration->registerGatewayBuilder($gatewayBuilder);
        }
    }
}