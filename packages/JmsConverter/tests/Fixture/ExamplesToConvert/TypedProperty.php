<?php


namespace Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert;


class TypedProperty
{
    private int $data;

    /**
     * TypedProperty constructor.
     * @param $data
     */
    public function __construct(int $data)
    {
        $this->data = $data;
    }
}