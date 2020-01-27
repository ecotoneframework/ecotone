<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Gateway\Converter\Serializer;
use Ecotone\Messaging\Gateway\Converter\SerializerHandler;
use Ecotone\Messaging\Gateway\Converter\SerializerHandlerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;

/**
 * @ModuleAnnotation()
 */
class SerializerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewaySerializerModule';
    const ECOTONE_SERIALIZER_CONVERT_CHANNEL = "ecotone.serializer.convert";

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
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
        $configuration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    Serializer::class,
                    Serializer::class,
                    "convertFromPHP",
                    self::ECOTONE_SERIALIZER_CONVERT_CHANNEL
                )->withParameterConverters([
                    GatewayPayloadBuilder::create("data"),
                    GatewayHeaderBuilder::create("mediaType", SerializerHandler::TARGET_MEDIA_TYPE)
                ])
            )
            ->registerMessageHandler(
                SerializerHandlerBuilder::create()
                    ->withInputChannelName(self::ECOTONE_SERIALIZER_CONVERT_CHANNEL)
            );
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }
}