<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use function class_exists;

use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDefinitionException;

use function interface_exists;

/**
 * Parser for complex type strings like "array<int, iterable<MyClass<string>>>"
 */
class TypeParser
{
    private const OPEN_ANGLE = '<';
    private const CLOSE_ANGLE = '>';
    private const COMMA = ',';
    private const OPEN_BRACKET = '[';
    private const CLOSE_BRACKET = ']';
    private const OPEN_BRACE = '{';
    private const CLOSE_BRACE = '}';
    private const COLON = ':';
    private const UNION = '|';

    private array $tokens;
    private int $currentTokenIndex;
    private string $originalExpression;
    private ?TypeContext $context;

    public function __construct(string $expression, ?TypeContext $context = null)
    {
        $this->originalExpression = trim($expression);
        $this->tokens = $this->getTokens($this->originalExpression);
        $this->context = $context;
    }

    public function parse(): Type
    {
        if (empty($this->tokens)) {
            return new BuiltinType(TypeIdentifier::ANYTHING);
        }
        $this->currentTokenIndex = 0;

        $parsed = $this->parseUnion();

        return $parsed;
    }

    private function nextToken(): ?string
    {
        if ($this->currentTokenIndex < count($this->tokens)) {
            return $this->tokens[$this->currentTokenIndex++];
        }
        return null;
    }

    private function peekToken(): ?string
    {
        if ($this->currentTokenIndex < count($this->tokens)) {
            return $this->tokens[$this->currentTokenIndex];
        }
        return null;
    }

    private function parseUnion(): Type
    {
        $isNullable = false;
        if ($this->peekToken() === '?') {
            $isNullable = true;
            $this->nextToken(); // consume the '?'
        }

        $left = $this->parseType();

        $types = [$left];
        while ($this->peekToken() === self::UNION) {
            $this->nextToken(); // consume the '|'
            $types[] = $this->parseType();
        }

        if ($isNullable) {
            $types[] = new BuiltinType(TypeIdentifier::NULL);
        }

        return UnionType::createWith($types);
    }

    private function parseType(): Type
    {
        $token = $this->nextToken();

        if ($token === null) {
            throw TypeDefinitionException::create("Error while parsing '{$this->originalExpression}'. Unexpected end of expression");
        }

        // Check if this is an array shape (array{...})
        if ($token === 'array' && $this->peekToken() === self::OPEN_BRACE) {
            $this->nextToken(); // consume the '{'
            try {
                $shape = $this->parseArrayShape();
                $this->expect(self::CLOSE_BRACE);
            } catch (TypeDefinitionException $e) {
                if ($this->peekToken() === null) {
                    // We are at the end of the expression, probably a multiline type that is not handled by TypeResolver
                    // for backward compatibility, we ignore and return an array
                    return new BuiltinType(TypeIdentifier::ARRAY);
                } else {
                    throw $e;
                }
            }

            return new ArrayShapeType($shape);
        }

        // Check if this is a generic type (has angle brackets)
        if ($this->peekToken() === self::OPEN_ANGLE) {
            $this->nextToken(); // consume the '<'
            $genericTypes = $this->parseGenericTypes();
            $this->expect(self::CLOSE_ANGLE);

            return $this->createGenericType($token, $genericTypes);
        }

        // Check if this is an array type with [] syntax
        if ($this->peekToken() === self::OPEN_BRACKET) {
            $this->nextToken(); // consume the '['
            $this->expect(self::CLOSE_BRACKET);

            // Convert [] syntax to array<...> syntax
            // When parsing array syntax, we should allow unknown types
            return $this->createGenericType('array', [$this->createSimpleType($token)]);
        }

        return $this->createSimpleType($token);
    }

    private function parseGenericTypes(): array
    {
        $types = [];

        while (true) {
            try {
                $types[] = $this->parseUnion();
            } catch (TypeDefinitionException $e) {
                $types[] = new BuiltinType(TypeIdentifier::ANYTHING);
            }

            if ($this->peekToken() === self::COMMA) {
                $this->nextToken(); // consume the ','
            } else {
                break;
            }
        }

        return $types;
    }

