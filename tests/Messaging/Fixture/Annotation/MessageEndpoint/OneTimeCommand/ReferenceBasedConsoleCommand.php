<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;


use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\ConsoleCommand;

#[ClassReference("consoleCommand")]
class ReferenceBasedConsoleCommand
{
    public function __construct(\stdClass $class) {}

    #[ConsoleCommand("doSomething")]
    public function execute() : void
    {

    }
}