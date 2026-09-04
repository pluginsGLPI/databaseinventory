<?php

/**
 * -------------------------------------------------------------------------
 * DatabaseInventory plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of DatabaseInventory.
 *
 * DatabaseInventory is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * DatabaseInventory is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DatabaseInventory. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2021-2023 by Teclib'.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://services.glpi-network.com
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\DatabaseInventory\Tests\Units;

use GlpiPlugin\DatabaseInventory\Tests\DatabaseInventoryTestCase;
use PluginDatabaseinventoryCredential;
use PluginDatabaseinventoryCredentialType;
use PluginDatabaseinventoryDatabaseParam;
use PluginDatabaseinventoryDatabaseParam_Credential;

final class DatabaseParamTest extends DatabaseInventoryTestCase
{
    public function testGetCredentialTypeLinkedReturnsLinkedModuleKeys(): void
    {
        $this->login();

        $mysql_type = new PluginDatabaseinventoryCredentialType();
        $this->assertTrue($mysql_type->getFromDBByCrit(['name' => 'MySQL']));

        $postgresql_type = new PluginDatabaseinventoryCredentialType();
        $this->assertTrue($postgresql_type->getFromDBByCrit(['name' => 'PostgreSQL']));

        $mysql_credential = $this->createItem(PluginDatabaseinventoryCredential::class, [
            'name'                                          => 'MySQL credential',
            'login'                                          => 'root',
            'plugin_databaseinventory_credentialtypes_id'    => $mysql_type->getID(),
        ]);

        $postgresql_credential = $this->createItem(PluginDatabaseinventoryCredential::class, [
            'name'                                          => 'PostgreSQL credential',
            'login'                                          => 'postgres',
            'plugin_databaseinventory_credentialtypes_id'    => $postgresql_type->getID(),
        ]);

        $dbparam = $this->createItem(PluginDatabaseinventoryDatabaseParam::class, [
            'name' => 'Param with credentials',
        ]);

        $this->createItem(PluginDatabaseinventoryDatabaseParam_Credential::class, [
            'plugin_databaseinventory_databaseparams_id' => $dbparam->getID(),
            'plugin_databaseinventory_credentials_id'    => $mysql_credential->getID(),
        ]);

        $this->createItem(PluginDatabaseinventoryDatabaseParam_Credential::class, [
            'plugin_databaseinventory_databaseparams_id' => $dbparam->getID(),
            'plugin_databaseinventory_credentials_id'    => $postgresql_credential->getID(),
        ]);

        $types = $dbparam->getCredentialTypeLinked();

        $this->assertEquals(['mysql', 'postgresql'], $types);
    }

    public function testGetCredentialTypeLinkedReturnsEmptyArrayWithoutCredentials(): void
    {
        $this->login();

        $dbparam = $this->createItem(PluginDatabaseinventoryDatabaseParam::class, [
            'name' => 'Param without credentials',
        ]);

        $this->assertSame([], $dbparam->getCredentialTypeLinked());
    }
}
