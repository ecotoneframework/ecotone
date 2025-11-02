<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Messaging\Handler\Gateway\ProxyGenerator;

use function file_get_contents;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ProxyGeneratorTest extends TestCase
{
    public function test_proxy_generation(): void
    {
        $proxyGenerator = new ProxyGenerator('Ecotone\\__Proxy__');

        try {
            self::assertEquals(
                file_get_contents(__DIR__ . '/ProxyGeneratorTest.php.snapshot'),
                $proxyGenerator->generateProxyFor('GeneratedClass', InterfaceForProxyGeneration::class)
            );
        } catch (ExpectationFailedException $e) {
            self::assertEquals(
                file_get_contents(__DIR__ . '/ProxyGeneratorTest.php.snapshot_v2'),
                $proxyGenerator->generateProxyFor('GeneratedClass', InterfaceForProxyGeneration::class)
            );
        }
    }

}

/**
 * @internal
 */
interface InterfaceForProxyGeneration
{
    public function doSomething(): void;
    public function doSomethingAndReturnSomething(): mixed;
    public function doSomethingWithDefaultParameter(array $param = []): mixed;
    public function doSomethingWithNullableParameter(?string $param): void;
    public function doSomethingWithNoType($param): void;
    public function nullableDefaultParameter(?string $param = null): void;
    public function unionReturnType(?string $param = null): string|int;
}
