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

use GLPIKey;
use GlpiPlugin\DatabaseInventory\Tests\DatabaseInventoryTestCase;
use PluginDatabaseinventoryCredential;
use PluginDatabaseinventoryDatabaseParam;
use PluginDatabaseinventoryDatabaseParam_Credential;

final class CredentialTest extends DatabaseInventoryTestCase
{
    public function testPasswordIsEncryptedOnAdd(): void
    {
        $this->login();

        $credential = $this->createItem(
            PluginDatabaseinventoryCredential::class,
            [
                'name'     => 'Test credential',
                'login'    => 'glpi',
                'password' => 'secret_password',
                'port'     => 3306,
            ],
            ['password'],
        );

        $this->assertNotSame('secret_password', $credential->fields['password']);
        $this->assertSame('secret_password', (new GLPIKey())->decrypt($credential->fields['password']));
    }

    public function testBlankPasswordFlagResetsPasswordOnUpdate(): void
    {
        $this->login();

        $credential = $this->createItem(
            PluginDatabaseinventoryCredential::class,
            [
                'name'     => 'Credential to blank',
                'login'    => 'glpi',
                'password' => 'secret_password',
            ],
            ['password'],
        );

        $credential = $this->updateItem(
            PluginDatabaseinventoryCredential::class,
            $credential->getID(),
            [
                '_blank_passwd' => true,
            ],
            ['_blank_passwd'],
        );

        $this->assertSame('', $credential->fields['password']);
    }

    public function testPostPurgeItemDeletesRelatedData(): void
    {
        $this->login();

        $credential = $this->createItem(PluginDatabaseinventoryCredential::class, [
            'name'  => 'Credential to purge',
            'login' => 'glpi',
        ]);

        $dbparam = $this->createItem(PluginDatabaseinventoryDatabaseParam::class, [
            'name' => 'Param linked to purged credential',
        ]);

        $link = $this->createItem(PluginDatabaseinventoryDatabaseParam_Credential::class, [
            'plugin_databaseinventory_databaseparams_id' => $dbparam->getID(),
            'plugin_databaseinventory_credentials_id'    => $credential->getID(),
        ]);

        $this->deleteItem(PluginDatabaseinventoryCredential::class, $credential->getID(), true);

        $this->assertFalse((new PluginDatabaseinventoryDatabaseParam_Credential())->getFromDB($link->getID()));
    }
}
