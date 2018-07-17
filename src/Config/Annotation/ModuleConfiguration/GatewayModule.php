<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayHeader;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayPayload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\GatewayHeaderValue;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;

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
     * @var AnnotationRegistration[]
     */
    private $gatewayRegistrations;

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param AnnotationRegistration[] $gatewayRegistrations
     */
    private function __construct(array $gatewayRegistrations)
    {
        $this->gatewayRegistrations = $gatewayRegistrations;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self($annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, Gateway::class));
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
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver): void
    {
        foreach ($this->gatewayRegistrations as $annotationRegistration) {
            /** @var Gateway $annotation */
            $annotation = $annotationRegistration->getAnnotationForMethod();

            $parameterConverters = [];
            foreach ($annotation->parameterConverters as $parameterToMessage) {
                if ($parameterToMessage instanceof GatewayPayload) {
                    $parameterConverters[] = GatewayPayloadBuilder::create($parameterToMessage->parameterName);
                } else if ($parameterToMessage instanceof GatewayHeader) {
                    $parameterConverters[] = GatewayHeaderBuilder::create($parameterToMessage->parameterName, $parameterToMessage->headerName);
                } else if ($parameterToMessage instanceof GatewayHeaderValue) {
                    $parameterConverters[] = GatewayHeaderValueBuilder::create($parameterToMessage->headerName, $parameterToMessage->headerValue);
                }
            }

            $gateway = GatewayProxyBuilder::create($annotationRegistration->getReferenceName(), $annotationRegistration->getClassWithAnnotation(), $annotationRegistration->getMethodName(), $annotation->requestChannel)
                ->withMillisecondTimeout(1)
                ->withTransactionFactories($annotation->transactionFactories)
                ->withErrorChannel($annotation->errorChannel)
                ->withParameterToMessageConverters($parameterConverters);

            $configuration->registerGatewayBuilder($gateway);
        }
    }
}