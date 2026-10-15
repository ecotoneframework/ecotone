<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\Reference as ReferenceAttribute;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

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
final class ClosureExpressionParameterConverterBuilder implements ParameterConverterBuilder
{
    private function __construct(
        private string $parameterName,
        private object $attributeWithExpression,
        private AttributeDeclaration $attributeDeclaration,
    ) {
    }

    public static function create(string $parameterName, object $attributeWithExpression, InterfaceToCall $interfaceToCall): self
    {
        return new self(
            $parameterName,
            $attributeWithExpression,
            new AttributeDeclaration(
                get_class($attributeWithExpression),
                $interfaceToCall->getInterfaceName(),
                $interfaceToCall->getMethodName(),
                $parameterName,
            ),
        );
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(ClosureExpressionParameterConverter::class, [
            AttributeExpressionExecutorCompiler::compile($this->attributeWithExpression, $this->attributeDeclaration, $interfaceToCall, $this->parameterName),
            ...$this->additionalContextSpecification($interfaceToCall),
        ]);
    }

    /**
     * @return array{0: ?string, 1: bool, 2: array}
     */
    private function additionalContextSpecification(InterfaceToCall $interfaceToCall): array
    {
        $staticAdditionalContext = [];
        if ($this->attributeWithExpression instanceof ReferenceAttribute) {
            $staticAdditionalContext['service'] = new Reference($this->attributeWithExpression->getReferenceName() ?: $interfaceToCall->getParameterWithName($this->parameterName)->getTypeHint());
        }

        return [
            $this->attributeWithExpression instanceof Header ? $this->attributeWithExpression->getHeaderName() : null,
            $this->attributeWithExpression instanceof Payload,
            $staticAdditionalContext,
        ];
    }
}
