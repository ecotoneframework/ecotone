<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;

class OneTimeWithConstructorParametersCommandExample
{
    public function __construct(string $name)
    {

    }
    
    #[ConsoleCommand("doSomething")]
    public function execute() : void
    {

    }
}