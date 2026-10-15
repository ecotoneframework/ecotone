<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use function array_diff;
use function array_intersect;
use function array_key_first;
use function array_keys;
use function array_unique;
use function array_values;
use function class_exists;
use function count;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\EventTag;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use Ecotone\EventSourcing\Tagging\EventTagRegistry;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionResolver;

use function implode;

use ReflectionAttribute;
use ReflectionClass;

use function sprintf;

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
final class DecisionModelDefinitionBuilder
{
    /**
     * @param string[] $explicitTagNames
     * @param ?class-string $aggregateClassName
     */
    public static function buildFor(
        ClassDefinition $classDefinition,
        InterfaceToCallRegistry $interfaceToCallRegistry,
        EventTagRegistry $eventTagRegistry,
        array $explicitTagNames,
        ?string $aggregateClassName = null,
    ): DecisionModelDefinition|AggregateBackedDecisionModelDefinition {
        $className = $classDefinition->getClassType()->toString();

        self::assertPublicNoArgumentConstructor($className);
        self::assertNotAlsoAnAggregateOrSaga($className);
        self::assertNoMessageHandlerDeclaredOnTheModelItself($classDefinition, $interfaceToCallRegistry);

        $handledEventClasses = self::findHandledEventClasses($classDefinition, $interfaceToCallRegistry);

        if ($handledEventClasses === []) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s must define at least one #[Apply].',
                $className
            ));
        }

        if ($aggregateClassName !== null) {
            return self::aggregateBackedDefinition($className, $aggregateClassName, $explicitTagNames, $handledEventClasses, $interfaceToCallRegistry);
        }

        $tagNames = $explicitTagNames !== []
            ? array_values(array_unique($explicitTagNames))
            : self::intersectionOfTagNames($handledEventClasses, $eventTagRegistry);

        if ($tagNames === []) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is scoped by no tag name, so it would fold no event and guard nothing: its handled event(s) %s share no #[EventTag] name. Add #[EventTag] to the event(s) so that every handled event carries a common tag name, scope the model explicitly with #[DecisionModel(tags: [...])], or, if these are one event-sourced aggregate\'s own events, scope the model with #[DecisionModel(aggregate: Wallet::class)].',
                $className,
                self::tagNamesPerHandledEvent($handledEventClasses, $eventTagRegistry),
            ));
        }

        foreach ($handledEventClasses as $handledEventClass) {
            $eventTagNames = $eventTagRegistry->tagNamesFor($handledEventClass);
            $missing = array_diff($tagNames, $eventTagNames);

            if ($missing !== []) {
                throw ConfigurationException::create(sprintf(
                    "DecisionModel %s is scoped by tag(s) '%s', but its handled event %s does not carry %s.",
                    $className,
                    implode(', ', $tagNames),
                    $handledEventClass,
                    implode(', ', $missing)
                ));
            }
        }

        return new DecisionModelDefinition(
            $className,
            $tagNames,
            $handledEventClasses,
            self::literalTagValuesAgreedByEveryHandledEvent($className, $tagNames, $handledEventClasses, $eventTagRegistry),
        );
    }

    /**
     * @param string[] $tagNames
     * @param class-string[] $handledEventClasses
     * @return array<string, string>
     */
    private static function literalTagValuesAgreedByEveryHandledEvent(
        string $className,
        array $tagNames,
        array $handledEventClasses,
        EventTagRegistry $eventTagRegistry,
    ): array {
        $literalValuesPerEvent = [];
        foreach ($handledEventClasses as $handledEventClass) {
            $literalValuesPerEvent[$handledEventClass] = $eventTagRegistry->literalTagValuesFor($handledEventClass);
        }

        $agreedLiteralValues = [];
        foreach ($tagNames as $tagName) {
            $declaredValues = [];
            foreach ($literalValuesPerEvent as $handledEventClass => $literalValues) {
                if (! isset($literalValues[$tagName])) {
                    continue 2;
                }

                $declaredValues[$literalValues[$tagName]][] = $handledEventClass;
            }

            if (count($declaredValues) > 1) {
                throw ConfigurationException::create(sprintf(
                    "DecisionModel %s is scoped by tag '%s', but its handled events declare different class-level #[EventTag('%s')] literals: %s. One model folds one value per tag -- give the events the same literal, or scope the model by a tag the message supplies.",
                    $className,
                    $tagName,
                    $tagName,
                    self::describeDeclaredLiterals($declaredValues),
                ));
            }

            $agreedLiteralValues[$tagName] = (string) array_key_first($declaredValues);
        }

        return $agreedLiteralValues;
    }

    /**
     * @param array<string|int, class-string[]> $eventClassesByLiteralValue
     */
    private static function describeDeclaredLiterals(array $eventClassesByLiteralValue): string
    {
        $descriptions = [];
        foreach ($eventClassesByLiteralValue as $literalValue => $eventClasses) {
            $descriptions[] = sprintf("'%s' on %s", $literalValue, implode(', ', $eventClasses));
        }

        return implode('; ', $descriptions);
    }

    /**
     * @param string[] $explicitTagNames
     * @param class-string[] $handledEventClasses
     */
    private static function aggregateBackedDefinition(
        string $className,
        string $aggregateClassName,
        array $explicitTagNames,
        array $handledEventClasses,
        InterfaceToCallRegistry $interfaceToCallRegistry,
    ): AggregateBackedDecisionModelDefinition {
        if ($explicitTagNames !== []) {
            throw ConfigurationException::create(sprintf(
                "DecisionModel %s declares both aggregate: %s and tags: '%s' -- a model is scoped by one aggregate instance or by tag names, never both, because an aggregate's own events carry no #[EventTag]. Drop one of the two.",
                $className,
                $aggregateClassName,
                implode("', '", $explicitTagNames),
            ));
        }

        if (! class_exists($aggregateClassName)) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is backed by %s, which is not a loadable class -- back a model with an #[EventSourcingAggregate] class name.',
                $className,
                $aggregateClassName,
            ));
        }

        self::assertBackedByAnEventSourcedAggregate($className, $aggregateClassName);

        $recordedEventClasses = self::eventClassesRecordedBy($aggregateClassName, $interfaceToCallRegistry);
        $notRecorded = array_values(array_diff($handledEventClasses, $recordedEventClasses));

        if ($notRecorded !== []) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is backed by aggregate %s, but its handled event(s) %s are never recorded by %s, so the model could never receive them. %s records %s.',
                $className,
                $aggregateClassName,
                implode(', ', $notRecorded),
                $aggregateClassName,
                $aggregateClassName,
                $recordedEventClasses === [] ? 'no event' : implode(', ', $recordedEventClasses),
            ));
        }

        $aggregateDefinition = AggregateDefinitionResolver::resolve($aggregateClassName, $interfaceToCallRegistry);

        return new AggregateBackedDecisionModelDefinition(
            $className,
            $aggregateClassName,
            $aggregateDefinition->getAggregateClassType(),
            $aggregateDefinition->getAggregateStreamName(),
            array_keys($aggregateDefinition->getAggregateIdentifierMapping()),
            $handledEventClasses,
        );
    }

    private static function assertBackedByAnEventSourcedAggregate(string $className, string $aggregateClassName): void
    {
        $reflectionClass = new ReflectionClass($aggregateClassName);

        if ($reflectionClass->getAttributes(Saga::class) !== [] || $reflectionClass->getAttributes(EventSourcingSaga::class) !== []) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is backed by %s, which is a saga -- sagas are outside the Dynamic Consistency Boundary, so a decision folded from one would be unguarded. Back the model with an #[EventSourcingAggregate], or scope it by tag names.',
                $className,
                $aggregateClassName,
            ));
        }

        if ($reflectionClass->getAttributes(EventSourcingAggregate::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
            return;
        }

        if ($reflectionClass->getAttributes(Aggregate::class, ReflectionAttribute::IS_INSTANCEOF) !== []) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is backed by %s, which is a state-stored #[Aggregate] and records no events, so there is no history to fold. Fetch it with #[Fetch] instead, which is already guarded by its counter.',
                $className,
                $aggregateClassName,
            ));
        }

        throw ConfigurationException::create(sprintf(
            'DecisionModel %s is backed by %s, which is not an #[EventSourcingAggregate] -- only an event-sourced aggregate has a stream of its own to fold from.',
            $className,
            $aggregateClassName,
        ));
    }

    /**
     * @return class-string[]
     */
    private static function eventClassesRecordedBy(string $aggregateClassName, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $classDefinition = $interfaceToCallRegistry->getClassDefinitionFor(Type::object($aggregateClassName));
        $applyAnnotation = Type::object(Apply::class);

        $recordedEventClasses = [];
        foreach ($classDefinition->getPublicMethodNames() as $method) {
            $interfaceToCall = $interfaceToCallRegistry->getFor($aggregateClassName, $method);

            if (! $interfaceToCall->hasMethodAnnotation($applyAnnotation)) {
                continue;
            }

            if ($interfaceToCall->getInterfaceParameterAmount() < 1 || ! $interfaceToCall->getFirstParameter()->isClassOrInterface()) {
                continue;
            }

            $recordedEventClasses[] = $interfaceToCall->getFirstParameter()->getTypeHint();
        }

        return array_values(array_unique($recordedEventClasses));
    }

    private static function assertPublicNoArgumentConstructor(string $className): void
    {
        $reflectionClass = new ReflectionClass($className);

        if ($reflectionClass->hasMethod('__construct')) {
            $constructor = $reflectionClass->getMethod('__construct');

            if ($constructor->getParameters() !== []) {
                throw ConfigurationException::create("Constructor for DecisionModel {$className} should not have any parameters.");
            }

            if (! $constructor->isPublic()) {
                throw ConfigurationException::create("Constructor for DecisionModel {$className} should be public.");
            }
        }
    }

    private static function assertNotAlsoAnAggregateOrSaga(string $className): void
    {
        foreach ((new ReflectionClass($className))->getAttributes(Aggregate::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel %s is also declared #[%s] -- a class is either a decision model, injected into handlers, or an aggregate/saga, loaded by identifier. Move the model into its own class and inject it into the aggregate\'s handler.',
                $className,
                $attribute->getName(),
            ));
        }
    }

    private static function assertNoMessageHandlerDeclaredOnTheModelItself(ClassDefinition $classDefinition, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $className = $classDefinition->getClassType()->toString();

        foreach ([CommandHandler::class, EventHandler::class, QueryHandler::class] as $handlerAnnotationClass) {
            $annotationType = Type::object($handlerAnnotationClass);

            foreach ($classDefinition->getPublicMethodNames() as $method) {
                $interfaceToCall = $interfaceToCallRegistry->getFor($className, $method);

                if ($interfaceToCall->hasMethodAnnotation($annotationType)) {
                    throw ConfigurationException::create(sprintf(
                        '%s::%s is a %s, but %s is a DecisionModel -- models are injected, they never own handlers.',
                        $className,
                        $method,
                        $handlerAnnotationClass,
                        $className,
                    ));
                }
            }
        }
    }

    /**
     * @return class-string[]
     */
    private static function findHandledEventClasses(ClassDefinition $classDefinition, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $className = $classDefinition->getClassType()->toString();
        $applyAnnotation = Type::object(Apply::class);

        $handledEventClasses = [];
        foreach ($classDefinition->getPublicMethodNames() as $method) {
            $interfaceToCall = $interfaceToCallRegistry->getFor($className, $method);

            if (! $interfaceToCall->hasMethodAnnotation($applyAnnotation)) {
                continue;
            }

            if ($interfaceToCall->isStaticallyCalled()) {
                throw ConfigurationException::create("{$interfaceToCall} is an #[Apply] method and should not be static.");
            }

            if ($interfaceToCall->getInterfaceParameterAmount() < 1 || ! $interfaceToCall->getFirstParameter()->isClassOrInterface()) {
                throw ConfigurationException::create("{$interfaceToCall} is an #[Apply] method and should have first parameter as Event Class type hint.");
            }

            $firstParameterTypeHint = $interfaceToCall->getFirstParameter()->getTypeHint();
            if (interface_exists($firstParameterTypeHint) && ! class_exists($firstParameterTypeHint)) {
                throw ConfigurationException::create("{$interfaceToCall} is a DecisionModel #[Apply] method and its first parameter must be a concrete Event class, an interface or union type is not allowed, as its tag names could not be derived.");
            }

            $handledEventClasses[] = $firstParameterTypeHint;
        }

        return array_values(array_unique($handledEventClasses));
    }

    /**
     * @param class-string[] $handledEventClasses
     */
    private static function tagNamesPerHandledEvent(array $handledEventClasses, EventTagRegistry $eventTagRegistry): string
    {
        $descriptions = [];
        foreach ($handledEventClasses as $handledEventClass) {
            $eventTagNames = $eventTagRegistry->tagNamesFor($handledEventClass);

            $descriptions[] = match (true) {
                $eventTagNames !== [] => sprintf('%s (%s)', $handledEventClass, implode(', ', $eventTagNames)),
                self::declaresEventTag($handledEventClass) => sprintf("%s (declares #[EventTag] but was not found by Ecotone's class scan -- add its namespace to the scanned namespaces)", $handledEventClass),
                default => sprintf('%s (no #[EventTag])', $handledEventClass),
            };
        }

        return implode(', ', $descriptions);
    }

    private static function declaresEventTag(string $eventClass): bool
    {
        for ($reflectionClass = new ReflectionClass($eventClass); $reflectionClass !== false; $reflectionClass = $reflectionClass->getParentClass()) {
            $members = [$reflectionClass, ...$reflectionClass->getProperties(), ...$reflectionClass->getMethods()];
            foreach ($members as $member) {
                if ($member->getAttributes(EventTag::class) !== []) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param class-string[] $handledEventClasses
     * @return string[]
     */
    private static function intersectionOfTagNames(array $handledEventClasses, EventTagRegistry $eventTagRegistry): array
    {
        $tagNames = null;
        foreach ($handledEventClasses as $handledEventClass) {
            $eventTagNames = $eventTagRegistry->tagNamesFor($handledEventClass);
            $tagNames = $tagNames === null ? $eventTagNames : array_values(array_intersect($tagNames, $eventTagNames));
        }

        return $tagNames ?? [];
    }
}
