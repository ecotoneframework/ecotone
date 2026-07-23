<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\SymfonyContainer\ResilientDumpedContainer;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Reference;
use Throwable;

/**
 * The dumped container memoizes a shared service BEFORE running its method
 * calls whenever the graph is circular (channel -> subscribe(handler) ->
 * handler(channel)). Symfony's own make() only cleans up the id that was
 * requested — when the half-built service sits deeper in the graph (bus ->
 * channel), it stays memoized after the failure. The next resolution would
 * then silently return a channel with no subscribers (the "no message handler
 * registered" failure mode) instead of repeating the honest error.
 *
 * licence Apache-2.0
 * @internal
 */
final class DiscardsHalfBuiltServicesTest extends TestCase
{
    public function test_failed_resolution_discards_nested_half_built_services_and_repeats_the_same_error(): void
    {
        $container = $this->dumpedCircularGraph();

        $firstException = $this->getAndCaptureFailure($container, 'bus');
        $secondException = $this->getAndCaptureFailure($container, 'bus');

        $this->assertSame($firstException::class, $secondException::class);
        $this->assertSame(
            $firstException->getMessage(),
            $secondException->getMessage(),
            'The half-built channel nested under the bus must not stay memoized after the failed resolution',
        );
    }

    public function test_resolution_succeeds_and_is_memoized_when_the_wiring_is_complete(): void
    {
        $container = $this->dumpedCircularGraph();
        $container->set('missing.external', new stdClass());

        $bus = $container->get('bus');

        $this->assertInstanceOf(BusStub::class, $bus);
        $this->assertCount(1, $bus->channel->subscribers, 'The subscriber wired through the circular graph must be registered');
        $this->assertSame($bus, $container->get('bus'), 'A successfully built service must stay memoized');
    }

    private function getAndCaptureFailure(Container $container, string $id): Throwable
    {
        try {
            $container->get($id);
        } catch (Throwable $exception) {
            return $exception;
        }

        $this->fail('Resolution was expected to fail');
    }

    private function dumpedCircularGraph(): Container
    {
        $builder = new ContainerBuilder();

        $builder->register('channel', ChannelStub::class)
            ->setPublic(true)
            ->addMethodCall('subscribe', [new Reference('handler')]);

        $builder->register('handler', HandlerStub::class)
            ->setPublic(true)
            ->setArguments([new Reference('channel'), new Reference('missing.external')]);

        $builder->register('missing.external', stdClass::class)
            ->setPublic(true)
            ->setSynthetic(true);

        $builder->register('bus', BusStub::class)
            ->setPublic(true)
            ->setArguments([new Reference('channel')]);

        $builder->compile();

        $className = 'DumpedCircularGraphContainer_' . md5(uniqid('', true));
        $code = (new PhpDumper($builder))->dump([
            'class' => $className,
            'base_class' => '\\' . ResilientDumpedContainer::class,
        ]);

        eval(substr($code, strlen('<?php')));

        return new $className();
    }
}

final class ChannelStub
{
    /** @var object[] */
    public array $subscribers = [];

    public function subscribe(object $handler): void
    {
        $this->subscribers[] = $handler;
    }
}

final class HandlerStub
{
    public function __construct(ChannelStub $channel, object $externalCollaborator)
    {
    }
}

final class BusStub
{
    public function __construct(public ChannelStub $channel)
    {
    }
}
