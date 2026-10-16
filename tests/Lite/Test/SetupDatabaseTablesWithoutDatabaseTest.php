<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Test;

use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class SetupDatabaseTablesWithoutDatabaseTest extends TestCase
{
    public function test_a_flow_test_without_a_database_module_boots_without_setting_up_tables(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: []);

        self::assertSame([], $ecotone->popRecordedEvents());
    }

    public function test_setting_up_tables_without_a_database_module_says_what_to_enable(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(enabledModules: []);

        try {
            $ecotone->setupDatabaseTables();
            self::fail('Expected setting up tables without a database module to be refused');
        } catch (ConfigurationException $exception) {
            self::assertStringContainsString('setupDatabaseTables() has no database tables to set up', $exception->getMessage());
            self::assertStringContainsString('enabledModules: [ModulePackageList::DBAL_PACKAGE]', $exception->getMessage());
            self::assertStringContainsString('or remove the call', $exception->getMessage());
        }
    }
}
