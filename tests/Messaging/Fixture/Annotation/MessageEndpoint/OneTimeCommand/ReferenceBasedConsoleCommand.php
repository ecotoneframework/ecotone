<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;


use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\ConsoleCommand;

#[ClassReference("consoleCommand")]
class ReferenceBasedConsoleCommand
{
    public function __construct(\stdClass $class) {}

    /**
     * @ConsoleCommand("doSomething")
     */
    public function execute() : void
    {

    }
}