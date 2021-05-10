<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Gateway;

interface MixedReturningGateway
{
    public function executeNoParameter() :mixed;
}