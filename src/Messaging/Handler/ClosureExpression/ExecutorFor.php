<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
/**
 * Marks interceptor parameter to receive AttributeExpressionExecutor carrying related intercepted endpoint attribute
 * together with its compiled expression program.
 */
/**
 * licence Enterprise
 */
final class ExecutorFor
{
    public function __construct(public string $attributeClassName)
    {
    }
}
