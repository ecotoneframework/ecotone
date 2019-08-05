<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Handler\Processor;

use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;

/**
 * Class AroundInterceptorObjectBuilderExample
 * @package Fixture\Handler\Processor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AroundInterceptorObjectBuilderExample implements AroundInterceptorObjectBuilder
{
    private $object;

    /**
     * AroundInterceptorObjectBuilderExample constructor.
     * @param $object
     */
    private function __construct($object)
    {
        $this->object = $object;
    }

    public static function create(object $object) : self
    {
        return new self($object);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptingInterfaceClassName(): string
    {
        return get_class($this->object);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): object
    {
        return $this->object;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return ["test"];
    }
}