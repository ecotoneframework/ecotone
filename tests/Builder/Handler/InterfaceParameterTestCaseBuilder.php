<?php
declare(strict_types=1);

namespace Builder\Handler;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InterfaceParameter;

/**
 * Class InterfaceParameterTestCaseBuilder
 * @package Builder\Handler
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
        return InterfaceParameter::create(
            new \ReflectionParameter(function ($x){}, "x")
        );
    }
}