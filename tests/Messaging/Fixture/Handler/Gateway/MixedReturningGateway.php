<?php

namespace Ecotone\Tests\Messaging\Fixture\Handler\Gateway;

interface MixedReturningGateway
{
    public function executeNoParameter() :mixed;
}