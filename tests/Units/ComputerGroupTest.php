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

use Computer;
use GlpiPlugin\DatabaseInventory\Tests\DatabaseInventoryTestCase;
use PluginDatabaseinventoryComputerGroup;
use PluginDatabaseinventoryComputerGroupDynamic;
use PluginDatabaseinventoryComputerGroupStatic;
use PluginDatabaseinventoryDatabaseParam;
use PluginDatabaseinventoryDatabaseParam_ComputerGroup;

final class ComputerGroupTest extends DatabaseInventoryTestCase
{
    public function testCountStaticItem(): void
    {
        $this->login();

        $group = $this->createItem(PluginDatabaseinventoryComputerGroup::class, [
            'name' => 'Static group test',
        ]);

        $this->assertEquals(0, $group->countStaticItem());

        $computer = $this->createItem(Computer::class, [
            'name'        => 'Test computer for group',
            'entities_id' => 0,
        ]);

        $this->createItem(PluginDatabaseinventoryComputerGroupStatic::class, [
            'plugin_databaseinventory_computergroups_id' => $group->getID(),
            'computers_id'                                => $computer->getID(),
        ]);

        $this->assertEquals(1, $group->countStaticItem());
    }

    public function testCountDynamicItem(): void
    {
        $this->login();

        $group = $this->createItem(PluginDatabaseinventoryComputerGroup::class, [
            'name' => 'Dynamic group test',
        ]);

        $this->assertEquals(0, $group->countDynamicItem());

        $computer = $this->createItem(Computer::class, [
            'name'        => 'Test computer for dynamic group',
            'entities_id' => getItemByTypeName('Entity', '_test_root_entity', true),
        ]);

        $this->createItem(PluginDatabaseinventoryComputerGroupDynamic::class, [
            'plugin_databaseinventory_computergroups_id' => $group->getID(),
            'search'                                       => json_encode([
                'criteria' => [
                    [
                        'field'      => 2, // Computer ID
                        'searchtype' => 'contains',
                        'value'      => $computer->getID(),
                    ],
                ],
            ]),
        ]);

        $this->assertEquals(1, $group->countDynamicItem());
    }

    public function testPostPurgeItemDeletesRelatedData(): void
    {
        $this->login();

        $group = $this->createItem(PluginDatabaseinventoryComputerGroup::class, [
            'name' => 'Group to purge',
        ]);

        $computer = $this->createItem(Computer::class, [
            'name'        => 'Computer for purged group',
            'entities_id' => 0,
        ]);

        $static = $this->createItem(PluginDatabaseinventoryComputerGroupStatic::class, [
            'plugin_databaseinventory_computergroups_id' => $group->getID(),
            'computers_id'                                => $computer->getID(),
        ]);

        $dynamic = $this->createItem(PluginDatabaseinventoryComputerGroupDynamic::class, [
            'plugin_databaseinventory_computergroups_id' => $group->getID(),
            'search'                                       => json_encode(['criteria' => []]),
        ]);

        $dbparam = $this->createItem(PluginDatabaseinventoryDatabaseParam::class, [
            'name' => 'Param linked to purged group',
        ]);

        $link = $this->createItem(PluginDatabaseinventoryDatabaseParam_ComputerGroup::class, [
            'plugin_databaseinventory_databaseparams_id' => $dbparam->getID(),
            'plugin_databaseinventory_computergroups_id' => $group->getID(),
        ]);

        $this->deleteItem(PluginDatabaseinventoryComputerGroup::class, $group->getID(), true);

        $this->assertFalse((new PluginDatabaseinventoryComputerGroupStatic())->getFromDB($static->getID()));
        $this->assertFalse((new PluginDatabaseinventoryComputerGroupDynamic())->getFromDB($dynamic->getID()));
        $this->assertFalse((new PluginDatabaseinventoryDatabaseParam_ComputerGroup())->getFromDB($link->getID()));
    }
}
