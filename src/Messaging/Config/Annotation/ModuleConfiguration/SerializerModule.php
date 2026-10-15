<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Gateway\SerializerGateway;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Gateway\Converter\SerializerHandler;
use Ecotone\Messaging\Gateway\Converter\SerializerHandlerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class SerializerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = 'gatewaySerializerModule';
    public const ECOTONE_FROM_PHP_CHANNEL = 'ecotone.serializer.convert_from';
    public const ECOTONE_TO_PHP_CHANNEL = 'ecotone.serializer.convert_to';

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
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $messagingConfiguration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    SerializerGateway::class,
                    SerializerGateway::class,
                    'convertFromPHP',
                    self::ECOTONE_FROM_PHP_CHANNEL
                )->withParameterConverters([
                    GatewayPayloadBuilder::create('data'),
                    GatewayHeaderBuilder::create('targetMediaType', SerializerHandler::MEDIA_TYPE),
                ])
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create(
                    SerializerGateway::class,
                    SerializerGateway::class,
                    'convertToPHP',
                    self::ECOTONE_TO_PHP_CHANNEL
                )->withParameterConverters([
                    GatewayPayloadBuilder::create('data'),
                    GatewayHeaderBuilder::create('sourceMediaType', SerializerHandler::MEDIA_TYPE),
                    GatewayHeaderBuilder::create('targetType', SerializerHandler::TARGET_TYPE),
                ])
            )
            ->registerMessageHandler(
                SerializerHandlerBuilder::createFromPHP()
                    ->withInputChannelName(self::ECOTONE_FROM_PHP_CHANNEL)
            )
            ->registerMessageHandler(
                SerializerHandlerBuilder::createToPHP()
                    ->withInputChannelName(self::ECOTONE_TO_PHP_CHANNEL)
            );
        ;
    }

    /**
     * @inheritDoc
     */
    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
