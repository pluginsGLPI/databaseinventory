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
use PHPUnit\Framework\Attributes\DataProvider;
use PluginDatabaseinventoryCredentialType;

final class CredentialTypeTest extends DatabaseInventoryTestCase
{
    public function testPreDeleteItemPreventsRemoval(): void
    {
        $this->login();

        $type = new PluginDatabaseinventoryCredentialType();
        $this->assertTrue($type->getFromDBByCrit(['name' => 'MySQL']));

        $this->assertFalse($type->pre_deleteItem());
        $this->hasSessionMessages(ERROR, ['You cannot remove this type: MySQL']);
    }

    public static function moduleKeyProvider(): iterable
    {
        yield [1, 'mysql'];
        yield [2, 'oracle'];
        yield [3, 'db2'];
        yield [4, 'mssql'];
        yield [5, 'postgresql'];
        yield [6, 'mongodb'];
    }

    #[DataProvider('moduleKeyProvider')]
    public function testGetModuleKey(int $id, string $name): void
    {
        $this->assertSame($name, PluginDatabaseinventoryCredentialType::getModuleKeyById($id));
        $this->assertSame($id, PluginDatabaseinventoryCredentialType::getModuleKeyByName($name));
    }

    public function testGetModuleKeyReturnsNullForUnknownValues(): void
    {
        $this->assertNull(PluginDatabaseinventoryCredentialType::getModuleKeyById(999));
        $this->assertNull(PluginDatabaseinventoryCredentialType::getModuleKeyByName('unknown'));
    }
}
