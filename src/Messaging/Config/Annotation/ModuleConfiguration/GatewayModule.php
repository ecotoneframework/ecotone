<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Headers;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\HeaderValue;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class GatewayModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewayModule';

    /**
     * @var GatewayBuilder[]
     */
    private $gatewayBuilders = [];

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
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        $gatewayBuilders = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, Gateway::class) as $annotationRegistration) {
            /** @var \SimplyCodedSoftware\Messaging\Annotation\Gateway $annotation */
            $annotation = $annotationRegistration->getAnnotationForMethod();

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
                        $parameterConverters[] = GatewayHeaderExpressionBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName, $parameterToMessage->expression);
                    }else {
                        $parameterConverters[] = GatewayHeaderBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName);
                    }
                } else if ($parameterToMessage instanceof Headers) {
                    $parameterConverters[] = GatewayHeadersBuilder::create($parameterToMessage->parameterName);
                } else if ($parameterToMessage instanceof HeaderValue) {
                    $parameterConverters[] = GatewayHeaderValueBuilder::create($parameterToMessage->headerName, $parameterToMessage->headerValue);
                }else {
                    $converterClass = get_class($parameterToMessage);
                    throw new \InvalidArgumentException("Not known converters for gateway {$converterClass} for {$annotationRegistration->getClassName()}::{$annotationRegistration->getMethodName()}. Have you registered converter starting with name @Gateway(...) e.g. @GatewayHeader?");
                }
            }

            $gatewayBuilders[] = GatewayProxyBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getClassName(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                ->withErrorChannel($annotation->errorChannel)
                ->withParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
                ->withReplyMillisecondTimeout($annotation->replyTimeoutInMilliseconds);
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