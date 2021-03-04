<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion;


class InCorrectArrayDocblock
{
    /**
     * @var rabbitMq[]
     */
    private array $incorrectProperty;

    /**
     * @param rabbitMq[] $data
     */
    public function incorrectParameter(array $data) : void
    {

    }

    /**
     * @return rabbitMq[]
     */
    public function incorrectReturnType() : array
    {

    }
}