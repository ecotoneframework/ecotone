<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

class GatewayExecuteClass
{
    private $returnData;

    private function __construct($returnData)
    {
        $this->returnData = $returnData;
    }

    public static function createBuildClosure($returnData) : \Closure
    {
        return function() use($returnData) {
            return new self($returnData);
        };
    }

    public function execute()
    {
        return $this->returnData;
    }
}