<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Projecting\ProjectionOperations;

/**
 * licence Apache-2.0
 */
final class WithoutDatabaseTables implements DatabaseTablesSetUp
{
    public function __construct(private ProjectionOperations $projectionOperations)
    {
    }

    public function setUpEveryTable(): void
    {
        throw ConfigurationException::create(
            'setupDatabaseTables() has no database tables to set up: none of the modules this test enables keeps its data in a database. '
            . 'Enable the DBAL module, which the database tables belong to - EcotoneLite::bootstrapFlowTesting(..., enabledModules: [ModulePackageList::DBAL_PACKAGE]) - '
            . 'and register the DbalConnectionFactory the tables are created through; '
            . 'or remove the call, when the test is not meant to use a database.'
        );
    }

    public function setUpTablesOfProvidedConnections(): void
    {
    }

    public function initializeProjections(): void
    {
        $this->projectionOperations->initializeAll();
    }
}
