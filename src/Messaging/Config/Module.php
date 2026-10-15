<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

/**
 * Interface ExternalConfiguration
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface Module
{
    /**
     * In here you can register all message handlers, gateways, message channels
     *
     * @param object[]                     $extensionObjects
     * @param ModuleReferenceSearchService $moduleReferenceSearchService
     *
     * @return void
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void;


    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions /* , InterfaceToCallRegistry $interfaceToCallRegistry  */): array;

    public function getModulePackageName(): string;
}
