<?php


namespace TestingNamespace\Correct;

use Ecotone\Messaging\Annotation\ApplicationContext;

class CorrectClass
{
    #[ApplicationContext]
    public function someExtension() : array
    {
        return [];
    }
}