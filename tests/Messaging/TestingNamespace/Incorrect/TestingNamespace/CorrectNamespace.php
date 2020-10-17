<?php


namespace Incorrect\TestingNamespace;


use Ecotone\Messaging\Annotation\ApplicationContext;

class CorrectNamespace
{
    #[ApplicationContext]
    public function someExtension() : array
    {
        return [];
    }
}