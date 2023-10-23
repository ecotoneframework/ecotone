<?php

namespace Ecotone\Modelling;

interface LazyRepositoryBuilder extends RepositoryBuilder
{
    public function build(): EventSourcedRepository|StandardRepository;
}
