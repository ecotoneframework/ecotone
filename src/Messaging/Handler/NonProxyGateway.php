<?php


namespace Ecotone\Messaging\Handler;

interface NonProxyGateway
{
    public function execute(array $methodArgumentValues);
}