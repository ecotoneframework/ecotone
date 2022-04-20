<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
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
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
class SerializerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewaySerializerModule';
    const ECOTONE_FROM_PHP_CHANNEL = "ecotone.serializer.convert_from";
    const ECOTONE_TO_PHP_CHANNEL = "ecotone.serializer.convert_to";

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $configuration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    Serializer::class,
                    Serializer::class,
                    "convertFromPHP",
                    self::ECOTONE_FROM_PHP_CHANNEL
                )->withParameterConverters([
                    GatewayPayloadBuilder::create("data"),
                    GatewayHeaderBuilder::create("targetMediaType", SerializerHandler::MEDIA_TYPE)
                ])
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    Serializer::class,
                    Serializer::class,
                    "convertToPHP",
                    self::ECOTONE_TO_PHP_CHANNEL
                )->withParameterConverters([
                    GatewayPayloadBuilder::create("data"),
                    GatewayHeaderBuilder::create("sourceMediaType", SerializerHandler::MEDIA_TYPE),
                    GatewayHeaderBuilder::create("targetType", SerializerHandler::TARGET_TYPE)
                ])
            )
            ->registerMessageHandler(
                SerializerHandlerBuilder::createFromPHP()
                    ->withInputChannelName(self::ECOTONE_FROM_PHP_CHANNEL)
            )
            ->registerMessageHandler(
                SerializerHandlerBuilder::createToPHP()
                    ->withInputChannelName(self::ECOTONE_TO_PHP_CHANNEL)
            );;
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }
}