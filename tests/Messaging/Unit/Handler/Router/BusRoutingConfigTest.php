<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Router;

use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BusRoutingConfigTest extends TestCase
{
    public static function optimizedProvider(): iterable
    {
        yield 'not optimized' => [false];
        yield 'optimized' => [true];
    }

    public static function routingConfig(bool $isOptimized): BusRoutingMapBuilder
    {
        $routingConfig = new BusRoutingMapBuilder();
        $routingConfig->addObjectRoute(AClass::class, 'AClassRouteChannel1', 3);
        $routingConfig->addObjectRoute(AClass::class, 'AClassRouteChannel2', 2);
        $routingConfig->addCatchAllRoute('CatchAllRouteChannel', -2);
        $routingConfig->addNamedRoute('test.named', 'testNamedChannel');
        $routingConfig->addNamedRoute('test.*', 'testChannelRegex');
        $routingConfig->addObjectAlias(AClass::class, 'test.a_class');

        if ($isOptimized) {
            $routingConfig->optimize();
        }

        return $routingConfig;
    }

    #[DataProvider('optimizedProvider')]
    public function test_it_can_route_by_named_classname(bool $isOptimized): void
    {
        $routingConfig = self::routingConfig($isOptimized);

        self::assertEquals([
            'AClassRouteChannel1',
            'AClassRouteChannel2',
            'testChannelRegex',
            'CatchAllRouteChannel',
        ], $routingConfig->get(AClass::class));
    }

    #[DataProvider('optimizedProvider')]
    public function test_it_can_route_by_inheritance(bool $isOptimized): void
    {
        $routingConfig = self::routingConfig($isOptimized);

        self::assertEquals(
            [
                'AClassRouteChannel1',
                'AClassRouteChannel2',
                'CatchAllRouteChannel',
            ],
            $routingConfig->get(BClass::class),
            'BClass should not inherit the named channel alias from AClass'
        );
    }

    #[DataProvider('optimizedProvider')]
    public function test_it_can_route_by_known_named_channel(bool $isOptimized): void
    {
        $routingConfig = self::routingConfig($isOptimized);

        self::assertEqualsCanonicalizing([
            'testNamedChannel',
            'testChannelRegex',
        ], $routingConfig->get('test.named'));
    }

    #[DataProvider('optimizedProvider')]
    public function test_it_can_route_by_unknown_named_channel(bool $isOptimized): void
    {
        $routingConfig = self::routingConfig($isOptimized);

        self::assertEqualsCanonicalizing(['testChannelRegex'], $routingConfig->get('test.unknown'));
    }
}

/**
 * @internal
 */
class AClass
{
}

/**
 * @internal
 */
class BClass extends AClass
{
}
