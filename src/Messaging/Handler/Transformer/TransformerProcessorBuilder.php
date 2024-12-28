<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;

/**
 * licence Apache-2.0
 */
final class TransformerProcessorBuilder implements CompilableBuilder
{
    private function __construct(private TransformerBuilder $transformerBuilder)
    {

    }

    public static function create(TransformerBuilder $transformerBuilder): self
    {
        return new self($transformerBuilder);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return $this->transformerBuilder->compileProcessor($builder);
    }
}
