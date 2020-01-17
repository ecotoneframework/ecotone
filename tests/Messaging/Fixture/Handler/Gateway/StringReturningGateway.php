<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;


interface StringReturningGateway
{
    public function execute(string $replyMediaType) : string;
}