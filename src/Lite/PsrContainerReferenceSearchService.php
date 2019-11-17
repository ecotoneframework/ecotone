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
     * @var array
     */
    private $defaults;

    /**
     * PsrContainerReferenceSearchService constructor.
     * @param ContainerInterface $container
     * @param array $defaults
     */
    public function __construct(ContainerInterface $container, array $defaults = [])
    {
        $this->container = $container;
        $this->defaults = $defaults;
    }

    /**
     * @inheritDoc
     */
    public function get(string $reference)
    {
        if (!$this->container->has($reference)) {
            if (array_key_exists($reference, $this->defaults)) {
                return $this->defaults[$reference];
            }

            throw ReferenceNotFoundException::create("Reference {$reference} was not found");
        }

        return $this->container->get($reference);
    }
}