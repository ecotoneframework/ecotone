<?php


namespace Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert;


class ThreeLevelNestedObjectProperty
{
    private TwoLevelNestedObjectProperty $data;

    /**
     * TwoLevelNestedObjectProperty constructor.
     * @param TwoLevelNestedObjectProperty $data
     */
    public function __construct(TwoLevelNestedObjectProperty $data)
    {
        $this->data = $data;
    }
}