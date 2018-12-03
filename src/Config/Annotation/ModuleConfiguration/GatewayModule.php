<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayHeaderValue;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\CombinedGatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\CombinedGatewayDefinition;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;

/**
 * Class AnnotationGatewayConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationToBuilder
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
            /** @var \SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\Gateway $annotation */
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
                        ->withParameterToMessageConverters($parameterConverters)
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