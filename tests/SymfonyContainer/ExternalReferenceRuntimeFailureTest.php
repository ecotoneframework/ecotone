<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\Attribute\CommandHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

/**
 * Runtime failures for external references that the container passed to
 * EcotoneLite cannot provide. Bootstrap stays lazy — a missing reference must
 * NOT block boot; it must fail at dispatch with an error naming the missing
 * service, and dispatching again must report the very same error instead of a
 * misleading follow-up ("no message handler registered") caused by half-built
 * services left behind by the first failure.
 *
 * licence Apache-2.0
 * @internal
 */
final class ExternalReferenceRuntimeFailureTest extends TestCase
{
    public function test_missing_handler_dependency_fails_at_dispatch_with_honest_error(): void
    {
        $messagingSystem = $this->bootstrapWith(new RecordingPsrContainer([
            RuntimeCheckedHandler::class => fn () => new RuntimeCheckedHandler(),
        ]));

        try {
            $messagingSystem->getCommandBus()->sendWithRouting('runtime_failure.lite.run', 'payload');

            $this->fail('Dispatch must fail: RuntimeCheckedHandler references RuntimeCheckedCollaborator which the container does not provide');
        } catch (Throwable $exception) {
            $this->assertStringContainsString(
                'RuntimeCheckedCollaborator',
                $exception->getMessage(),
                'The dispatch error must name the unresolvable reference',
            );
        }
    }

    public function test_dependency_registered_through_factory_is_resolved_at_dispatch(): void
    {
        $container = new RecordingPsrContainer([
            RuntimeCheckedHandler::class => fn () => new RuntimeCheckedHandler(),
            RuntimeCheckedCollaborator::class => fn () => new class () implements RuntimeCheckedCollaborator {
            },
        ]);

        $messagingSystem = $this->bootstrapWith($container);

        $this->assertSame(
            'payload',
            $messagingSystem->getCommandBus()->sendWithRouting('runtime_failure.lite.run', 'payload'),
        );
        $this->assertContains(
            RuntimeCheckedCollaborator::class,
            $container->invokedFactories,
            'The collaborator must have been resolved through the registered factory',
        );
    }

    public function test_instantiable_class_absent_from_container_still_fails_because_lite_does_not_autowire(): void
    {
        $messagingSystem = $this->bootstrapWith(new RecordingPsrContainer([
            HandlerNeedingConcreteCollaborator::class => fn () => new HandlerNeedingConcreteCollaborator(),
        ]));

        try {
            $messagingSystem->getCommandBus()->sendWithRouting('runtime_failure.lite.concrete', 'payload');

            $this->fail('Dispatch must fail: ConcreteCollaborator is instantiable, but Lite resolves references only from the container');
        } catch (Throwable $exception) {
            $this->assertStringContainsString(
                'ConcreteCollaborator',
                $exception->getMessage(),
                'The dispatch error must name the reference the container cannot provide',
            );
        }
    }

    public function test_failed_dispatch_reports_the_same_error_when_repeated(): void
    {
        $messagingSystem = $this->bootstrapWith(new RecordingPsrContainer([
            RuntimeCheckedHandler::class => fn () => new RuntimeCheckedHandler(),
        ]));

        $firstException = $this->dispatchAndCaptureFailure($messagingSystem);
        $secondException = $this->dispatchAndCaptureFailure($messagingSystem);

        $this->assertSame($firstException::class, $secondException::class);
        $this->assertSame(
            $firstException->getMessage(),
            $secondException->getMessage(),
            'A failed dispatch must not leave half-built services behind that change the error on the next attempt',
        );
    }

    private function dispatchAndCaptureFailure(ConfiguredMessagingSystem $messagingSystem): Throwable
    {
        try {
            $messagingSystem->getCommandBus()->sendWithRouting('runtime_failure.lite.run', 'payload');
        } catch (Throwable $exception) {
            return $exception;
        }

        $this->fail('Dispatch was expected to fail');
    }

    private function bootstrapWith(RecordingPsrContainer $container): ConfiguredMessagingSystem
    {
        $classesToResolve = [];
        foreach (array_keys($container->factories) as $registeredId) {
            if (str_contains($registeredId, 'Handler')) {
                $classesToResolve[] = $registeredId;
            }
        }

        return EcotoneLite::bootstrap(
            classesToResolve: $classesToResolve,
            containerOrAvailableServices: $container,
            configuration: ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );
    }
}

interface RuntimeCheckedCollaborator
{
}

final class ConcreteCollaborator
{
}

final class RuntimeCheckedHandler
{
    #[CommandHandler('runtime_failure.lite.run')]
    public function run(string $payload, RuntimeCheckedCollaborator $collaborator): string
    {
        return $payload;
    }
}

final class HandlerNeedingConcreteCollaborator
{
    #[CommandHandler('runtime_failure.lite.concrete')]
    public function run(string $payload, ConcreteCollaborator $collaborator): string
    {
        return $payload;
    }
}

final class RecordingPsrContainer implements ContainerInterface
{
    /** @var string[] */
    public array $invokedFactories = [];

    /**
     * @param array<string, callable(): object> $factories
     */
    public function __construct(public readonly array $factories)
    {
    }

    public function get(string $id): mixed
    {
        if (! isset($this->factories[$id])) {
            throw new class ('Service not found: ' . $id) extends RuntimeException implements \Psr\Container\NotFoundExceptionInterface {
            };
        }

        $this->invokedFactories[] = $id;

        return ($this->factories[$id])();
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }
}
