<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container\Compiler;

use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\AttributeReference;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\LicensingException;

use function is_a;
use function is_array;
use function sprintf;

/**
 * licence Enterprise
 */
final class VerifyEnterpriseLicenceForClosureExpressions implements CompilerPass
{
    public function __construct(private bool $isRunningForEnterpriseLicence)
    {
    }

    public function process(ContainerBuilder $builder): void
    {
        if ($this->isRunningForEnterpriseLicence) {
            return;
        }

        foreach ($builder->getDefinitions() as $definition) {
            $this->verify($definition);
        }
    }

    private function verify(mixed $argument): void
    {
        if ($argument instanceof AttributeReference) {
            $this->verifyAttributeReference($argument);

            return;
        }
        if ($argument instanceof AttributeDefinition) {
            $this->verifyAttributeDefinition($argument);
        }
        if ($argument instanceof Definition) {
            $this->verify($argument->getArguments());
            foreach ($argument->getMethodCalls() as $methodCall) {
                $this->verify($methodCall->getArguments());
            }
        } elseif (is_array($argument)) {
            foreach ($argument as $value) {
                $this->verify($value);
            }
        }
    }

    private function verifyAttributeReference(AttributeReference $attributeReference): void
    {
        $attributeClassName = $attributeReference->getAttributeClass();
        if (! is_a($attributeClassName, WithExpression::class, true)) {
            return;
        }

        $attribute = AttributeDeclaration::resolveAttributeInstance($attributeClassName, $attributeReference->getClassName(), $attributeReference->getMethodName(), null, 0);
        if ($attribute->getExpression() instanceof Closure) {
            throw self::licensingException($attributeClassName, $attributeReference->getClassName(), $attributeReference->getMethodName());
        }
    }

    private function verifyAttributeDefinition(AttributeDefinition $attributeDefinition): void
    {
        $attributeClassName = $attributeDefinition->getClassName();
        if (! is_a($attributeClassName, WithExpression::class, true)) {
            return;
        }
        if (! $this->mayContainClosure($attributeDefinition)) {
            return;
        }

        if ($attributeDefinition->instance()->getExpression() instanceof Closure) {
            $declaration = $attributeDefinition->getDeclaration();
            throw self::licensingException($attributeClassName, $declaration?->getClassName(), $declaration?->getMethodName());
        }
    }

    private function mayContainClosure(AttributeDefinition $attributeDefinition): bool
    {
        if ($attributeDefinition->hasFactory()) {
            return $attributeDefinition->getFactory() === [AttributeDeclaration::class, 'resolveAttributeInstance'];
        }

        return $this->containsClosure($attributeDefinition->getArguments());
    }

    private function containsClosure(mixed $argument): bool
    {
        if ($argument instanceof Closure) {
            return true;
        }
        if (is_array($argument)) {
            foreach ($argument as $value) {
                if ($this->containsClosure($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function licensingException(string $attributeClassName, ?string $className, ?string $methodName): LicensingException
    {
        $declaredAt = $className !== null ? ' declared in ' . $className . ($methodName !== null ? '::' . $methodName : '') : '';

        return LicensingException::create(sprintf('Closure given as expression in %s attribute%s is available as part of Ecotone Enterprise.', $attributeClassName, $declaredAt));
    }
}
