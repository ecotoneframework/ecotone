<?php

namespace Ecotone\Modelling;

/**
 * licence Apache-2.0
 */
interface LazyRepositoryBuilder extends RepositoryBuilder
{
    public function build(): EventSourcedRepository|StandardRepository;
}