    private function createSimpleType(string $typeName): Type
    {
        $typeName = trim($typeName);

        // Handle builtin types
        if ($typeIdentifier = $this->getTypeIdentifier($typeName)) {
            return new BuiltinType($typeIdentifier);
        }

        // Handle type keywords (self, static, this, parent)
        if (in_array($typeName, ['self', 'static', 'parent'], true)) {
            $resolvedType = $this->context?->resolveKeyword($typeName);
            if ($resolvedType === null) {
                throw TypeDefinitionException::create("Error while parsing '{$this->originalExpression}'. Cannot resolve keyword '{$typeName}' - no context available");
            }
            // Keywords are already fully resolved, don't apply namespace resolution
            $resolvedClassName = $resolvedType;
        } else {
            // Resolve class name using context (aliases, namespace)
            if ($this->context) {
                $resolvedClassName = $this->context->resolveClassName($typeName);
            } else {
                $resolvedClassName = $typeName;
            }
        }

        // Handle class names
        if (class_exists($resolvedClassName) || interface_exists($resolvedClassName)) {
            return new ObjectType($resolvedClassName);
        } else {
            throw TypeDefinitionException::create("Error while parsing '{$this->originalExpression}'. Unknown type or class '{$typeName}' (resolved to '{$resolvedClassName}')");
        }
    }

    private function createGenericType(string $typeName, array $genericTypes): Type
    {
        $typeName = trim($typeName);
        $baseType = $this->createSimpleType($typeName);

        if ($baseType instanceof ObjectType || ($baseType instanceof BuiltinType && $baseType->isIterable())) {
            return GenericType::from($baseType, ...$genericTypes);
        } else {
            throw TypeDefinitionException::create("Error while parsing '{$this->originalExpression}'. Only collection types and object types can be generic, got '{$typeName}'");
        }
    }

    private function getTypeIdentifier(string $typeName): ?TypeIdentifier
    {
        return match ($typeName) {
            'int', 'integer' => TypeIdentifier::INTEGER,
            'float', 'double' => TypeIdentifier::FLOAT,
            'string' => TypeIdentifier::STRING,
            'bool', 'boolean' => TypeIdentifier::BOOL,
            'true' => TypeIdentifier::TRUE,
            'false' => TypeIdentifier::FALSE,
            'array' => TypeIdentifier::ARRAY,
            'iterable' => TypeIdentifier::ITERABLE,
            'object' => TypeIdentifier::OBJECT,
            'callable' => TypeIdentifier::CALLABLE,
            'resource' => TypeIdentifier::RESOURCE,
            'void' => TypeIdentifier::VOID,
            'null' => TypeIdentifier::NULL,
            'never' => TypeIdentifier::NEVER,
            'mixed' => TypeIdentifier::ANYTHING,
            default => null,
        };
    }

    private function expect(?string $expectedToken): void
    {
        $token = $this->nextToken();
        if ($token !== $expectedToken) {
            throw TypeDefinitionException::create("Error while parsing '{$this->originalExpression}'. Expected '$expectedToken', got '$token'");
        }
    }

    private function getTokens(string $expression): array
    {
        // Match "<", ">", ",", "[", "]", "{", "}", ":", "|", "?", or whitespace
        $pattern = '/(<|>|,|\[|\]|\{|\}|:|\||\?|\s+)/';

        $parts = preg_split($pattern, $expression, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $parts = array_filter(
            array_map('trim', $parts),
            fn ($token) => $token !== ''
        );

        return array_values($parts);
    }

    /**
     * Parse array shape like {name: string, age: int}
     * @return array<string, Type>
     */
    private function parseArrayShape(): array
    {
        $shape = [];

        // Handle empty array shape
        if ($this->peekToken() === self::CLOSE_BRACE) {
            return $shape;
        }

        do {
            // Parse field name
            $fieldName = $this->nextToken();
            if ($fieldName === null) {
                throw TypeDefinitionException::create("Error while parsing '{$this->originalExpression}'. Expected field name in array shape");
            }

            // Expect colon
            $this->expect(self::COLON);

            // Parse field type
            $fieldType = $this->parseUnion();

            $shape[$fieldName] = $fieldType;

            // Check if there are more fields
            if ($this->peekToken() === self::COMMA) {
                $this->nextToken(); // consume the ','
            } else {
                break;
            }
        } while (true);

        return $shape;
    }
}
