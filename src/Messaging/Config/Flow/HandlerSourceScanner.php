<?php

/*
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
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow;

use BackedEnum;
use Error;
use PhpToken;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionNamedType;

final class HandlerSourceScanner
{
    private const IGNORED_NAMES = ['parent', 'class'];
    private const IGNORED_TOKENS = [T_WHITESPACE => true, T_COMMENT => true, T_DOC_COMMENT => true, T_OPEN_TAG => true, T_INLINE_HTML => true];
    private const UNRESOLVED = ['kind' => 'unresolved', 'value' => ''];
    private const RETURN_OF = 'returnOf';
    private const NULL = ['kind' => 'null', 'value' => ''];
    private const MEMBER_ACCESS_TOKENS = [T_OBJECT_OPERATOR => true, T_NULLSAFE_OBJECT_OPERATOR => true, T_DOUBLE_COLON => true];
    private const EVERY_VARIABLE = '*';
    private const NAME_TOKENS = [T_STRING => true, T_NAME_QUALIFIED => true, T_NAME_FULLY_QUALIFIED => true, T_NAME_RELATIVE => true, T_STATIC => true];
    private const OPENS_METHOD_CALL = 'methodCall';
    private const OPENS_PLAIN_CALL = 'plainCall';
    private const OPENS_VALUE = 'value';

    /**
     * @param array<string, array{string, string}> $targets facts key => [class name, method name]
     * @return array<string, MethodSourceFacts>
     */
    public function scan(array $targets): array
    {
        $files = [];
        $methodFacts = [];
        $reflections = [];

        return $this->scanMethods($targets, [], $files, $methodFacts, $reflections);
    }

    /**
     * @param array<string, array{string, string}> $targets
     * @param array<string, bool> $visited
     * @param array<string, array{list<PhpToken>, array<int, int>, int, array<int, array{string, array<string, string>}>}> $files
     * @param array<string, MethodSourceFacts> $methodFacts
     * @param array<string, ReflectionMethod> $reflections
     * @return array<string, MethodSourceFacts>
     */
    private function scanMethods(array $targets, array $visited, array &$files, array &$methodFacts, array &$reflections): array
    {
        $facts = [];
        $methodsByFile = [];
        foreach ($targets as $key => [$className, $methodName]) {
            $methodKey = $className . '::' . $methodName;
            if (isset($methodFacts[$methodKey])) {
                $facts[$key] = $methodFacts[$methodKey];
                continue;
            }
            $method = $reflections[$methodKey] ??= new ReflectionMethod($className, $methodName);
            $fileName = $method->getFileName();
            if ($fileName === false || ! is_file($fileName) || ! is_readable($fileName) || $method->getStartLine() === false || $method->getEndLine() === false) {
                $facts[$key] = $methodFacts[$methodKey] = MethodSourceFacts::none();
                continue;
            }
            $methodsByFile[$fileName][] = [$key, $className, $method->getStartLine(), $method->getEndLine(), $method->getDeclaringClass()->getName(), $this->typedReceiversOf($method, $className)];
        }

        foreach ($methodsByFile as $fileName => $methods) {
            if (! isset($files[$fileName])) {
                $tokens = [];
                $firstIndexByLine = [];
                foreach (PhpToken::tokenize((string) file_get_contents($fileName)) as $token) {
                    if (isset(self::IGNORED_TOKENS[$token->id])) {
                        continue;
                    }
                    $firstIndexByLine[$token->line] ??= count($tokens);
                    $tokens[] = $token;
                }
                $contexts = $this->importContextsOf($tokens);
                $lastLine = $tokens === [] ? 0 : end($tokens)->line;
                $files[$fileName] = [$tokens, $firstIndexByLine, $lastLine, $contexts];
            }
            [$tokens, $firstIndexByLine, $lastLine, $contexts] = $files[$fileName];
            foreach ($methods as [$key, $className, $startLine, $endLine, $declaringClass, $typedReceivers]) {
                $start = $this->firstIndexAtOrAfter($firstIndexByLine, $startLine, $lastLine, count($tokens));
                $end = $this->firstIndexAtOrAfter($firstIndexByLine, $endLine + 1, $lastLine, count($tokens));
                [$namespace, $imports] = $this->contextAt($contexts, $start);
                $imports['self'] = $declaringClass;
                $facts[$key] = $methodFacts[$className . '::' . $targets[$key][1]] = $this->factsOf(array_slice($tokens, $start, $end - $start), $className, $namespace, $imports, $typedReceivers);
            }
        }

        foreach ($targets as $key => [$className, $methodName]) {
            $path = $visited + [$className . '::' . $methodName => true];
            $instantiations = $facts[$key]->instantiations;
            $calls = [];
            foreach ($facts[$key]->calls as $call) {
                $calls[] = $this->withReturnedLiterals($call, $path, $files, $methodFacts, $reflections);
            }
            $literals = $facts[$key]->stringLiterals;
            foreach ($calls as $call) {
                $helperKey = $className . '::' . $call['method'];
                if (! in_array($call['receiver'], ['this', 'self', 'static'], true) || isset($path[$helperKey]) || ! method_exists($className, $call['method'])) {
                    continue;
                }
                $helper = $this->scanMethods([$helperKey => [$className, $call['method']]], $path, $files, $methodFacts, $reflections)[$helperKey];
                $bindings = $this->bindingsOf($reflections[$helperKey] ??= new ReflectionMethod($className, $call['method']), $call);
                foreach (($call['insideAnotherArgument'] ?? false) ? [] : $helper->instantiations as $instantiation) {
                    if ($instantiation['enclosingCall'] === null) {
                        $instantiation['enclosingCall'] = $call['enclosingCall'];
                        $instantiation['receiver'] = $call['receiver'];
                    } else {
                        $instantiation['receiver'] = $this->boundReceiver($instantiation['receiver'], $bindings);
                    }
                    $instantiations[] = $instantiation;
                }
                foreach ($helper->calls as $helperCall) {
                    $calls[] = $this->boundCall($helperCall, $bindings);
                }
                $literals = [...$literals, ...$helper->stringLiterals];
            }
            $facts[$key] = new MethodSourceFacts($instantiations, $calls, array_values(array_unique($literals)), $facts[$key]->returnedLiteral);
        }

        return $facts;
    }

    /**
     * @return array<string, string> variable or this.property => class
     */
    private function typedReceiversOf(ReflectionMethod $method, string $className): array
    {
        $receivers = [];
        foreach ((new ReflectionClass($className))->getProperties() as $property) {
            $type = $property->getType();
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $receivers['this.' . $property->getName()] = $type->getName();
            }
        }
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $receivers['$' . $parameter->getName()] = $type->getName();
            }
        }

        return $receivers;
    }

    /**
     * @param array{receiver: string|null, method: string, enclosingCall: string|null, arguments: array<int|string, array{kind: string, value: string}>, literalArguments: list<string>, firstArgumentClass: string|null, unresolvedFrom: int|null} $call
     * @param array<string, bool> $visited
     * @param array<string, array{list<PhpToken>, array<int, int>, int, array<int, array{string, array<string, string>}>}> $files
     * @param array<string, MethodSourceFacts> $methodFacts
     * @param array<string, ReflectionMethod> $reflections
     * @return array{receiver: string|null, method: string, enclosingCall: string|null, arguments: array<int|string, array{kind: string, value: string}>, literalArguments: list<string>, firstArgumentClass: string|null, unresolvedFrom: int|null}
     */
    private function withReturnedLiterals(array $call, array $visited, array &$files, array &$methodFacts, array &$reflections): array
    {
        $arguments = [];
        foreach ($call['arguments'] as $position => $argument) {
            $arguments[$position] = $argument['kind'] === self::RETURN_OF ? $this->returnedLiteralOf($argument['value'], $visited, $files, $methodFacts, $reflections) : $argument;
        }

        return ['arguments' => $arguments, 'literalArguments' => $this->leadingLiteralsOf($arguments)] + $call;
    }

    /**
     * @param array<string, bool> $visited
     * @param array<string, array{list<PhpToken>, array<int, int>, int, array<int, array{string, array<string, string>}>}> $files
     * @param array<string, MethodSourceFacts> $methodFacts
     * @param array<string, ReflectionMethod> $reflections
     * @return array{kind: string, value: string}
     */
    private function returnedLiteralOf(string $method, array $visited, array &$files, array &$methodFacts, array &$reflections): array
    {
        [$class, $methodName] = explode('::', $method, 2);
        if (isset($visited[$method]) || ! method_exists($class, $methodName)) {
            return self::UNRESOLVED;
        }
        $reflection = $reflections[$method] ??= new ReflectionMethod($class, $methodName);
        if (! $reflection->isPublic() || $reflection->isAbstract() || ! ($reflection->isFinal() || $reflection->getDeclaringClass()->isFinal())) {
            return self::UNRESOLVED;
        }
        $returned = $this->scanMethods([$method => [$class, $methodName]], $visited, $files, $methodFacts, $reflections)[$method]->returnedLiteral;

        return $returned === null ? self::UNRESOLVED : ['kind' => 'literal', 'value' => $returned];
    }

    /**
     * @param array{arguments: array<int|string, array{kind: string, value: string}>, unresolvedFrom: int|null} $call
     * @return array<string, array{kind: string, value: string}|null>
     */
    private function bindingsOf(ReflectionMethod $helper, array $call): array
    {
        $bindings = [];
        foreach ($helper->getParameters() as $parameter) {
            $argument = $call['arguments'][$parameter->getName()] ?? $call['arguments'][$parameter->getPosition()] ?? null;
            if ($argument === null && $call['unresolvedFrom'] !== null && $parameter->getPosition() >= $call['unresolvedFrom']) {
                $argument = self::UNRESOLVED;
            }
            if ($argument === null && $parameter->isDefaultValueAvailable()) {
                $default = $parameter->getDefaultValue();
                $argument = match (true) {
                    is_string($default) => ['kind' => 'literal', 'value' => $default],
                    $default === null => self::NULL,
                    default => null,
                };
            }
            $bindings[$parameter->getName()] = $argument;
        }

        return $bindings;
    }

    /**
     * @param array{receiver: string|null, method: string, enclosingCall: string|null, arguments: array<int|string, array{kind: string, value: string}>, literalArguments: list<string>, firstArgumentClass: string|null, unresolvedFrom: int|null} $call
     * @param array<string, array{kind: string, value: string}|null> $bindings
     * @return array{receiver: string|null, method: string, enclosingCall: string|null, arguments: array<int|string, array{kind: string, value: string}>, literalArguments: list<string>, firstArgumentClass: string|null, unresolvedFrom: int|null}
     */
    private function boundCall(array $call, array $bindings): array
    {
        $arguments = [];
        foreach ($call['arguments'] as $key => $argument) {
            $arguments[$key] = $argument['kind'] === 'variable' ? ($bindings[$argument['value']] ?? self::UNRESOLVED) : $argument;
        }

        return ['receiver' => $this->boundReceiver($call['receiver'], $bindings), 'arguments' => $arguments, 'literalArguments' => $this->leadingLiteralsOf($arguments)] + $call;
    }

    /**
     * @param array<string, array{kind: string, value: string}|null> $bindings
     */
    private function boundReceiver(?string $receiver, array $bindings): ?string
    {
        if ($receiver === null || ! array_key_exists($receiver, $bindings)) {
            return $receiver;
        }

        return ($bindings[$receiver]['kind'] ?? null) === 'variable' ? $bindings[$receiver]['value'] : null;
    }

    /**
     * @param array<int|string, array{kind: string, value: string}> $arguments
     * @return list<string>
     */
    private function leadingLiteralsOf(array $arguments): array
    {
        $literals = [];
        for ($position = 0; ($arguments[$position]['kind'] ?? null) === 'literal'; $position++) {
            $literals[] = $arguments[$position]['value'];
        }

        return $literals;
    }

    /**
     * @param array<int, int> $firstIndexByLine
     */
    private function firstIndexAtOrAfter(array $firstIndexByLine, int $line, int $lastLine, int $tokenCount): int
    {
        for ($current = $line; $current <= $lastLine; $current++) {
            if (isset($firstIndexByLine[$current])) {
                return $firstIndexByLine[$current];
            }
        }

        return $tokenCount;
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array<int, array{string, array<string, string>}>
     */
    private function importContextsOf(array $tokens): array
    {
        $namespace = '';
        $imports = [];
        $braceDepth = 0;
        $namespaceDepth = 0;
        $contexts = [0 => ['', []]];
        foreach ($tokens as $index => $token) {
            $id = $token->id;
            if ($id === 123 || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $braceDepth++;
            } elseif ($id === 125) {
                $braceDepth--;
            } elseif ($id === T_NAMESPACE) {
                $name = $tokens[$index + 1] ?? null;
                $namespace = $name !== null && $this->isName($name) ? $name->text : '';
                $delimiter = $tokens[$index + ($namespace === '' ? 1 : 2)] ?? null;
                $namespaceDepth = $delimiter?->text === '{' ? 1 : 0;
                $imports = [];
                $contexts[$index] = [$namespace, $imports];
            } elseif ($id === T_USE && $braceDepth === $namespaceDepth && ($tokens[$index + 1] ?? null)?->text !== '(') {
                $imports = array_merge($imports, $this->importStatementAt($tokens, $index + 1));
                $contexts[$index] = [$namespace, $imports];
            }
        }

        return $contexts;
    }

    /**
     * @param array<int, array{string, array<string, string>}> $contexts
     * @return array{string, array<string, string>}
     */
    private function contextAt(array $contexts, int $index): array
    {
        $result = ['', []];
        foreach ($contexts as $position => $context) {
            if ($position > $index) {
                break;
            }
            $result = $context;
        }

        return $result;
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array<string, string>
     */
    private function importStatementAt(array $tokens, int $index): array
    {
        $imports = [];
        if (isset($tokens[$index]) && ($tokens[$index]->is(T_FUNCTION) || $tokens[$index]->is(T_CONST))) {
            return $imports;
        }
        $groupPrefix = '';
        $current = '';
        for ($i = $index; isset($tokens[$i]) && $tokens[$i]->text !== ';'; $i++) {
            $token = $tokens[$i];
            if ($this->isName($token)) {
                $current = $groupPrefix . ltrim($token->text, '\\');
                $imports[strtolower($this->shortName($current))] = $current;
            } elseif ($token->is(T_NS_SEPARATOR) || $token->text === '{') {
                $groupPrefix = rtrim($current, '\\') . '\\';
                unset($imports[strtolower($this->shortName($current))]);
            } elseif ($token->is(T_AS) && isset($tokens[$i + 1])) {
                unset($imports[strtolower($this->shortName($current))]);
                $imports[strtolower($tokens[$i + 1]->text)] = $current;
                $i++;
            }
        }

        return $imports;
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array<string, string> $imports
     */
    /**
     * @param array<string, string> $typedReceivers
     */
    private function factsOf(array $tokens, string $className, string $namespace, array $imports, array $typedReceivers): MethodSourceFacts
    {
        $instantiations = [];
        $calls = [];
        $literals = [];
        $enclosingCalls = [];
        $openings = [];
        $variables = [];
        $unresolvable = $this->unresolvableVariablesOf($tokens, $className);
        $returning = false;
        foreach ($tokens as $index => $token) {
            $id = $token->id;
            if ($id === T_RETURN || $id === T_YIELD) {
                $returning = true;
            } elseif ($id === 59) {
                $returning = false;
            }
            if ($id === T_VARIABLE && ($tokens[$index + 1] ?? null)?->id === 61) {
                unset($variables[$token->text]);
                if (($tokens[$index + 2] ?? null)?->is(T_NEW) && isset($tokens[$index + 3]) && $this->isName($tokens[$index + 3])) {
                    $variables[$token->text] = $this->resolve($tokens[$index + 3], $className, $namespace, $imports);
                }
            }
            $insideAnotherArgument = $this->isInsideAnotherArgument($openings);
            if ($id === T_NEW && isset($tokens[$index + 1]) && $this->isName($tokens[$index + 1])) {
                $resolved = $this->resolve($tokens[$index + 1], $className, $namespace, $imports);
                if ($resolved !== null && ! $insideAnotherArgument && ! ($tokens[$index - 1] ?? null)?->is(T_THROW)) {
                    $instantiations[] = ['class' => $resolved, 'returned' => $returning, 'enclosingCall' => $this->innermostCall($enclosingCalls)['method'] ?? null, 'receiver' => $this->innermostCall($enclosingCalls)['receiver'] ?? null];
                }
            } elseif ($id === T_DOUBLE_COLON && isset($tokens[$index - 1], $tokens[$index + 1], $tokens[$index + 2]) && $this->isName($tokens[$index - 1]) && $tokens[$index + 1]->is(T_STRING) && $tokens[$index + 2]->text === '(') {
                $resolved = $this->resolve($tokens[$index - 1], $className, $namespace, $imports);
                if ($resolved !== null && $resolved !== $className && ! $insideAnotherArgument) {
                    $instantiations[] = ['class' => $resolved, 'returned' => $returning, 'enclosingCall' => $this->innermostCall($enclosingCalls)['method'] ?? null, 'receiver' => $this->innermostCall($enclosingCalls)['receiver'] ?? null];
                }
            } elseif ($id === 40) {
                $call = $this->callBefore($tokens, $index);
                if ($call !== null) {
                    $argument = $tokens[$index + 1] ?? null;
                    if ($argument?->is(T_VARIABLE) && isset($variables[$argument->text]) && in_array($call['method'], ['publish', 'recordThat', 'send'], true)) {
                        array_unshift($instantiations, ['class' => $variables[$argument->text], 'returned' => false, 'enclosingCall' => $call['method'], 'receiver' => $call['receiver']]);
                    }
                    $calls[] = $call + ['enclosingCall' => $this->innermostCall($enclosingCalls)['method'] ?? null, 'receiver' => $this->innermostCall($enclosingCalls)['receiver'] ?? null, 'insideAnotherArgument' => $insideAnotherArgument] + $this->argumentsAt($tokens, $index + 1, $unresolvable, $className, $namespace, $imports, $typedReceivers);
                }
                $enclosingCalls[] = $call;
                $openings[] = $this->openingKindOf($tokens, $index, $call);
            } elseif ($id === 41) {
                array_pop($enclosingCalls);
                array_pop($openings);
            } elseif ($id === 91 || $id === T_ATTRIBUTE) {
                $openings[] = self::OPENS_VALUE;
            } elseif ($id === 93) {
                array_pop($openings);
            } elseif ($id === T_CONSTANT_ENCAPSED_STRING) {
                $literals[] = $this->literal($token);
            }
        }

        return new MethodSourceFacts($instantiations, $calls, array_values(array_unique($literals)), $this->returnedLiteralIn($tokens, $className, $namespace, $imports));
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array<string, string> $imports
     */
    private function returnedLiteralIn(array $tokens, string $className, string $namespace, array $imports): ?string
    {
        $last = count($tokens) - 1;
        $returns = array_keys(array_filter($tokens, fn (PhpToken $token) => $token->is(T_RETURN)));
        if (count($returns) !== 1 || ($tokens[$last] ?? null)?->text !== '}' || ($tokens[$last - 1] ?? null)?->text !== ';' || ($tokens[$returns[0] - 1] ?? null)?->text !== '{') {
            return null;
        }
        [$literal, $literalEnd] = $this->literalArgumentAt($tokens, $returns[0] + 1, $className, $namespace, $imports);

        return $literalEnd === $last - 2 ? $literal : null;
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array{receiver: string|null, method: string}|null
     */
    private function callBefore(array $tokens, int $parenthesisIndex): ?array
    {
        $name = $tokens[$parenthesisIndex - 1] ?? null;
        $operator = $tokens[$parenthesisIndex - 2] ?? null;
        if ($name === null || $operator === null || ! $name->is(T_STRING)) {
            return null;
        }
        if (! ($operator->is(T_OBJECT_OPERATOR) || $operator->is(T_NULLSAFE_OBJECT_OPERATOR) || $operator->is(T_DOUBLE_COLON))) {
            return null;
        }
        $receiver = null;
        $subject = $tokens[$parenthesisIndex - 3] ?? null;
        $subjectOperator = $tokens[$parenthesisIndex - 4] ?? null;
        $subjectOwner = $tokens[$parenthesisIndex - 5] ?? null;
        if ($operator->is(T_DOUBLE_COLON) && in_array(strtolower($subject?->text ?? ''), ['self', 'static'], true)) {
            $receiver = strtolower($subject->text);
        } elseif ($subject?->is(T_VARIABLE)) {
            $receiver = ltrim($subject->text, '$');
        } elseif ($subject?->is(T_STRING) && $subjectOperator?->is(T_OBJECT_OPERATOR) && $subjectOwner?->text === '$this') {
            $receiver = 'this.' . $subject->text;
        }

        return ['receiver' => $receiver, 'method' => $name->text];
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array{reassignedFrom: array<string, int>, shadows: list<array{string, int, int}>}
     */
    private function unresolvableVariablesOf(array $tokens, string $className): array
    {
        $reassignedFrom = array_map(fn () => 0, $this->assignedVariablesOf($tokens));
        foreach ($this->variablesPassedToUnfollowedCallsOf($tokens, $className) as $variable => $index) {
            $reassignedFrom[$variable] = min($reassignedFrom[$variable] ?? $index, $index);
        }

        return ['reassignedFrom' => $reassignedFrom, 'shadows' => $this->closureParametersOf($tokens)];
    }

    /**
     * @param array{reassignedFrom: array<string, int>, shadows: list<array{string, int, int}>} $unresolvable
     */
    private function isUnresolvableAt(array $unresolvable, string $variable, int $index): bool
    {
        foreach ($unresolvable['shadows'] as [$parameter, $start, $end]) {
            if ($parameter === $variable && $index >= $start && $index <= $end) {
                return true;
            }
        }

        return $index >= min($unresolvable['reassignedFrom'][$variable] ?? PHP_INT_MAX, $unresolvable['reassignedFrom'][self::EVERY_VARIABLE] ?? PHP_INT_MAX);
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array<string, int>
     */
    private function variablesPassedToUnfollowedCallsOf(array $tokens, string $className): array
    {
        $passed = [];
        $body = false;
        foreach ($tokens as $index => $token) {
            $body = $body || $token->text === '{';
            if (! $body || $token->text !== '(') {
                continue;
            }
            $call = $this->callBefore($tokens, $index);
            $function = $call === null ? $this->functionCalledAt($tokens, $index) : null;
            if ($call === null && $function === null) {
                continue;
            }
            $byReference = null;
            if ($call !== null && in_array($call['receiver'], ['this', 'self', 'static'], true) && method_exists($className, $call['method'])) {
                $byReference = $this->byReferenceParametersOf(new ReflectionMethod($className, $call['method']));
                if ($byReference === []) {
                    continue;
                }
            }
            if ($function === 'extract') {
                $passed[self::EVERY_VARIABLE] ??= $index;
            }
            for ($argument = $index + 1, $position = 0; isset($tokens[$argument]) && $tokens[$argument]->text !== ')'; $position++) {
                $parameter = $position;
                if ($tokens[$argument]->is(T_STRING) && ($tokens[$argument + 1] ?? null)?->text === ':') {
                    $parameter = $tokens[$argument]->text;
                    $argument += 2;
                }
                $end = $this->argumentEndAt($tokens, $argument);
                $handsTheVariable = $tokens[$argument]->is(T_VARIABLE) && ! isset(self::MEMBER_ACCESS_TOKENS[($tokens[$argument + 1] ?? null)?->id]);
                if ($handsTheVariable && ($byReference === null || isset($byReference[$parameter]))) {
                    $passed[$tokens[$argument]->text] ??= ($end ?? $argument) + 1;
                }
                if ($end === null) {
                    break;
                }
                $argument = $tokens[$end]->text === ',' ? $end + 1 : $end;
            }
        }

        return $passed;
    }

    /**
     * @return array<int|string, true>
     */
    private function byReferenceParametersOf(ReflectionMethod $method): array
    {
        $byReference = [];
        foreach ($method->getParameters() as $parameter) {
            if ($parameter->isPassedByReference()) {
                $byReference[$parameter->getPosition()] = true;
                $byReference[$parameter->getName()] = true;
            }
        }

        return $byReference;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function functionCalledAt(array $tokens, int $parenthesisIndex): ?string
    {
        $name = $tokens[$parenthesisIndex - 1] ?? null;
        $before = $tokens[$parenthesisIndex - 2] ?? null;
        if ($name === null || ! ($name->is(T_STRING) || $name->is(T_NAME_FULLY_QUALIFIED) || $name->is(T_NAME_QUALIFIED))) {
            return null;
        }
        if ($before !== null && ($before->is(T_OBJECT_OPERATOR) || $before->is(T_NULLSAFE_OBJECT_OPERATOR) || $before->is(T_DOUBLE_COLON) || $before->is(T_FUNCTION) || $before->is(T_NEW))) {
            return null;
        }

        return strtolower(ltrim($name->text, '\\'));
    }

    /**
     * @param list<PhpToken> $tokens
     * @return list<array{string, int, int}>
     */
    private function closureParametersOf(array $tokens): array
    {
        $shadows = [];
        $body = false;
        foreach ($tokens as $index => $token) {
            $isClosure = $body && ($token->is(T_FUNCTION) || $token->is(T_FN));
            $body = $body || $token->text === '{';
            if (! $isClosure) {
                continue;
            }
            $open = ($tokens[$index + 1] ?? null)?->text === '&' ? $index + 2 : $index + 1;
            $close = $this->matchingCloseAt($tokens, $open);
            if (($tokens[$open] ?? null)?->text !== '(' || $close === null) {
                continue;
            }
            $end = $token->is(T_FN) ? $this->arrowFunctionEndAt($tokens, $close) : $this->closureBodyEndAt($tokens, $close);
            for ($parameter = $open + 1; $parameter < $close; $parameter++) {
                if ($tokens[$parameter]->is(T_VARIABLE)) {
                    $shadows[] = [$tokens[$parameter]->text, $open, $end];
                }
            }
        }

        return $shadows;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function matchingCloseAt(array $tokens, int $open): ?int
    {
        $depth = 0;
        for ($current = $open; isset($tokens[$current]); $current++) {
            $text = $tokens[$current]->text;
            if (in_array($text, ['(', '[', '{'], true) || $tokens[$current]->is(T_CURLY_OPEN) || $tokens[$current]->is(T_DOLLAR_OPEN_CURLY_BRACES)) {
                $depth++;
            } elseif (in_array($text, [')', ']', '}'], true) && --$depth === 0) {
                return $current;
            }
        }

        return null;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function closureBodyEndAt(array $tokens, int $parametersClose): int
    {
        for ($current = $parametersClose + 1; isset($tokens[$current]); $current++) {
            if ($tokens[$current]->text === '{') {
                return $this->matchingCloseAt($tokens, $current) ?? count($tokens) - 1;
            }
        }

        return count($tokens) - 1;
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function arrowFunctionEndAt(array $tokens, int $parametersClose): int
    {
        $depth = 0;
        for ($current = $parametersClose + 1; isset($tokens[$current]); $current++) {
            $text = $tokens[$current]->text;
            if (in_array($text, ['(', '[', '{'], true) || $tokens[$current]->is(T_CURLY_OPEN) || $tokens[$current]->is(T_DOLLAR_OPEN_CURLY_BRACES)) {
                $depth++;
            } elseif ($depth === 0 && in_array($text, [',', ')', ']', '}', ';'], true)) {
                return $current - 1;
            } elseif (in_array($text, [')', ']', '}'], true)) {
                $depth--;
            }
        }

        return count($tokens) - 1;
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array<string, bool>
     */
    private function assignedVariablesOf(array $tokens): array
    {
        $assigned = [];
        $body = false;
        foreach ($tokens as $index => $token) {
            $body = $body || $token->text === '{';
            if (! $body || ! $token->is(T_VARIABLE)) {
                continue;
            }
            $next = $tokens[$index + 1] ?? null;
            $previous = $tokens[$index - 1] ?? null;
            $assignedByOperator = $next !== null && ($next->text === '=' || str_ends_with($next->text, '=') && ! in_array($next->text, ['==', '===', '!=', '!==', '<=', '>=', '<=>'], true));
            $assignedByForeach = $previous !== null && ($previous->is(T_AS) || $previous->is(T_DOUBLE_ARROW) && ($tokens[$index - 3] ?? null)?->is(T_AS));
            if ($assignedByOperator || $assignedByForeach || $previous?->text === '&') {
                $assigned[$token->text] = true;
            }
        }
        foreach ($this->destructuringRangesOf($tokens) as [$open, $close]) {
            for ($index = $open; $index <= $close; $index++) {
                if ($tokens[$index]->is(T_VARIABLE)) {
                    $assigned[$tokens[$index]->text] = true;
                }
            }
        }

        return $assigned;
    }

    /**
     * @param list<PhpToken> $tokens
     * @return list<array{int, int}>
     */
    private function destructuringRangesOf(array $tokens): array
    {
        $ranges = [];
        $openers = [];
        foreach ($tokens as $index => $token) {
            if ($token->text === '[' || $token->text === '(') {
                $openers[] = $index;
            } elseif (($token->text === ']' || $token->text === ')') && $openers !== []) {
                $open = array_pop($openers);
                $before = $tokens[$open - 1] ?? null;
                $destructuring = $token->text === ']'
                    ? $before === null || ! ($before->is(T_VARIABLE) || $this->isName($before) || in_array($before->text, [']', ')', '}'], true))
                    : $before?->is(T_LIST) === true;
                $leadingIndex = $token->text === ')' ? $open - 2 : $open - 1;
                $foreachValue = ($tokens[$leadingIndex] ?? null)?->is(T_AS) === true
                    || ($tokens[$leadingIndex] ?? null)?->is(T_DOUBLE_ARROW) === true && ($tokens[$leadingIndex - 2] ?? null)?->is(T_AS) === true;
                $assignedTo = ($tokens[$index + 1] ?? null)?->text === '=' || $foreachValue;
                if ($destructuring && $assignedTo) {
                    $ranges[] = [$open, $index];
                }
            }
        }

        return $ranges;
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array{reassignedFrom: array<string, int>, shadows: list<array{string, int, int}>} $unresolvable
     * @param array<string, string> $imports
     * @param array<string, string> $typedReceivers
     * @return array{arguments: array<int|string, array{kind: string, value: string}>, literalArguments: list<string>, firstArgumentClass: string|null, unresolvedFrom: int|null}
     */
    private function argumentsAt(array $tokens, int $index, array $unresolvable, string $className, string $namespace, array $imports, array $typedReceivers): array
    {
        $first = $tokens[$index] ?? null;
        if ($first?->is(T_NEW) && isset($tokens[$index + 1]) && $this->isName($tokens[$index + 1])) {
            return ['arguments' => [], 'literalArguments' => [], 'firstArgumentClass' => $this->resolve($tokens[$index + 1], $className, $namespace, $imports), 'unresolvedFrom' => 0];
        }
        $arguments = [];
        $position = 0;
        $unresolvedFrom = null;
        while (isset($tokens[$index]) && $tokens[$index]->text !== ')') {
            if ($tokens[$index]->is(T_ELLIPSIS)) {
                $unresolvedFrom = $position;
                break;
            }
            $name = null;
            if ($tokens[$index]->is(T_STRING) && ($tokens[$index + 1] ?? null)?->text === ':') {
                $name = $tokens[$index]->text;
                $index += 2;
            }
            $end = $this->argumentEndAt($tokens, $index);
            if ($end === null) {
                $unresolvedFrom = $position;
                break;
            }
            $arguments[$name ?? $position] = $this->argumentValueAt($tokens, $index, $end, $unresolvable, $className, $namespace, $imports, $typedReceivers);
            if ($name === null) {
                $position++;
            }
            $index = $tokens[$end]->text === ',' ? $end + 1 : $end;
        }

        return ['arguments' => $arguments, 'literalArguments' => $this->leadingLiteralsOf($arguments), 'firstArgumentClass' => null, 'unresolvedFrom' => $unresolvedFrom];
    }

    /**
     * @param list<PhpToken> $tokens
     */
    private function argumentEndAt(array $tokens, int $index): ?int
    {
        $depth = 0;
        for ($current = $index; isset($tokens[$current]); $current++) {
            $token = $tokens[$current];
            if (in_array($token->text, ['(', '[', '{'], true) || $token->is(T_CURLY_OPEN) || $token->is(T_DOLLAR_OPEN_CURLY_BRACES) || $token->is(T_ATTRIBUTE)) {
                $depth++;
            } elseif ($depth === 0 && in_array($token->text, [',', ')'], true)) {
                return $current;
            } elseif (in_array($token->text, [')', ']', '}'], true)) {
                $depth--;
            }
        }

        return null;
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array{reassignedFrom: array<string, int>, shadows: list<array{string, int, int}>} $unresolvable
     * @param array<string, string> $imports
     * @param array<string, string> $typedReceivers
     * @return array{kind: string, value: string}
     */
    private function argumentValueAt(array $tokens, int $index, int $end, array $unresolvable, string $className, string $namespace, array $imports, array $typedReceivers): array
    {
        $token = $tokens[$index];
        if ($end === $index + 1 && $token->is(T_VARIABLE)) {
            return $token->text === '$this' || $this->isUnresolvableAt($unresolvable, $token->text, $index) ? self::UNRESOLVED : ['kind' => 'variable', 'value' => ltrim($token->text, '$')];
        }
        if ($end === $index + 1 && $token->is(T_STRING) && strtolower($token->text) === 'null') {
            return self::NULL;
        }
        [$receiverClass, $memberIndex] = $this->typedReceiverAt($tokens, $index, $unresolvable, $typedReceivers);
        if ($receiverClass !== null && $tokens[$memberIndex - 1]->is(T_OBJECT_OPERATOR) && $end === $memberIndex + 3 && $tokens[$memberIndex + 1]->text === '(' && $tokens[$memberIndex + 2]->text === ')') {
            return ['kind' => self::RETURN_OF, 'value' => $receiverClass . '::' . $tokens[$memberIndex]->text];
        }
        [$literal, $literalEnd] = $receiverClass !== null && $tokens[$memberIndex - 1]->is(T_DOUBLE_COLON)
            ? $this->overridableConstantAt($tokens, $receiverClass, $memberIndex)
            : $this->literalArgumentAt($tokens, $index, $className, $namespace, $imports);

        return $literal !== null && $literalEnd === $end - 1 ? ['kind' => 'literal', 'value' => $literal] : self::UNRESOLVED;
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array{reassignedFrom: array<string, int>, shadows: list<array{string, int, int}>} $unresolvable
     * @param array<string, string> $typedReceivers
     * @return array{string|null, int}
     */
    private function typedReceiverAt(array $tokens, int $index, array $unresolvable, array $typedReceivers): array
    {
        $token = $tokens[$index];
        if (! $token->is(T_VARIABLE)) {
            return [null, $index];
        }
        $receiver = $token->text;
        $memberIndex = $index + 2;
        if ($receiver === '$this' && ($tokens[$index + 1] ?? null)?->is(T_OBJECT_OPERATOR) && ($tokens[$index + 2] ?? null)?->is(T_STRING)) {
            $receiver = 'this.' . $tokens[$index + 2]->text;
            $memberIndex = $index + 4;
        } elseif ($this->isUnresolvableAt($unresolvable, $receiver, $index)) {
            return [null, $index];
        }
        $operator = $tokens[$memberIndex - 1] ?? null;
        if (! isset($typedReceivers[$receiver]) || ! ($operator?->is(T_OBJECT_OPERATOR) || $operator?->is(T_DOUBLE_COLON)) || ! ($tokens[$memberIndex] ?? null)?->is(T_STRING)) {
            return [null, $index];
        }

        return [$typedReceivers[$receiver], $memberIndex];
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array{string|null, int}
     */
    private function overridableConstantAt(array $tokens, string $class, int $nameIndex): array
    {
        $name = $tokens[$nameIndex]->text;
        if (! (class_exists($class) || interface_exists($class)) || ! defined($class . '::' . $name)) {
            return [null, $nameIndex];
        }
        $constant = new ReflectionClassConstant($class, $name);
        if (! $constant->isPublic() || ! ($constant->isFinal() || $constant->getDeclaringClass()->isFinal() || $constant->getDeclaringClass()->isEnum())) {
            return [null, $nameIndex];
        }

        return $this->constantValueAt($tokens, $class, $nameIndex);
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array<string, string> $imports
     * @return array{string|null, int}
     */
    private function literalArgumentAt(array $tokens, int $index, string $className, string $namespace, array $imports): array
    {
        $token = $tokens[$index];
        if ($token->is(T_CONSTANT_ENCAPSED_STRING)) {
            return [$this->literal($token), $index];
        }
        if (! $this->isName($token) || ! ($tokens[$index + 1] ?? null)?->is(T_DOUBLE_COLON) || ! ($tokens[$index + 2] ?? null)?->is(T_STRING)) {
            return [null, $index];
        }
        $class = $this->resolve($token, $className, $namespace, $imports);

        return $class === null ? [null, $index] : $this->constantValueAt($tokens, $class, $index + 2);
    }

    /**
     * @param list<PhpToken> $tokens
     * @return array{string|null, int}
     */
    private function constantValueAt(array $tokens, string $class, int $nameIndex): array
    {
        $constant = $class . '::' . $tokens[$nameIndex]->text;
        if (! defined($constant)) {
            return [null, $nameIndex];
        }
        try {
            $value = constant($constant);
        } catch (Error) {
            return [null, $nameIndex];
        }
        $end = $nameIndex;
        if ($value instanceof BackedEnum && ($tokens[$end + 1] ?? null)?->is(T_OBJECT_OPERATOR) && ($tokens[$end + 2] ?? null)?->text === 'value') {
            $value = $value->value;
            $end += 2;
        }

        return [is_string($value) ? $value : null, $end];
    }

    /**
     * @param list<self::OPENS_*> $openings
     */
    private function isInsideAnotherArgument(array $openings): bool
    {
        $buildsValue = false;
        for ($i = count($openings) - 1; $i >= 0; $i--) {
            if ($openings[$i] === self::OPENS_METHOD_CALL) {
                return $buildsValue;
            }
            $buildsValue = $buildsValue || $openings[$i] === self::OPENS_VALUE;
        }

        return false;
    }

    /**
     * @param list<PhpToken> $tokens
     * @param array{receiver: string|null, method: string}|null $call
     * @return self::OPENS_*
     */
    private function openingKindOf(array $tokens, int $parenthesisIndex, ?array $call): string
    {
        if (($tokens[$parenthesisIndex - 2] ?? null)?->is(T_NEW)) {
            return self::OPENS_VALUE;
        }
        if ($call === null) {
            return self::OPENS_PLAIN_CALL;
        }
        if (($tokens[$parenthesisIndex - 2] ?? null)?->is(T_DOUBLE_COLON) && $call['receiver'] === null) {
            return self::OPENS_VALUE;
        }

        return self::OPENS_METHOD_CALL;
    }

    /**
     * @param list<array{receiver: string|null, method: string}|null> $enclosingCalls
     * @return array{receiver: string|null, method: string}|null
     */
    private function innermostCall(array $enclosingCalls): ?array
    {
        for ($i = count($enclosingCalls) - 1; $i >= 0; $i--) {
            if ($enclosingCalls[$i] !== null) {
                return $enclosingCalls[$i];
            }
        }

        return null;
    }

    private function isName(PhpToken $token): bool
    {
        return isset(self::NAME_TOKENS[$token->id]);
    }

    /**
     * @param array<string, string> $imports
     */
    private function resolve(PhpToken $token, string $className, string $namespace, array $imports): ?string
    {
        $name = $token->text;
        if (strtolower($name) === 'self') {
            return $imports['self'] ?? $className;
        }
        if (strtolower($name) === 'static') {
            return $className;
        }
        if (in_array(strtolower($name), self::IGNORED_NAMES, true)) {
            return null;
        }
        if ($token->is(T_NAME_FULLY_QUALIFIED)) {
            return ltrim($name, '\\');
        }
        if ($token->is(T_NAME_RELATIVE)) {
            return $namespace . '\\' . substr($name, strlen('namespace\\'));
        }
        $firstSegment = strtolower(explode('\\', $name)[0]);
        if (isset($imports[$firstSegment])) {
            $rest = substr($name, strlen(explode('\\', $name)[0]));

            return $imports[$firstSegment] . $rest;
        }

        return $namespace === '' ? $name : $namespace . '\\' . $name;
    }

    private function literal(PhpToken $token): string
    {
        $text = substr($token->text, 1, -1);
        if ($token->text[0] === "'") {
            return str_replace(['\\\\', "\\'"], ['\\', "'"], $text);
        }

        return preg_replace_callback('~\\\\(?:u\{([0-9a-fA-F]+)\}|[nrtvef\\\\$"]|[0-7]{1,3}|x[0-9a-fA-F]{1,2})~', function (array $match): string {
            if (($match[1] ?? '') !== '') {
                $codePoint = hexdec($match[1]);
                $escaped = $codePoint <= 0xffff
                    ? sprintf('"\u%04x"', $codePoint)
                    : sprintf('"\u%04x\u%04x"', 0xd800 + (($codePoint - 0x10000) >> 10), 0xdc00 + (($codePoint - 0x10000) & 0x3ff));

                return json_decode($escaped, flags: JSON_THROW_ON_ERROR);
            }

            return $match[0] === '\e' ? "\x1b" : stripcslashes($match[0]);
        }, $text);
    }

    private function shortName(string $name): string
    {
        $segments = explode('\\', $name);

        return end($segments);
    }
}
