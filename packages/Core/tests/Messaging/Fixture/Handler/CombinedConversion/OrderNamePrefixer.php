<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;

/**
 * Class OrderNameTransformer
 * @package Fixture\Handler\CombinedConversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderNamePrefixer
{
    /**
     * @var string
     */
    private $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function transform(array $order) : array
    {
        $order['name'] = $this->prefix . $order['name'];

        return $order;
    }
}