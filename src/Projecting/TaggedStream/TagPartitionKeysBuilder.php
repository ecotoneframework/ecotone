<?php

declare(strict_types=1);

namespace Ecotone\Projecting\TaggedStream;

use Ecotone\EventSourcing\Mapping\EventMapper;
use Ecotone\EventSourcing\Tagging\EventTagRegistry;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class TagPartitionKeysBuilder implements ParameterConverterBuilder
{
    public function __construct(
        private string $parameterName,
        private string $tag,
    ) {
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(TagPartitionKeysConverter::class, [
            new Reference(EventTagRegistry::class),
            $this->tag,
            new Definition(PayloadConverter::class, [
                new Reference(ConversionService::REFERENCE_NAME),
                new Reference(EventMapper::class),
                $interfaceToCall->toString(),
                $this->parameterName,
                Type::object(),
            ]),
        ]);
    }
}
