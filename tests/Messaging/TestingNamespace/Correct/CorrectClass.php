<?php


namespace TestingNamespace\Correct;

use Ecotone\Messaging\Annotation\ServiceContext;

class CorrectClass
{
    #[ServiceContext]
    public function someExtension() : array
    {
        return [];
    }
}