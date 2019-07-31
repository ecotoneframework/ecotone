<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Builder\Handler;

use Ecotone\Messaging\Handler\InterfaceParameter;

/**
 * Class InterfaceParameterTestCaseBuilder
 * @package Test\Ecotone\Messaging\Builder\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceParameterTestCaseBuilder
{
    /**
     * InterfaceParameterTestCaseBuilder constructor.
     */
    private function __construct()
    {

    }

    /**
     * @return InterfaceParameterTestCaseBuilder
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @return InterfaceParameter
     * @throws \ReflectionException
     */
    public function build() : InterfaceParameter
    {
        return InterfaceParameter::createNullable(
            new \ReflectionParameter(function ($x){}, "x")
        );
    }
}