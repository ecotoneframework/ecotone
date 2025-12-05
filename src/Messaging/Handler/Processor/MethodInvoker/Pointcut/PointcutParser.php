<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
class PointcutParser
{
    private const AND = '&&';
    private const OR = '||';
    private const OPEN_PAREN = '(';
    private const CLOSE_PAREN = ')';
    private const NOT = 'not(';
    private array $tokens;
    private int $currentTokenIndex;

    public function __construct(private string $expression)
    {
        $this->tokens = $this->getTokens($expression);
    }

    public function parse(): PointcutExpression
    {
        $this->currentTokenIndex = 0;

        $parsed = $this->parseAnd();

        if ($this->nextToken()) {
            throw IncorrectPointcutException::create("Error while parsing '{$this->expression}'. Expected end of expression, got '{$this->tokens[$this->currentTokenIndex - 1]}'");
        }

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

    private function parseAnd(): PointcutExpression
    {
        $left = $this->parseOr();

        while (($token = $this->peekToken()) !== null) {
            if ($token == self::AND) {
                $this->nextToken(); // consume the '&&'
                $right = $this->parseOr();
                $left = new PointcutAndExpression($left, $right);
            } else {
                break;
            }
        }

        return $left;
    }

    private function parseOr(): PointcutExpression
    {
        $left = $this->parsePrimary();

        while (($token = $this->peekToken()) !== null) {
            if ($token == self::OR) {
                $this->nextToken(); // consume the '||'
                $right = $this->parsePrimary();
                $left = new PointcutOrExpression($left, $right);
            } else {
                break;
            }
        }

        return $left;
    }

    private function parseNot(): PointcutExpression
    {
        $inner = $this->parseAnd();
        return new PointcutNotExpression($inner);
    }

    private function parsePrimary(): PointcutExpression
    {
        $token = $this->nextToken();

        if ($token === self::OPEN_PAREN) {
            $expr = $this->parseAnd();
            $this->expect(self::CLOSE_PAREN);
            return $expr;
        }

        if ($token === self::NOT) {
            $expr = $this->parseNot();
            $this->expect(self::CLOSE_PAREN);
            return $expr;
        }

        if ($token !== null) {
            if ($token === 'true') {
                return new PointcutBoolExpression(true);
            }
            if ($token === 'false') {
                return new PointcutBoolExpression(false);
            }
            if (strpos($token, '::') !== false) {
                [$class, $method] = explode('::', $token);

                return new PointcutMethodExpression($class, $method);
            }
            if (strpos($token, '*') !== false) {
                return new PointcutRegexExpression($token);
            }
            $type = Type::object($token);
            if ($type->exists()) {
                if ($type->isAttribute()) {
                    return new PointcutAttributeExpression($type);
                } else {
                    return new PointcutInterfaceExpression($type);
                }
            }
        }

        throw IncorrectPointcutException::create("Error while parsing '{$this->expression}'. '$token' is not a valid token");
    }

    private function expect(?string $expectedToken): void
    {
        $token = $this->nextToken();
        if ($token !== $expectedToken) {
            throw IncorrectPointcutException::create("Error while parsing '{$this->expression}'. Expected '$expectedToken', got '$token'");
        }
    }

    private function getTokens(string $expression): array
    {
        // Match "||", "&&", "not(" "(" or ")"
        $pattern = '/(\|\||&&|not\(|\(|\))/';

        $parts = preg_split($pattern, $expression, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $parts = array_filter(
            array_map('trim', $parts),
            fn ($token) => $token !== ''
        );

        $parts = $this->groupParenthesesAtEndOfExpression(array_values($parts));

        return array_values($parts);
    }

    /**
     * It will drop a close parenthese directly following an open parenthesis
     * allowing to declare a pointcut like "Class::method()" instead of "Class::method"
     * Example:
     * $parts = ["Class::method", "(", ")"]
     * Result: ["Class::method"]
     */
    private function groupParenthesesAtEndOfExpression(array $parts): array
    {
        $filteredParts = [];
        for ($i = 0; $i < count($parts); $i++) {
            if ($parts[$i] === self::OPEN_PAREN && $i + 1 < count($parts) && $parts[$i + 1] === self::CLOSE_PAREN) {
                $i += 2;
            } else {
                $filteredParts[] = $parts[$i];
            }
        }
        return $filteredParts;
    }
}
