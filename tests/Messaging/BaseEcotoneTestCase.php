<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 */
abstract class BaseEcotoneTestCase extends TestCase
{
    /**
     * @dataProvider enterpriseMode
     * @return iterable<string, array>
     */
    public static function enterpriseMode(): iterable
    {
        yield 'Open Core' => [false];
        yield 'Enterprise' => [true];
    }
}
