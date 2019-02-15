<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayHeaderValue;
use SimplyCodedSoftware\Messaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\CombinedGatewayDefinition;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
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
        $gatewaysToBuild = [];
        /** @var CombinedGatewayDefinition[][] $gatewayDefinitions */
        $gatewayDefinitions = [];
        foreach ($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, Gateway::class) as $annotationRegistration) {
            /** @var \SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway $annotation */
            $annotation = $annotationRegistration->getAnnotationForMethod();

            $parameterConverters = [];
            foreach ($annotation->parameterConverters as $parameterToMessage) {
                if ($parameterToMessage instanceof GatewayPayload) {
                    if ($parameterToMessage->expression) {
                        $parameterConverters[] = GatewayPayloadExpressionBuilder::create($parameterToMessage->parameterName, $parameterToMessage->expression);
                    } else {
                        $parameterConverters[] = GatewayPayloadBuilder::create($parameterToMessage->parameterName);
                    }
                } else if ($parameterToMessage instanceof GatewayHeader) {
                    if ($parameterToMessage->expression) {
                        $parameterConverters[] = GatewayHeaderExpressionBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName, $parameterToMessage->expression);
                    } else {
                        $parameterConverters[] = GatewayHeaderBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName);
                    }
                } else if ($parameterToMessage instanceof GatewayHeaderValue) {
                    $parameterConverters[] = GatewayHeaderValueBuilder::create($parameterToMessage->headerName, $parameterToMessage->headerValue);
                }else {
                    $converterClass = get_class($parameterToMessage);
                    throw new \InvalidArgumentException("Not known converters for gateway {$converterClass}");
                }
            }

            $gatewayDefinitions[$annotationRegistration->getReferenceName()][] =
                CombinedGatewayDefinition::create(
                    GatewayProxyBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getClassName(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                        ->withTransactionFactories($annotation->transactionFactories)
                        ->withErrorChannel($annotation->errorChannel)
                        ->withParameterConverters($parameterConverters)
                        ->withReplyMillisecondTimeout($annotation->replyTimeoutInMilliseconds),
                    $annotationRegistration->getMethodName()
                );
        }

        foreach ($gatewayDefinitions as $gatewayDefinitionsPerReference) {
            $firstDefinition = $gatewayDefinitionsPerReference[0]->getGatewayBuilder();
            if (count($gatewayDefinitionsPerReference) == 1) {
                $gatewaysToBuild[] = $firstDefinition;
            } else {
                $gatewaysToBuild[] = CombinedGatewayBuilder::create(
                    $firstDefinition->getReferenceName(),
                    $firstDefinition->getInterfaceName(),
                    $gatewayDefinitionsPerReference
                );
            }
        }

        return new self($gatewaysToBuild);
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
    public function prepare(Configuration $configuration, array $extensionObjects, ConfigurableReferenceSearchService $configurableReferenceSearchService): void
    {
        foreach ($this->gatewayBuilders as $gatewayBuilder) {
            $configuration->registerGatewayBuilder($gatewayBuilder);
        }
    }
}