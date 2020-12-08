<?php


namespace Incorrect\TestingNamespace;


use Ecotone\Messaging\Annotation\ServiceContext;

class CorrectNamespace
{
    #[ServiceContext]
    public function someExtension() : array
    {
        return [];
    }
}