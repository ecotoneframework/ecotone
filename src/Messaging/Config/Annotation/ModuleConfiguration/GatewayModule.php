<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;

/**
 * Class AnnotationGatewayConfiguration
 * @package Ecotone\Messaging\Config\Annotation\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
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
    public static function create(AnnotationFinder $annotationRegistrationService): \Ecotone\Messaging\Config\Annotation\AnnotationModule
    {
        $gatewayBuilders = [];
        foreach ($annotationRegistrationService->findAnnotatedMethods( MessageGateway::class) as $annotationRegistration) {
            /** @var \Ecotone\Messaging\Annotation\MessageGateway $annotation */
            $annotation = $annotationRegistration->getAnnotationForMethod();
            $referenceName = AnnotatedDefinitionReference::getReferenceFor($annotationRegistration);

            $parameterConverters = [];
            foreach ($annotation->parameterConverters as $parameterToMessage) {
                if ($parameterToMessage instanceof Payload) {
                    if ($parameterToMessage->expression) {
                        $parameterConverters[] = GatewayPayloadExpressionBuilder::create($parameterToMessage->parameterName, $parameterToMessage->expression);
                    } else {
                        $parameterConverters[] = GatewayPayloadBuilder::create($parameterToMessage->parameterName);
                    }
                } else if ($parameterToMessage instanceof Header) {
                    if ($parameterToMessage->expression) {
                        throw ConfigurationException::create("@Header annotation for Gateway ({$referenceName}) cannot be used with expression");
                    }else {
                        $parameterConverters[] = GatewayHeaderBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName);
                    }
                } else if ($parameterToMessage instanceof Headers) {
                    $parameterConverters[] = GatewayHeadersBuilder::create($parameterToMessage->parameterName);
                }else {
                    $converterClass = get_class($parameterToMessage);
                    throw new \InvalidArgumentException("Not known converters for gateway {$converterClass} for {$annotationRegistration->getClassName()}::{$annotationRegistration->getMethodName()}. Have you registered converter starting with name @MessageGateway(...) e.g. @MessageGatewayHeader?");
                }
            }

            $gatewayProxyBuilder = GatewayProxyBuilder::create($referenceName, $annotationRegistration->getClassName(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                ->withErrorChannel($annotation->errorChannel)
                ->withParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
                ->withReplyMillisecondTimeout($annotation->replyTimeoutInMilliseconds);

            if ($annotation->replyContentType) {
                $gatewayProxyBuilder = $gatewayProxyBuilder->withReplyContentType($annotation->replyContentType);
            }

            $gatewayBuilders[]   = $gatewayProxyBuilder;
        }

        return new self($gatewayBuilders);
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
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $configuration->registerGatewayBuilder($gatewayBuilder);
        }
    }
}