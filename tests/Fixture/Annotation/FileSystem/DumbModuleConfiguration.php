<?php

namespace Fixture\Annotation\FileSystem;

use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleMessagingConfiguration;

/**
 * Class DumbModuleConfiguration
 * @package Fixture\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class DumbModuleConfiguration implements ModuleMessagingConfiguration
{
    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration): void
    {

    }
}