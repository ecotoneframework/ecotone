<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Builder\Handler;

use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;

/**
 * Class InterfaceParameterTestCaseBuilder
 * @package Test\SimplyCodedSoftware\Messaging\Builder\Handler
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