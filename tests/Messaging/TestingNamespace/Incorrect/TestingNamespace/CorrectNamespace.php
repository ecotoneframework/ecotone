<?php


namespace Incorrect\TestingNamespace;


use Ecotone\Messaging\Attribute\ServiceContext;

class CorrectNamespace
{
    #[ServiceContext]
    public function someExtension() : array
    {
        return [];
    }
}