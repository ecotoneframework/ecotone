<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;

/**
 * Class ConverterReferenceBuilder
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ConverterReferenceBuilder implements CompilableBuilder
{
    private function __construct(private string $referenceName)
    {
    }

    /**
     * @param string $referenceName
     * @return ConverterReferenceBuilder
     */
    public static function create(string $referenceName): self
    {
        return new self($referenceName);
    }

    public function compile(MessagingContainerBuilder $builder): Reference
    {
        return new Reference($this->referenceName);
    }
}
