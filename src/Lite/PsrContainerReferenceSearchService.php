<?php
declare(strict_types=1);


namespace Ecotone\Lite;

use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Psr\Container\ContainerInterface;

/**
 * Class PsrContainerReferenceSearchService
 * @package Ecotone\Lite
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PsrContainerReferenceSearchService implements ReferenceSearchService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * PsrContainerReferenceSearchService constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference)
    {
        if (!$this->container->has($reference)) {
            throw ReferenceNotFoundException::create("Reference {$reference} was not found");
        }

        return $this->container->get($reference);
    }
}