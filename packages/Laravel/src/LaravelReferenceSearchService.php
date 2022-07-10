<?php

namespace Ecotone\Laravel;

use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Illuminate\Contracts\Foundation\Application;

class LaravelReferenceSearchService implements ReferenceSearchService, ReferenceTypeFromNameResolver
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function get(string $reference): object
    {
        return $this->application->get($reference);
    }

    public function has(string $referenceName): bool
    {
        return $this->application->has($referenceName);
    }

    public function resolve(string $referenceName): Type
    {
        return TypeDescriptor::createFromVariable($this->application->get($referenceName));
    }
}