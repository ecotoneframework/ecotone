<?php


namespace TestingNamespace\Correct;

use Ecotone\Messaging\Attribute\ServiceContext;

class CorrectClass
{
    #[ServiceContext]
    public function someExtension() : array
    {
        return [];
    }
}